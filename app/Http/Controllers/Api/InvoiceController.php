<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\AuditService;
use App\Services\InvoiceNumberService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceNumberService $numbers,
        private AuditService $audit,
    ) {}

    public function nextNumber(): JsonResponse
    {
        return ApiResponse::success([
            'invoice_number' => $this->numbers->allocateUnique(),
        ]);
    }

    public function index(): JsonResponse
    {
        $rows = Invoice::query()
            ->with('salesOrder')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $result = $rows->map(function (Invoice $r) {
            $document = $r->snapshot_json;

            return [
                'id' => (int) $r->id,
                'sales_order_id' => $r->sales_order_id,
                'invoice_number' => $r->invoice_number,
                'amount' => $r->amount,
                'status' => $r->status,
                'issued_at' => $r->issued_at,
                'due_date' => $r->due_date,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at ?? $r->created_at,
                'has_snapshot' => (bool) $r->snapshot_json,
                'customer_name' => is_array($document) ? ($document['customer']['name'] ?? null) : null,
                'doc_type' => is_array($document) ? ($document['doc_type'] ?? null) : null,
                'sales_order' => $r->sales_order_id ? [
                    'id' => (int) $r->sales_order_id,
                    'total_amount' => $r->salesOrder?->total_amount,
                ] : null,
            ];
        })->values()->all();

        return ApiResponse::success($result);
    }

    public function show(int $id): JsonResponse
    {
        $r = Invoice::query()->with('salesOrder')->find($id);

        if (! $r) {
            return ApiResponse::error('Invoice not found', 'NOT_FOUND', 404);
        }

        $document = $r->snapshot_json;

        return ApiResponse::success([
            'id' => (int) $r->id,
            'sales_order_id' => $r->sales_order_id,
            'invoice_number' => $r->invoice_number,
            'amount' => $r->amount,
            'status' => $r->status,
            'issued_at' => $r->issued_at,
            'due_date' => $r->due_date,
            'created_at' => $r->created_at,
            'updated_at' => $r->updated_at ?? $r->created_at,
            'document' => $document,
            'has_snapshot' => (bool) $r->snapshot_json,
            'customer_name' => is_array($document) ? ($document['customer']['name'] ?? null) : null,
            'doc_type' => is_array($document) ? ($document['doc_type'] ?? null) : null,
            'sales_order' => $r->sales_order_id ? [
                'id' => (int) $r->sales_order_id,
                'total_amount' => $r->salesOrder?->total_amount,
            ] : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $amount = $request->input('amount');
        $salesOrderId = $request->input('sales_order_id');
        $dueDate = $request->input('due_date');
        $document = $request->input('document');
        $status = $request->input('status');

        if ($amount === null || $amount === '') {
            return ApiResponse::error('Amount is required', 'INVALID_INPUT', 400);
        }

        $soId = ($salesOrderId === null || $salesOrderId === '')
            ? null
            : (int) $salesOrderId;

        if ($soId !== null && $soId <= 0) {
            return ApiResponse::error('Invalid sales order ID', 'INVALID_INPUT', 400);
        }

        if ($soId === null && ! (is_array($document))) {
            return ApiResponse::error(
                'Document snapshot is required when creating without a sales order',
                'INVALID_INPUT',
                400,
            );
        }

        if ($soId !== null && ! SalesOrder::query()->where('id', $soId)->exists()) {
            return ApiResponse::error('Sales order not found', 'NOT_FOUND', 404);
        }

        $invoiceStatus = $status === 'issued' ? 'issued' : 'draft';
        $generatedNumber = '';
        $invId = 0;

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $generatedNumber = $this->numbers->allocateUnique();
            $snapshot = null;
            if (is_array($document)) {
                $synced = $document;
                $synced['doc_number'] = $generatedNumber;
                $snapshot = $synced;
            }

            try {
                $invoice = Invoice::query()->create([
                    'sales_order_id' => $soId,
                    'invoice_number' => $generatedNumber,
                    'amount' => $amount,
                    'status' => $invoiceStatus,
                    'issued_at' => $invoiceStatus === 'issued' ? now() : null,
                    'due_date' => $dueDate ?: null,
                    'snapshot_json' => $snapshot,
                ]);
                $invId = (int) $invoice->id;
                break;
            } catch (\Throwable $e) {
                if ($attempt < 5 && str_contains($e->getMessage(), 'Duplicate')) {
                    continue;
                }
                throw $e;
            }
        }

        if (! $invId) {
            return ApiResponse::error('Could not allocate a unique invoice number', 'CONFLICT', 409);
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $this->audit->log($user, 'invoice', $invId, 'CREATE_INVOICE', [
            'invoice_number' => $generatedNumber,
            'amount' => $amount,
            'sales_order_id' => $soId,
            'has_snapshot' => true,
        ], $request);

        return ApiResponse::success([
            'id' => $invId,
            'invoice_number' => $generatedNumber,
            'amount' => $amount,
            'status' => $invoiceStatus,
            'sales_order_id' => $soId,
        ], null, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::query()->find($id);
        if (! $invoice) {
            return ApiResponse::error('Invoice not found', 'NOT_FOUND', 404);
        }

        if (in_array($invoice->status, ['paid', 'cancelled'], true)) {
            return ApiResponse::error('Paid or cancelled invoices cannot be edited', 'FORBIDDEN', 403);
        }

        $document = $request->input('document');
        $amount = $request->input('amount');
        $dueDate = $request->input('due_date');
        $status = $request->input('status');

        if ($status === 'paid') {
            return ApiResponse::error(
                'Mark invoices paid by recording a payment, not by status change',
                'INVALID_INPUT',
                400,
            );
        }

        $nextNumber = $invoice->invoice_number;
        $nextAmount = ($amount !== null && $amount !== '') ? $amount : $invoice->amount;
        $nextDue = $request->exists('due_date') ? $dueDate : $invoice->due_date;
        $nextStatus = ($status && in_array($status, ['draft', 'issued', 'overdue', 'cancelled'], true))
            ? $status
            : $invoice->status;

        $nextSnapshot = $invoice->snapshot_json;
        if (is_array($document)) {
            $nextSnapshot = array_merge($document, ['doc_number' => $nextNumber]);
        }

        $invoice->invoice_number = $nextNumber;
        $invoice->amount = $nextAmount;
        $invoice->due_date = $nextDue;
        $invoice->status = $nextStatus;
        $invoice->snapshot_json = $nextSnapshot;
        if ($nextStatus === 'issued' && ! $invoice->issued_at) {
            $invoice->issued_at = now();
        }
        $invoice->save();

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $this->audit->log($user, 'invoice', $id, 'UPDATE_INVOICE', [
            'invoice_number' => $nextNumber,
            'has_snapshot' => (bool) $nextSnapshot,
        ], $request);

        return ApiResponse::success([
            'id' => $id,
            'invoice_number' => $nextNumber,
            'amount' => $nextAmount,
            'status' => $nextStatus,
            'due_date' => $nextDue,
            'document' => $nextSnapshot,
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $status = $request->input('status');

        if ($status === 'paid') {
            return ApiResponse::error(
                'Mark invoices paid by recording a payment, not by status change',
                'INVALID_INPUT',
                400,
            );
        }

        $valid = ['draft', 'issued', 'overdue', 'cancelled'];
        if (! in_array($status, $valid, true)) {
            return ApiResponse::error('Invalid invoice status', 'INVALID_INPUT', 400);
        }

        $invoice = Invoice::query()->find($id);
        if (! $invoice) {
            return ApiResponse::error('Invoice not found', 'NOT_FOUND', 404);
        }

        if (in_array($invoice->status, ['paid', 'cancelled'], true) && $status !== $invoice->status) {
            return ApiResponse::error(
                'Paid or cancelled invoices cannot change status this way',
                'FORBIDDEN',
                403,
            );
        }

        $invoice->status = $status;
        if ($status === 'issued' && ! $invoice->issued_at) {
            $invoice->issued_at = now();
        }
        $invoice->save();

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $this->audit->log($user, 'invoice', $id, 'UPDATE_INVOICE_STATUS', ['status' => $status], $request);

        return ApiResponse::success(['id' => $id, 'status' => $status]);
    }
}
