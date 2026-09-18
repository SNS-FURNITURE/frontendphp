<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentWebController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): View
    {
        $this->authorize('viewAny', Invoice::class);

        $payments = Payment::query()
            ->with('invoice')
            ->orderByDesc('paid_at')
            ->paginate(25);

        $invoices = Invoice::query()
            ->whereNotIn('status', ['cancelled', 'paid'])
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('payments.index', compact('payments', 'invoices'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('recordPayment', Invoice::class);

        $validated = $request->validate([
            'invoice_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            DB::transaction(function () use ($request, $validated) {
                $invoice = Invoice::query()->lockForUpdate()->find($validated['invoice_id']);
                if (! $invoice) {
                    throw new \RuntimeException('Invoice not found');
                }
                if ($invoice->status === 'cancelled') {
                    throw new \RuntimeException('Cannot record payment on a cancelled invoice');
                }

                $payment = Payment::query()->create([
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'method' => $validated['method'] ?? 'bank_transfer',
                    'paid_at' => now(),
                    'recorded_by' => auth()->id(),
                ]);

                $totalPaid = (float) Payment::query()->where('invoice_id', $invoice->id)->sum('amount');
                if ($totalPaid >= (float) $invoice->amount) {
                    $invoice->status = 'paid';
                    $invoice->save();
                }

                $this->audit->log(auth()->user(), 'payment', (int) $payment->id, 'RECORD_PAYMENT', [
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                ], $request);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['invoice_id' => $e->getMessage()])->withInput();
        }

        return redirect()->route('payments.index')->with('status', 'Payment recorded.');
    }
}
