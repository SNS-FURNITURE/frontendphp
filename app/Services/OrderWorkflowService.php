<?php

namespace App\Services;

use App\Models\OrderCheckpoint;
use App\Models\OrderIntake;
use App\Models\OrderPhase;
use App\Models\User;
use App\Support\OrderOperations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderWorkflowService
{
    public function __construct(
        private AuditService $audit,
        private NotifyService $notify,
    ) {}

    public function toggleCheckpoint(
        OrderCheckpoint $checkpoint,
        User $actor,
        bool $completed,
        ?Request $request = null,
    ): OrderCheckpoint {
        $phase = $checkpoint->phase()->with('intake')->firstOrFail();
        $intake = $phase->intake;

        $isDesignerAssignee = $intake->assignments()
            ->where('role_key', OrderOperations::ROLE_DESIGNER)
            ->where('user_id', $actor->id)
            ->exists();

        if (! $actor->isDesigner() && ! $isDesignerAssignee && ! $actor->isOms() && ! $actor->isAdmin()) {
            throw new InvalidArgumentException('Not authorized to update checkpoints.');
        }

        if ($phase->phase_key !== OrderOperations::PHASE_DESIGN) {
            throw new InvalidArgumentException('Checkpoints are only supported on the design phase.');
        }

        $checkpoint->is_completed = $completed;
        $checkpoint->completed_by = $completed ? $actor->id : null;
        $checkpoint->completed_at = $completed ? now() : null;
        $checkpoint->save();

        $this->notifyLiveAudience(
            $intake,
            'order_checkpoint',
            $completed ? 'Checkpoint completed' : 'Checkpoint reopened',
            "{$checkpoint->label} on {$intake->invoice_number}."
        );

        $this->audit->log($actor, 'order_checkpoint', (int) $checkpoint->id, $completed ? 'COMPLETE_CHECKPOINT' : 'REOPEN_CHECKPOINT', [
            'checkpoint_key' => $checkpoint->checkpoint_key,
        ], $request);

        $this->maybeCompleteDesignPhase($phase, $actor, $request);

        return $checkpoint->fresh();
    }

    public function completeProductionPhase(
        OrderIntake $intake,
        OrderPhase $phase,
        User $actor,
        ?Request $request = null,
    ): OrderPhase {
        if ((int) $phase->order_intake_id !== (int) $intake->id) {
            throw new InvalidArgumentException('Phase does not belong to this order.');
        }

        $isPmAssignee = $intake->assignments()
            ->where('role_key', OrderOperations::ROLE_PRODUCT_MANAGER)
            ->where('user_id', $actor->id)
            ->exists();

        if (! $actor->canSuperviseProductManager() && ! $isPmAssignee && ! $actor->isOms() && ! $actor->isAdmin()) {
            throw new InvalidArgumentException('Not authorized to update production phase progress.');
        }

        if (in_array($phase->phase_key, [OrderOperations::PHASE_DESIGN], true)) {
            throw new InvalidArgumentException('Use design checkpoints to complete the design phase.');
        }

        if ($phase->phase_key === OrderOperations::PHASE_ASSEMBLY) {
            return $this->completeAssembly($intake, $actor, $request);
        }

        if ($phase->phase_key === OrderOperations::PHASE_DELIVERY) {
            return $this->completeDelivery($intake, $actor, $request);
        }

        $phase->status = OrderOperations::PHASE_COMPLETED;
        $phase->completed_at = now();
        $phase->save();

        $this->notifyLiveAudience(
            $intake,
            'order_progress',
            'Phase completed',
            "{$phase->displayLabel()} completed for {$intake->invoice_number}."
        );

        $this->audit->log($actor, 'order_phase', (int) $phase->id, 'COMPLETE_PRODUCTION_PHASE', [
            'phase_key' => $phase->phase_key,
        ], $request);

        return $phase->fresh();
    }

    public function completeAssembly(OrderIntake $intake, User $actor, ?Request $request = null): OrderPhase
    {
        if (! $actor->canMonitorProductionDelivery() && ! $actor->canSuperviseProductManager()) {
            throw new InvalidArgumentException('Not authorized to complete assembly.');
        }

        $phase = $intake->phases()->where('phase_key', OrderOperations::PHASE_ASSEMBLY)->firstOrFail();
        $phase->status = OrderOperations::PHASE_COMPLETED;
        $phase->completed_at = now();
        $phase->save();

        $delivery = $intake->phases()->where('phase_key', OrderOperations::PHASE_DELIVERY)->first();
        if ($delivery && $delivery->status === OrderOperations::PHASE_PENDING) {
            $delivery->status = OrderOperations::PHASE_IN_PROGRESS;
            $delivery->started_at = now();
            $delivery->save();
        }

        $this->notifyLiveAudience($intake, 'order_progress', 'Assembly completed', "Assembly completed for {$intake->invoice_number}.");
        $this->audit->log($actor, 'order_phase', (int) $phase->id, 'COMPLETE_ASSEMBLY', null, $request);

        return $phase->fresh();
    }

    public function completeDelivery(OrderIntake $intake, User $actor, ?Request $request = null): OrderPhase
    {
        if (! $actor->canMonitorProductionDelivery() && ! $actor->canSuperviseProductManager()) {
            throw new InvalidArgumentException('Not authorized to complete delivery.');
        }

        $materials = $intake->phases()->where('phase_key', OrderOperations::PHASE_MATERIALS)->first();
        $assembly = $intake->phases()->where('phase_key', OrderOperations::PHASE_ASSEMBLY)->first();
        if ($materials?->status !== OrderOperations::PHASE_COMPLETED
            || $assembly?->status !== OrderOperations::PHASE_COMPLETED) {
            throw new InvalidArgumentException('Materials and assembly must be completed before delivery.');
        }

        $phase = $intake->phases()->where('phase_key', OrderOperations::PHASE_DELIVERY)->firstOrFail();
        $phase->status = OrderOperations::PHASE_COMPLETED;
        $phase->completed_at = now();
        $phase->save();

        $this->notifyLiveAudience($intake, 'order_delivered', 'Order delivered', "Delivery completed for {$intake->invoice_number}.");

        $this->audit->log($actor, 'order_phase', (int) $phase->id, 'COMPLETE_DELIVERY', null, $request);

        return $phase->fresh();
    }

    private function maybeCompleteDesignPhase(OrderPhase $phase, User $actor, ?Request $request): void
    {
        $phase->load('checkpoints', 'intake');
        $allDone = $phase->checkpoints->isNotEmpty()
            && $phase->checkpoints->every(fn (OrderCheckpoint $c) => $c->is_completed);

        if (! $allDone) {
            return;
        }

        DB::transaction(function () use ($phase, $actor, $request) {
            $phase->status = OrderOperations::PHASE_COMPLETED;
            $phase->completed_at = now();
            $phase->save();

            $materials = $phase->intake->phases()->where('phase_key', OrderOperations::PHASE_MATERIALS)->first();
            if ($materials && $materials->status === OrderOperations::PHASE_PENDING) {
                $materials->status = OrderOperations::PHASE_IN_PROGRESS;
                $materials->started_at = now();
                $materials->save();
            }

            $this->notifyLiveAudience(
                $phase->intake,
                'design_completed',
                'Design phase completed',
                "Design completed for {$phase->intake->invoice_number}."
            );

            foreach ($this->notify->activeUserIdsWithRoles([OrderOperations::ROLE_COMPANY_MANAGER]) as $userId) {
                $this->notify->notifyUser($userId, [
                    'type' => 'design_completed',
                    'title' => 'Design phase completed',
                    'message' => "Design completed for {$phase->intake->invoice_number}.",
                    'entityType' => 'order_intake',
                    'entityId' => (int) $phase->intake->id,
                ]);
            }

            $this->audit->log($actor, 'order_phase', (int) $phase->id, 'COMPLETE_DESIGN_PHASE', null, $request);
        });
    }

    /**
     * Live progress audience: OMS, OMF, admin, sales_supervisor, and sales who brought the customer.
     */
    private function notifyLiveAudience(OrderIntake $intake, string $type, string $title, string $message): void
    {
        $ids = $this->notify->activeUserIdsWithRoles([
            OrderOperations::ROLE_OMS,
            OrderOperations::ROLE_OMF,
            'admin',
            'sales_supervisor',
        ]);

        if ($intake->source_user_id) {
            $ids[] = (int) $intake->source_user_id;
        }

        foreach (array_unique($ids) as $userId) {
            $this->notify->notifyUser((int) $userId, [
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'entityType' => 'order_intake',
                'entityId' => (int) $intake->id,
            ]);
        }
    }
}
