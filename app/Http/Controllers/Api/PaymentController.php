<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): JsonResponse
    {
        $rows = Payment::query()
            ->join('invoices as i', 'payments.invoice_id', '=', 'i.id')
            ->select(
                'payments.*',
                'i.invoice_number',
                'i.amount as invoice_amount',
                'i.status as invoice_status',
            )
            ->orderByDesc('payments.paid_at')
            ->orderByDesc('payments.id')
            ->get();

        $result = $rows->map(fn ($r) => [
            'id' => (int) $r->id,
            'invoice_id' => (int) $r->invoice_id,
            'amount' => $r->amount,
            'method' => $r->method,
            'paid_at' => $r->paid_at,
            'recorded_by' => $r->recorded_by,
            'created_at' => $r->created_at,
            'invoice' => [
                'id' => (int) $r->invoice_id,
                'invoice_number' => $r->invoice_number,
                'amount' => $r->invoice_amount,
                'status' => $r->invoice_status,
            ],
        ])->values()->all();

        return ApiResponse::success($result);
    }

    public function store(Request $request): JsonResponse
    {
        $invoiceId = $request->input('invoice_id');
        $amount = $request->input('amount');
        $method = $request->input('method', 'bank_transfer');

        if (! $invoiceId || ! $amount) {
            return ApiResponse::error(
                'Invoice ID and payment amount are required',
                'INVALID_INPUT',
                400,
            );
        }

        try {
            $payId = 0;
            $totalPaid = 0.0;

            DB::transaction(function () use ($request, $invoiceId, $amount, $method, &$payId, &$totalPaid) {
                $invoice = Invoice::query()->lockForUpdate()->find($invoiceId);
                if (! $invoice) {
                    throw new \RuntimeException('NOT_FOUND');
                }
                if ($invoice->status === 'cancelled') {
                    throw new \RuntimeException('CANCELLED');
                }

                /** @var User|null $user */
                $user = $request->attributes->get('auth_user') ?? $request->user();

                $payment = Payment::query()->create([
                    'invoice_id' => $invoiceId,
                    'amount' => $amount,
                    'method' => $method,
                    'paid_at' => now(),
                    'recorded_by' => $user?->id,
                ]);
                $payId = (int) $payment->id;

                $totalPaid = (float) Payment::query()
                    ->where('invoice_id', $invoiceId)
                    ->sum('amount');

                if ($totalPaid >= (float) $invoice->amount) {
                    $invoice->status = 'paid';
                    $invoice->save();
                }
            });

            /** @var User|null $user */
            $user = $request->attributes->get('auth_user') ?? $request->user();
            $this->audit->log($user, 'payment', $payId, 'RECORD_PAYMENT', [
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'method' => $method,
            ], $request);

            return ApiResponse::success([
                'id' => $payId,
                'invoice_id' => (int) $invoiceId,
                'amount' => $amount,
                'total_paid' => $totalPaid,
            ], null, 201);
        } catch (\RuntimeException $e) {
            return match ($e->getMessage()) {
                'NOT_FOUND' => ApiResponse::error('Invoice not found', 'NOT_FOUND', 404),
                'CANCELLED' => ApiResponse::error(
                    'Cannot record payment on a cancelled invoice',
                    'FORBIDDEN',
                    403,
                ),
                default => throw $e,
            };
        }
    }
}
