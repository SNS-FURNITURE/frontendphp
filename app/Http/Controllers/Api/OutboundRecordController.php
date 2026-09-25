<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OutboundRecord;
use App\Models\User;
use App\Services\AuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class OutboundRecordController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        if (! Schema::hasTable('outbound_records')) {
            return ApiResponse::success([]);
        }

        $query = OutboundRecord::query()
            ->with('counter')
            ->latestFirst('counted_at');

        if ($deliveryId = $request->query('delivery_id')) {
            $query->where('delivery_id', $deliveryId);
        }

        $rows = $query->get()
            ->map(fn (OutboundRecord $r) => $r->toApiArray())
            ->values()
            ->all();

        return ApiResponse::success($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $deliveryId = $request->input('delivery_id');
        $productName = $request->input('product_name');
        $quantityOut = $request->input('quantity_out');
        $destination = $request->input('destination');
        $recipientName = $request->input('recipient_name');
        $reference = $request->input('reference');

        if (! $deliveryId || ! $productName || ! $quantityOut || ! $destination || ! $recipientName) {
            return ApiResponse::error(
                'delivery_id, product_name, quantity_out, destination, and recipient_name are required',
                'INVALID_INPUT',
                400,
            );
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        $record = OutboundRecord::query()->create([
            'delivery_id' => $deliveryId,
            'product_name' => $productName,
            'quantity_out' => $quantityOut,
            'destination' => $destination,
            'recipient_name' => $recipientName,
            'counted_by' => $user?->id,
            'counted_at' => now(),
            'reference' => $reference,
        ]);

        $this->audit->log($user, 'outbound_record', (int) $record->id, 'CREATE_OUTBOUND_RECORD', [
            'delivery_id' => $deliveryId,
            'product_name' => $productName,
        ], $request);

        $record->load('counter');

        return ApiResponse::success($record->toApiArray(), null, 201);
    }
}
