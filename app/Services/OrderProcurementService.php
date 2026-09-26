<?php

namespace App\Services;

use App\Models\OrderIntake;
use App\Models\OrderMaterialLine;
use App\Models\OrderProcurementRequest;
use App\Models\OrderSupplierQuote;
use App\Models\StockLevel;
use App\Models\User;
use App\Support\OrderOperations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderProcurementService
{
    public function __construct(
        private AuditService $audit,
        private NotifyService $notify,
        private OrderMaterialUsageService $materialUsage,
    ) {}

    /**
     * @param  list<array{item_name:string, quantity:float|int|string, unit?:string|null, item_id?:int|null, notes?:string|null}>  $lines
     * @return list<OrderMaterialLine>
     */
    public function submitMaterials(OrderIntake $intake, User $actor, array $lines, ?Request $request = null): array
    {
        $assigned = $intake->assignments()
            ->where('role_key', OrderOperations::ROLE_DESIGNER)
            ->where('user_id', $actor->id)
            ->exists();

        if (! $actor->isDesigner() && ! $assigned && ! $actor->isOms()) {
            throw new InvalidArgumentException('Only the assigned designer can submit materials.');
        }

        $design = $intake->phases()->where('phase_key', OrderOperations::PHASE_DESIGN)->first();
        if ($design && $design->status !== OrderOperations::PHASE_COMPLETED) {
            throw new InvalidArgumentException('Design phase must be completed before materials submission.');
        }

        if ($lines === []) {
            throw new InvalidArgumentException('At least one material line is required.');
        }

        $created = [];
        DB::transaction(function () use ($intake, $actor, $lines, &$created) {
            foreach ($lines as $line) {
                $created[] = OrderMaterialLine::query()->create([
                    'order_intake_id' => $intake->id,
                    'item_name' => $line['item_name'],
                    'item_id' => $line['item_id'] ?? null,
                    'quantity' => $line['quantity'],
                    'unit' => $line['unit'] ?? 'pcs',
                    'notes' => $line['notes'] ?? null,
                    'stock_status' => OrderOperations::STOCK_PENDING,
                    'submitted_by' => $actor->id,
                ]);
            }

            $materials = $intake->phases()->where('phase_key', OrderOperations::PHASE_MATERIALS)->first();
            if ($materials && $materials->status === OrderOperations::PHASE_PENDING) {
                $materials->status = OrderOperations::PHASE_IN_PROGRESS;
                $materials->started_at = now();
                $materials->save();
            }
        });

        foreach ($this->notify->activeUserIdsWithRoles([OrderOperations::ROLE_COMPANY_MANAGER, OrderOperations::ROLE_OMS]) as $userId) {
            $this->notify->notifyUser($userId, [
                'type' => 'materials_submitted',
                'title' => 'Materials submitted',
                'message' => "Materials list submitted for {$intake->invoice_number}.",
                'entityType' => 'order_intake',
                'entityId' => (int) $intake->id,
            ]);
        }

        $this->audit->log($actor, 'order_intake', (int) $intake->id, 'SUBMIT_MATERIALS', [
            'count' => count($created),
        ], $request);

        return $created;
    }

    public function verifyStockAvailable(OrderMaterialLine $line, User $actor, ?Request $request = null): OrderMaterialLine
    {
        if (! $actor->canApproveOrderProcurement() && ! $actor->hasRole('company_manager')) {
            throw new InvalidArgumentException('Only Company Manager can verify stock.');
        }

        $line->stock_status = OrderOperations::STOCK_AVAILABLE;
        $line->reviewed_by = $actor->id;
        $line->save();

        $this->audit->log($actor, 'order_material_line', (int) $line->id, 'VERIFY_STOCK_AVAILABLE', null, $request);

        return $line->fresh();
    }

    public function markUnavailableAndProcure(OrderMaterialLine $line, User $actor, ?Request $request = null): OrderProcurementRequest
    {
        if (! $actor->hasRole('company_manager')) {
            throw new InvalidArgumentException('Only Company Manager can route procurement.');
        }

        return DB::transaction(function () use ($line, $actor, $request) {
            $line->stock_status = OrderOperations::STOCK_UNAVAILABLE;
            $line->reviewed_by = $actor->id;
            $line->save();

            $procurement = OrderProcurementRequest::query()->create([
                'order_intake_id' => $line->order_intake_id,
                'order_material_line_id' => $line->id,
                'status' => OrderOperations::PROCUREMENT_OPEN,
            ]);

            foreach ($this->notify->activeUserIdsWithRoles([OrderOperations::ROLE_PROCUREMENT]) as $userId) {
                $this->notify->notifyUser($userId, [
                    'type' => 'procurement_needed',
                    'title' => 'Procurement required',
                    'message' => "Material {$line->item_name} needs sourcing for order {$line->intake->invoice_number}.",
                    'entityType' => 'order_procurement_request',
                    'entityId' => (int) $procurement->id,
                ]);
            }

            $this->audit->log($actor, 'order_procurement_request', (int) $procurement->id, 'OPEN_PROCUREMENT', [
                'material_line_id' => $line->id,
            ], $request);

            return $procurement->fresh();
        });
    }

    /**
     * @param  array{supplier_name:string, unit_price:float|int|string, total_price?:float|int|string|null, quality_grade?:string|null, availability?:string|null, lead_time_days?:int|null, delivery_terms?:string|null, notes?:string|null}  $data
     */
    public function addQuote(OrderProcurementRequest $procurement, User $actor, array $data, ?Request $request = null): OrderSupplierQuote
    {
        if (! $actor->isProcurement() && ! $actor->isAdmin()) {
            throw new InvalidArgumentException('Only procurement can add supplier quotes.');
        }

        $qty = (float) ($procurement->materialLine?->quantity ?? 1);
        $unit = (float) $data['unit_price'];
        $total = isset($data['total_price']) ? (float) $data['total_price'] : $unit * $qty;

        $quote = OrderSupplierQuote::query()->create([
            'order_procurement_request_id' => $procurement->id,
            'supplier_name' => $data['supplier_name'],
            'unit_price' => $unit,
            'total_price' => $total,
            'quality_grade' => $data['quality_grade'] ?? null,
            'availability' => $data['availability'] ?? null,
            'lead_time_days' => $data['lead_time_days'] ?? null,
            'delivery_terms' => $data['delivery_terms'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $actor->id,
        ]);

        if ($procurement->status === OrderOperations::PROCUREMENT_OPEN) {
            $procurement->status = OrderOperations::PROCUREMENT_QUOTED;
            $procurement->save();
        }

        $this->audit->log($actor, 'order_supplier_quote', (int) $quote->id, 'ADD_SUPPLIER_QUOTE', [
            'supplier_name' => $quote->supplier_name,
        ], $request);

        return $quote;
    }

    public function recommendQuote(
        OrderProcurementRequest $procurement,
        OrderSupplierQuote $quote,
        User $actor,
        ?string $justification = null,
        ?Request $request = null,
    ): OrderProcurementRequest {
        if (! $actor->isProcurement() && ! $actor->isAdmin()) {
            throw new InvalidArgumentException('Only procurement can recommend a quote.');
        }

        if ((int) $quote->order_procurement_request_id !== (int) $procurement->id) {
            throw new InvalidArgumentException('Quote does not belong to this procurement request.');
        }

        if (trim((string) $justification) === '') {
            throw new InvalidArgumentException('Recommendation justification is required.');
        }

        return DB::transaction(function () use ($procurement, $quote, $actor, $justification, $request) {
            OrderSupplierQuote::query()
                ->where('order_procurement_request_id', $procurement->id)
                ->update(['is_selected' => false]);

            $quote->is_selected = true;
            $quote->save();

            $procurement->proposed_quote_id = $quote->id;
            $procurement->status = OrderOperations::PROCUREMENT_PENDING_APPROVAL;
            $procurement->notes = $justification;
            $procurement->save();

            foreach ($this->notify->activeUserIdsWithRoles([OrderOperations::ROLE_COMPANY_MANAGER]) as $userId) {
                $this->notify->notifyUser($userId, [
                    'type' => 'procurement_recommendation',
                    'title' => 'Supplier recommendation',
                    'message' => "Procurement recommended {$quote->supplier_name} for approval.",
                    'entityType' => 'order_procurement_request',
                    'entityId' => (int) $procurement->id,
                ]);
            }

            $this->audit->log($actor, 'order_procurement_request', (int) $procurement->id, 'RECOMMEND_SUPPLIER', [
                'quote_id' => $quote->id,
                'justification' => $justification,
            ], $request);

            return $procurement->fresh(['quotes', 'proposedQuote']);
        });
    }

    public function decideRecommendation(
        OrderProcurementRequest $procurement,
        User $actor,
        bool $approve,
        ?string $comment = null,
        ?Request $request = null,
    ): OrderProcurementRequest {
        if (! $actor->canApproveOrderProcurement()) {
            throw new InvalidArgumentException('Only Company Manager can approve supplier selection.');
        }

        if ($procurement->status !== OrderOperations::PROCUREMENT_PENDING_APPROVAL) {
            throw new InvalidArgumentException('Procurement is not awaiting approval.');
        }

        if ($procurement->status === OrderOperations::PROCUREMENT_FINALIZED) {
            throw new InvalidArgumentException('Procurement already finalized.');
        }

        return DB::transaction(function () use ($procurement, $actor, $approve, $comment, $request) {
            $procurement->approved_by = $actor->id;
            $procurement->approved_at = now();
            $procurement->status = $approve
                ? OrderOperations::PROCUREMENT_APPROVED
                : OrderOperations::PROCUREMENT_REJECTED;
            if ($comment) {
                $procurement->notes = trim(($procurement->notes ? $procurement->notes."\n" : '').'CM: '.$comment);
            }
            $procurement->save();

            if ($approve) {
                $procurement->status = OrderOperations::PROCUREMENT_FINALIZED;
                $procurement->save();

                if ($procurement->materialLine) {
                    $procurement->materialLine->stock_status = OrderOperations::STOCK_AVAILABLE;
                    $procurement->materialLine->reviewed_by = $actor->id;
                    $procurement->materialLine->save();
                }
            }

            foreach ($this->notify->activeUserIdsWithRoles([
                OrderOperations::ROLE_OMS,
                OrderOperations::ROLE_PROCUREMENT,
            ]) as $userId) {
                $this->notify->notifyUser($userId, [
                    'type' => 'procurement_decision',
                    'title' => $approve ? 'Supplier approved' : 'Supplier rejected',
                    'message' => 'Company Manager '.($approve ? 'approved' : 'rejected')." procurement for order {$procurement->intake->invoice_number}.",
                    'entityType' => 'order_procurement_request',
                    'entityId' => (int) $procurement->id,
                ]);
            }

            $this->audit->log($actor, 'order_procurement_request', (int) $procurement->id, $approve ? 'APPROVE_PROCUREMENT' : 'REJECT_PROCUREMENT', [
                'comment' => $comment,
            ], $request);

            return $procurement->fresh();
        });
    }

    public function releaseMaterials(OrderIntake $intake, User $actor, ?Request $request = null): OrderIntake
    {
        if (! $actor->hasRole('company_manager') && ! $actor->isOms()) {
            throw new InvalidArgumentException('Not authorized to release materials.');
        }

        $lines = $intake->materialLines;
        if ($lines->isEmpty()) {
            throw new InvalidArgumentException('No materials to release.');
        }

        $blocking = $lines->first(function (OrderMaterialLine $line) {
            return ! in_array($line->stock_status, [
                OrderOperations::STOCK_AVAILABLE,
                OrderOperations::STOCK_RELEASED,
            ], true);
        });

        if ($blocking) {
            throw new InvalidArgumentException('All materials must be available before release.');
        }

        $openProcurement = $intake->procurementRequests()
            ->whereNotIn('status', [
                OrderOperations::PROCUREMENT_FINALIZED,
                OrderOperations::PROCUREMENT_REJECTED,
            ])
            ->exists();

        if ($openProcurement) {
            throw new InvalidArgumentException('Open procurement must be finalized before release.');
        }

        return DB::transaction(function () use ($intake, $actor, $lines, $request) {
            foreach ($lines as $line) {
                $line->stock_status = OrderOperations::STOCK_RELEASED;
                $line->save();
            }

            $this->materialUsage->logRelease($intake, $actor, $lines);

            $materials = $intake->phases()->where('phase_key', OrderOperations::PHASE_MATERIALS)->first();
            if ($materials) {
                $materials->status = OrderOperations::PHASE_COMPLETED;
                $materials->completed_at = now();
                $materials->save();
            }

            $assembly = $intake->phases()->where('phase_key', OrderOperations::PHASE_ASSEMBLY)->first();
            if ($assembly && $assembly->status === OrderOperations::PHASE_PENDING) {
                $assembly->status = OrderOperations::PHASE_IN_PROGRESS;
                $assembly->started_at = now();
                $assembly->save();
            }

            foreach ($this->notify->activeUserIdsWithRoles([
                OrderOperations::ROLE_OMS,
                OrderOperations::ROLE_OMF,
            ]) as $userId) {
                $this->notify->notifyUser($userId, [
                    'type' => 'materials_released',
                    'title' => 'Materials released',
                    'message' => "Materials released to production for {$intake->invoice_number}.",
                    'entityType' => 'order_intake',
                    'entityId' => (int) $intake->id,
                ]);
            }

            $this->audit->log($actor, 'order_intake', (int) $intake->id, 'RELEASE_MATERIALS', null, $request);

            return $intake->fresh(['materialLines', 'phases']);
        });
    }

    public function stockOnHand(?int $itemId): float
    {
        if (! $itemId || ! class_exists(StockLevel::class)) {
            return 0;
        }

        return (float) StockLevel::query()->where('item_id', $itemId)->sum('quantity_on_hand');
    }
}
