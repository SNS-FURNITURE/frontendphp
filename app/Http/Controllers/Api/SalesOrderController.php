<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\SalesQuota;
use App\Models\User;
use App\Services\AuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    private const STATUSES = [
        'draft',
        'quoted',
        'confirmed',
        'in_production',
        'delivered',
        'cancelled',
    ];

    public function __construct(private AuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = SalesOrder::query()->with('customer');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $rows = $query->latestFirst()->get();

        $data = $rows->map(fn (SalesOrder $so) => $this->serializeListItem($so))->values()->all();

        return ApiResponse::success($data);
    }

    public function show(int $id): JsonResponse
    {
        $order = SalesOrder::query()->with(['customer', 'lines.item'])->find($id);

        if (! $order) {
            return ApiResponse::error('Sales order not found', 'NOT_FOUND', 404);
        }

        return ApiResponse::success($this->serializeDetail($order));
    }

    public function store(Request $request): JsonResponse
    {
        $customerId = $request->input('customer_id');
        if (! $customerId) {
            return ApiResponse::error('Customer ID is required', 'INVALID_INPUT', 400);
        }

        $customer = Party::query()->find($customerId);
        if (! $customer) {
            return ApiResponse::error('Customer not found', 'NOT_FOUND', 404);
        }

        if ($customer->approval_status !== 'approved') {
            return ApiResponse::error(
                'Customer must be approved by a sales supervisor first',
                'CUSTOMER_NOT_APPROVED',
                400,
            );
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        $order = SalesOrder::query()->create([
            'customer_id' => $customer->id,
            'sales_rep_id' => $user?->id,
            'status' => 'draft',
            'total_amount' => 0,
        ]);

        $this->audit->log($user, 'sales_order', (int) $order->id, 'CREATE_SALES_ORDER', [
            'customer_id' => (int) $customer->id,
        ], $request);

        return ApiResponse::success([
            'id' => (int) $order->id,
            'customer_id' => (int) $customer->id,
            'status' => 'draft',
            'total_amount' => 0,
        ], null, 201);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $status = $request->input('status');
        if (! in_array($status, self::STATUSES, true)) {
            return ApiResponse::error('Invalid sales order status', 'INVALID_INPUT', 400);
        }

        $order = SalesOrder::query()->find($id);
        if (! $order) {
            return ApiResponse::error('Sales order not found', 'NOT_FOUND', 404);
        }

        $order->status = $status;
        $order->save();

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $this->audit->log($user, 'sales_order', (int) $order->id, 'UPDATE_ORDER_STATUS', [
            'new_status' => $status,
        ], $request);

        return ApiResponse::success([
            'id' => (int) $order->id,
            'status' => $order->status,
        ]);
    }

    public function addLine(Request $request, int $id): JsonResponse
    {
        $itemId = $request->input('item_id');
        $quantity = $request->input('quantity');
        $unitPrice = $request->input('unit_price');

        if (! $itemId || ! $quantity || $unitPrice === null || $unitPrice === '') {
            return ApiResponse::error(
                'Item, quantity, and unit price are required',
                'INVALID_INPUT',
                400,
            );
        }

        $order = SalesOrder::query()->find($id);
        if (! $order) {
            return ApiResponse::error('Sales order not found', 'NOT_FOUND', 404);
        }

        $customSpecs = $request->input('custom_specs');

        $grandTotal = 0.0;

        DB::transaction(function () use ($order, $itemId, $quantity, $unitPrice, $customSpecs, &$grandTotal) {
            SalesOrderLine::query()->create([
                'sales_order_id' => $order->id,
                'item_id' => $itemId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'custom_specs' => $customSpecs,
            ]);

            $grandTotal = (float) DB::table('sales_order_lines')
                ->where('sales_order_id', $order->id)
                ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as grand_total')
                ->value('grand_total');

            DB::table('sales_orders')
                ->where('id', $order->id)
                ->update(['total_amount' => $grandTotal]);
        });

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $this->audit->log($user, 'sales_order', (int) $order->id, 'ADD_ORDER_LINE', [
            'item_id' => $itemId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
        ], $request);

        return ApiResponse::success([
            'sales_order_id' => (int) $order->id,
            'total_amount' => $grandTotal,
        ], null, 201);
    }

    public function quota(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        $row = SalesQuota::query()
            ->where('user_id', $user?->id)
            ->orderByDesc('id')
            ->first();

        if (! $row) {
            return ApiResponse::success([
                'quota' => 100000,
                'actual' => 0,
                'period' => '2026-Q3',
            ]);
        }

        return ApiResponse::success([
            'quota' => (float) $row->quota,
            'actual' => (float) $row->actual,
            'period' => $row->period,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeListItem(SalesOrder $so): array
    {
        $customer = $so->customer;

        return [
            'id' => (int) $so->id,
            'customer_id' => $so->customer_id,
            'sales_rep_id' => $so->sales_rep_id,
            'status' => $so->status,
            'total_amount' => $so->total_amount,
            'created_at' => $so->created_at,
            'updated_at' => $so->updated_at,
            'customer' => [
                'id' => $customer?->id,
                'name' => $customer?->name,
                'email' => $customer?->email,
                'phone' => $customer?->phone,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDetail(SalesOrder $order): array
    {
        $customer = $order->customer;
        $lines = $order->lines->map(function (SalesOrderLine $line) {
            $item = $line->item;

            return [
                'id' => (int) $line->id,
                'sales_order_id' => (int) $line->sales_order_id,
                'item_id' => $line->item_id,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'custom_specs' => $line->custom_specs,
                'item' => [
                    'id' => $item?->id ?? $line->item_id,
                    'sku' => $item?->sku,
                    'name' => $item?->name,
                    'unit_of_measure' => $item?->unit_of_measure,
                ],
            ];
        })->values()->all();

        return [
            'id' => (int) $order->id,
            'customer_id' => $order->customer_id,
            'sales_rep_id' => $order->sales_rep_id,
            'status' => $order->status,
            'total_amount' => $order->total_amount,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
            'customer' => [
                'id' => $customer?->id,
                'name' => $customer?->name,
                'email' => $customer?->email,
                'phone' => $customer?->phone,
            ],
            'lines' => $lines,
        ];
    }
}
