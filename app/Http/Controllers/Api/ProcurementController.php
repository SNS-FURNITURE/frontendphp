<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExternalLaborer;
use App\Models\ProcurementMarketResearch;
use App\Models\SiteInstallationJob;
use App\Models\User;
use App\Services\AuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProcurementController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function researchIndex(Request $request): JsonResponse
    {
        $query = ProcurementMarketResearch::query()
            ->with(['conductedBy', 'approvedBy'])
            ->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        return ApiResponse::success(
            $query->get()->map(fn (ProcurementMarketResearch $r) => $r->toApiArray())->values()->all()
        );
    }

    public function researchStore(Request $request): JsonResponse
    {
        $itemName = $request->input('item_name');
        $quantity = $request->input('quantity');

        if (! $itemName || $quantity === null || $quantity === '') {
            return ApiResponse::error('Item name and quantity are required', 'INVALID_INPUT', 400);
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        $unitPrice = $request->input('selected_unit_price');
        $total = $request->input('selected_total_price');
        if ($total === null && $unitPrice !== null) {
            $total = (float) $unitPrice * (float) $quantity;
        }

        $options = $request->input('supplier_options_json');
        if (is_array($options)) {
            $options = $options;
        }

        $row = ProcurementMarketResearch::query()->create([
            'requisition_id' => $request->input('requisition_id'),
            'item_name' => $itemName,
            'category' => $request->input('category'),
            'specifications' => $request->input('specifications'),
            'quantity' => $quantity,
            'unit_of_measure' => $request->input('unit_of_measure') ?: 'pcs',
            'supplier_options_json' => $options,
            'selected_supplier_name' => $request->input('selected_supplier_name'),
            'selected_unit_price' => $unitPrice,
            'selected_total_price' => $total,
            'research_notes' => $request->input('research_notes'),
            'quality_grade' => $request->input('quality_grade'),
            'status' => $request->input('status') ?: 'submitted',
            'conducted_by_user_id' => $user?->id ?: 1,
        ]);

        $this->audit->log($user, 'procurement_market_research', (int) $row->id, 'CREATE_MARKET_RESEARCH', [
            'item_name' => $itemName,
            'selected_supplier_name' => $row->selected_supplier_name,
        ], $request);

        return ApiResponse::success($row->fresh(['conductedBy', 'approvedBy'])->toApiArray(), null, 201);
    }

    public function researchUpdate(Request $request, int $id): JsonResponse
    {
        $row = ProcurementMarketResearch::query()->find($id);
        if (! $row) {
            return ApiResponse::error('Procurement research entry not found', 'NOT_FOUND', 404);
        }

        $body = $request->all();
        $allowed = [
            'requisition_id', 'item_name', 'category', 'specifications', 'quantity',
            'unit_of_measure', 'selected_supplier_name', 'selected_unit_price',
            'selected_total_price', 'research_notes', 'quality_grade', 'status',
            'approved_by_user_id',
        ];
        $updates = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $body)) {
                $updates[$field] = $body[$field];
            }
        }
        if (array_key_exists('supplier_options_json', $body)) {
            $updates['supplier_options_json'] = $body['supplier_options_json'];
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        if (($body['status'] ?? null) === 'manager_approved' && ! array_key_exists('approved_by_user_id', $body)) {
            $updates['approved_by_user_id'] = $user?->id;
        }

        if ($updates === []) {
            return ApiResponse::error('No fields to update', 'INVALID_INPUT', 400);
        }

        $row->fill($updates);
        $row->save();

        $this->audit->log($user, 'procurement_market_research', $id, 'UPDATE_MARKET_RESEARCH', $body, $request);

        return ApiResponse::success($row->fresh(['conductedBy', 'approvedBy'])->toApiArray());
    }

    public function laborersIndex(Request $request): JsonResponse
    {
        $query = ExternalLaborer::query()->with('createdBy')->orderBy('full_name');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return ApiResponse::success(
            $query->get()->map(fn (ExternalLaborer $l) => $l->toApiArray())->values()->all()
        );
    }

    public function laborersStore(Request $request): JsonResponse
    {
        $fullName = $request->input('full_name');
        $phone = $request->input('phone');

        if (! $fullName || ! $phone) {
            return ApiResponse::error('Full name and phone number are required', 'INVALID_INPUT', 400);
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        $laborer = ExternalLaborer::query()->create([
            'full_name' => $fullName,
            'phone' => $phone,
            'national_id' => $request->input('national_id'),
            'specialty_skills' => $request->input('specialty_skills'),
            'experience_years' => $request->input('experience_years', 0),
            'daily_rate' => $request->input('daily_rate', 0),
            'status' => $request->input('status') ?: 'free',
            'notes' => $request->input('notes'),
            'created_by_user_id' => $user?->id,
        ]);

        $this->audit->log($user, 'external_laborers', (int) $laborer->id, 'CREATE_LABORER', [
            'full_name' => $fullName,
            'phone' => $phone,
        ], $request);

        return ApiResponse::success($laborer->fresh(['createdBy'])->toApiArray(), null, 201);
    }

    public function laborersUpdate(Request $request, int $id): JsonResponse
    {
        $laborer = ExternalLaborer::query()->find($id);
        if (! $laborer) {
            return ApiResponse::error('Laborer not found', 'NOT_FOUND', 404);
        }

        $body = $request->all();
        $allowed = ['full_name', 'phone', 'national_id', 'specialty_skills', 'experience_years', 'daily_rate', 'status', 'notes'];
        $updates = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $body)) {
                $updates[$field] = $body[$field];
            }
        }

        if ($updates === []) {
            return ApiResponse::error('No fields to update', 'INVALID_INPUT', 400);
        }

        $laborer->fill($updates);
        $laborer->save();

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $this->audit->log($user, 'external_laborers', $id, 'UPDATE_LABORER', $body, $request);

        return ApiResponse::success($laborer->fresh(['createdBy'])->toApiArray());
    }

    public function installationsIndex(Request $request): JsonResponse
    {
        $query = SiteInstallationJob::query()->with('laborer')->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($laborerId = $request->query('laborer_id')) {
            $query->where('assigned_laborer_id', $laborerId);
        }

        return ApiResponse::success(
            $query->get()->map(fn (SiteInstallationJob $j) => $j->toApiArray())->values()->all()
        );
    }

    public function installationsStore(Request $request): JsonResponse
    {
        $customerName = $request->input('customer_name');
        $siteAddress = $request->input('site_address');

        if (! $customerName || ! $siteAddress) {
            return ApiResponse::error('Customer name and site address are required', 'INVALID_INPUT', 400);
        }

        $laborerId = $request->input('assigned_laborer_id');
        $status = $request->input('status') ?: 'not_assigned';
        if ($laborerId && $status === 'not_assigned') {
            $status = 'assigned';
        }
        if (! $laborerId) {
            $status = 'not_assigned';
        }

        $total = (float) ($request->input('agreed_total_payment') ?: 0);
        $advance = (float) ($request->input('advance_payment_amount') ?: 0);
        $balance = $request->input('balance_payment_amount');
        if ($balance === null) {
            $balance = $total - $advance;
        }

        $job = SiteInstallationJob::query()->create([
            'order_id' => $request->input('order_id'),
            'delivery_id' => $request->input('delivery_id'),
            'customer_name' => $customerName,
            'customer_phone' => $request->input('customer_phone'),
            'site_address' => $siteAddress,
            'status' => $status,
            'assigned_laborer_id' => $laborerId,
            'scheduled_start_date' => $request->input('scheduled_start_date'),
            'estimated_duration_days' => $request->input('estimated_duration_days', 1),
            'agreed_total_payment' => $total,
            'advance_payment_amount' => $advance,
            'balance_payment_amount' => $balance,
            'funding_request_id' => $request->input('funding_request_id'),
            'setup_notes' => $request->input('setup_notes'),
        ]);

        if ($laborerId) {
            ExternalLaborer::query()->where('id', $laborerId)->update(['status' => 'assigned']);
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $this->audit->log($user, 'site_installation_jobs', (int) $job->id, 'CREATE_INSTALLATION_JOB', [
            'customer_name' => $customerName,
            'site_address' => $siteAddress,
        ], $request);

        return ApiResponse::success($job->fresh(['laborer'])->toApiArray(), null, 201);
    }

    public function installationsUpdate(Request $request, int $id): JsonResponse
    {
        $job = SiteInstallationJob::query()->find($id);
        if (! $job) {
            return ApiResponse::error('Site installation job not found', 'NOT_FOUND', 404);
        }

        $body = $request->all();
        $allowed = [
            'order_id', 'delivery_id', 'customer_name', 'customer_phone', 'site_address',
            'status', 'assigned_laborer_id', 'scheduled_start_date', 'estimated_duration_days',
            'actual_completion_date', 'agreed_total_payment', 'advance_payment_amount',
            'balance_payment_amount', 'funding_request_id', 'setup_notes', 'client_sign_off_notes',
        ];
        $updates = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $body)) {
                $updates[$field] = $body[$field];
            }
        }

        if (($body['status'] ?? null) === 'finished' && empty($body['actual_completion_date']) && ! $job->actual_completion_date) {
            $updates['actual_completion_date'] = Carbon::today()->toDateString();
        }

        if ($updates === []) {
            return ApiResponse::error('No fields to update', 'INVALID_INPUT', 400);
        }

        $oldLaborerId = $job->assigned_laborer_id;
        $job->fill($updates);
        $job->save();

        $currentLaborerId = array_key_exists('assigned_laborer_id', $updates)
            ? $updates['assigned_laborer_id']
            : $oldLaborerId;
        $currentStatus = array_key_exists('status', $updates) ? $updates['status'] : $job->status;

        if ($currentLaborerId) {
            if (in_array($currentStatus, ['finished', 'cancelled'], true)) {
                $active = SiteInstallationJob::query()
                    ->where('assigned_laborer_id', $currentLaborerId)
                    ->where('id', '!=', $id)
                    ->whereIn('status', ['assigned', 'in_progress'])
                    ->count();
                if ($active === 0) {
                    ExternalLaborer::query()->where('id', $currentLaborerId)->update(['status' => 'free']);
                }
            } elseif (in_array($currentStatus, ['assigned', 'in_progress'], true)) {
                ExternalLaborer::query()->where('id', $currentLaborerId)->update(['status' => 'assigned']);
            }
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $this->audit->log($user, 'site_installation_jobs', $id, 'UPDATE_INSTALLATION_JOB', $body, $request);

        return ApiResponse::success($job->fresh(['laborer'])->toApiArray());
    }
}
