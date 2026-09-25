<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\OutboundRecord;
use App\Models\User;
use App\Services\AuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeliveryController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        if (! Schema::hasTable('deliveries')) {
            return ApiResponse::success([]);
        }

        $query = Delivery::query()
            ->with(['creator', 'dispatcher'])
            ->latestFirst();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($destination = $request->query('destination')) {
            $query->where('destination', $destination);
        }

        $rawLimit = (float) $request->query('limit');
        $rawPage = (float) $request->query('page');
        $limit = is_finite($rawLimit) && $rawLimit > 0 ? (int) min($rawLimit, 500) : null;
        $page = is_finite($rawPage) && $rawPage > 0 ? (int) floor($rawPage) : 1;

        $meta = null;
        if ($limit !== null) {
            $query->limit($limit)->offset(($page - 1) * $limit);
            $meta = ['page' => $page, 'per_page' => $limit];
        }

        $rows = $query->get()
            ->map(fn (Delivery $d) => $d->toApiArray())
            ->values()
            ->all();

        return ApiResponse::success($rows, $meta);
    }

    public function store(Request $request): JsonResponse
    {
        $productName = $request->input('product_name');
        $quantity = $request->input('quantity');
        $recipientName = $request->input('recipient_name');
        $destination = $request->input('destination', 'customer');
        $notes = $request->input('notes');

        if (! $productName || ! $quantity || ! $recipientName) {
            return ApiResponse::error(
                'product_name, quantity, and recipient_name are required',
                'INVALID_INPUT',
                400,
            );
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        $delivery = Delivery::query()->create([
            'product_name' => $productName,
            'quantity' => $quantity,
            'destination' => $destination,
            'recipient_name' => $recipientName,
            'status' => 'planned',
            'notes' => $notes,
            'created_by' => $user?->id,
        ]);

        $this->audit->log($user, 'delivery', (int) $delivery->id, 'CREATE_DELIVERY', [
            'product_name' => $productName,
            'destination' => $destination,
        ], $request);

        $delivery->load(['creator', 'dispatcher']);

        return ApiResponse::success($delivery->toApiArray(), null, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return $this->patchStatus($request, $id);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        return $this->patchStatus($request, $id);
    }

    private function patchStatus(Request $request, int $id): JsonResponse
    {
        $status = $request->input('status');
        $quantityDispatched = $request->input('quantity_dispatched');
        $notes = $request->input('notes');

        if ($status !== null && ! in_array($status, Delivery::STATUSES, true)) {
            return ApiResponse::error('Invalid delivery status', 'INVALID_INPUT', 400);
        }

        $delivery = Delivery::query()->find($id);
        if (! $delivery) {
            return ApiResponse::error('Delivery not found', 'NOT_FOUND', 404);
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        DB::transaction(function () use ($delivery, $status, $quantityDispatched, $notes, $user, $request) {
            if ($status !== null) {
                $delivery->status = $status;
            }

            if ($request->exists('quantity_dispatched')) {
                $delivery->quantity_dispatched = $quantityDispatched;
            }

            if ($request->exists('notes')) {
                $delivery->notes = $notes;
            }

            if ($status === 'dispatched') {
                $delivery->dispatched_by = $user?->id;
                $delivery->dispatched_at = now();
            }

            $delivery->save();

            if ($status === 'dispatched' && Schema::hasTable('outbound_records')) {
                $qtyOut = $delivery->quantity_dispatched ?: $delivery->quantity;
                OutboundRecord::query()->create([
                    'delivery_id' => $delivery->id,
                    'product_name' => $delivery->product_name,
                    'quantity_out' => $qtyOut,
                    'destination' => $delivery->destination,
                    'recipient_name' => $delivery->recipient_name,
                    'counted_by' => $user?->id,
                    'counted_at' => now(),
                    'reference' => 'OUT-'.substr((string) (int) (microtime(true) * 1000), -6),
                ]);
            }
        });

        $this->audit->log($user, 'delivery', (int) $delivery->id, 'UPDATE_DELIVERY', [
            'status' => $status,
            'quantity_dispatched' => $quantityDispatched,
        ], $request);

        $delivery->load(['creator', 'dispatcher']);

        return ApiResponse::success($delivery->fresh(['creator', 'dispatcher'])->toApiArray());
    }
}
