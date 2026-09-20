<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Services\AuditService;
use App\Services\DocumentService;
use App\Services\InvoiceNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoiceWebController extends Controller
{
    public function __construct(
        private InvoiceNumberService $numbers,
        private DocumentService $documents,
        private AuditService $audit,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25);

        return view('invoices.index', compact('invoices'));
    }

    public function create(): View
    {
        $this->authorize('create', Invoice::class);

        $preview = $this->numbers->allocateUnique();
        $document = $this->documents->blankDraft(auth()->user(), $preview);

        return view('invoices.create', [
            'document' => $document,
            'salesOrderId' => request('order'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $document = $this->parseDocument($request);
        if ($document === null) {
            return back()->withErrors(['document' => 'Document snapshot is required'])->withInput();
        }

        $error = $this->documents->validateDocumentRules($document);
        if ($error) {
            return back()->withErrors(['document' => $error])->withInput();
        }

        $soId = $request->input('sales_order_id') ?: null;
        if ($soId && ! SalesOrder::query()->where('id', $soId)->exists()) {
            return back()->withErrors(['sales_order_id' => 'Sales order not found'])->withInput();
        }

        $assembled = $this->documents->assemble($document);
        $invoiceStatus = $request->input('status') === 'issued' ? 'issued' : 'draft';
        $generatedNumber = '';
        $invoice = null;

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $generatedNumber = $this->numbers->allocateUnique();
            $assembled['doc_number'] = $generatedNumber;

            try {
                $invoice = Invoice::query()->create([
                    'sales_order_id' => $soId ?: null,
                    'invoice_number' => $generatedNumber,
                    'amount' => $assembled['totals']['grand_total'],
                    'status' => $invoiceStatus,
                    'issued_at' => $invoiceStatus === 'issued' ? now() : null,
                    'due_date' => $request->input('due_date') ?: null,
                    'snapshot_json' => $assembled,
                ]);
                break;
            } catch (\Throwable $e) {
                if ($attempt < 5 && str_contains($e->getMessage(), 'Duplicate')) {
                    continue;
                }
                throw $e;
            }
        }

        if (! $invoice) {
            return back()->withErrors(['document' => 'Could not allocate a unique invoice number']);
        }

        $this->audit->log(auth()->user(), 'invoice', (int) $invoice->id, 'CREATE_INVOICE', [
            'invoice_number' => $generatedNumber,
            'amount' => $assembled['totals']['grand_total'],
            'status' => $invoiceStatus,
        ], $request);

        $msg = $invoiceStatus === 'issued'
            ? "Invoice {$generatedNumber} issued"
            : "Invoice {$generatedNumber} saved as draft";

        return redirect()->route('invoices.show', $invoice)->with('status', $msg);
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        try {
            $document = $this->documents->snapshotForInvoice($invoice, auth()->user());
        } catch (\RuntimeException $e) {
            $document = $this->documents->blankDraft(auth()->user(), $invoice->invoice_number);
        }

        $canEdit = auth()->user()->can('update', $invoice)
            && ! in_array($invoice->status, ['paid', 'cancelled'], true);

        return view('invoices.show', compact('invoice', 'document', 'canEdit'));
    }

    public function edit(Invoice $invoice): View|RedirectResponse
    {
        $this->authorize('update', $invoice);

        if (in_array($invoice->status, ['paid', 'cancelled'], true)) {
            return redirect()->route('invoices.show', $invoice)
                ->withErrors(['status' => 'Paid or cancelled invoices cannot be edited']);
        }

        try {
            $document = $this->documents->snapshotForInvoice($invoice, auth()->user());
        } catch (\RuntimeException $e) {
            $document = $this->documents->blankDraft(auth()->user(), $invoice->invoice_number);
        }

        return view('invoices.edit', compact('invoice', 'document'));
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if (in_array($invoice->status, ['paid', 'cancelled'], true)) {
            return back()->withErrors(['status' => 'Paid or cancelled invoices cannot be edited']);
        }

        $document = $this->parseDocument($request);
        if ($document === null) {
            return back()->withErrors(['document' => 'Document snapshot is required']);
        }

        $error = $this->documents->validateDocumentRules($document);
        if ($error) {
            return back()->withErrors(['document' => $error]);
        }

        $status = $request->input('status');
        if ($status === 'paid') {
            return back()->withErrors(['status' => 'Mark invoices paid by recording a payment, not by status change']);
        }

        $assembled = $this->documents->assemble($document);
        $assembled['doc_number'] = $invoice->invoice_number;

        $nextStatus = ($status && in_array($status, ['draft', 'issued', 'overdue', 'cancelled'], true))
            ? $status
            : $invoice->status;

        $invoice->amount = $assembled['totals']['grand_total'];
        $invoice->status = $nextStatus;
        $invoice->snapshot_json = $assembled;
        if ($nextStatus === 'issued' && ! $invoice->issued_at) {
            $invoice->issued_at = now();
        }
        $invoice->save();

        $this->audit->log(auth()->user(), 'invoice', (int) $invoice->id, 'UPDATE_INVOICE', [
            'invoice_number' => $invoice->invoice_number,
        ], $request);

        return redirect()->route('invoices.show', $invoice)->with('status', 'Invoice updated');
    }

    public function updateStatus(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $status = $request->input('status');
        if ($status === 'paid') {
            return back()->withErrors(['status' => 'Mark invoices paid by recording a payment, not by status change']);
        }

        $valid = ['draft', 'issued', 'overdue', 'cancelled'];
        if (! in_array($status, $valid, true)) {
            return back()->withErrors(['status' => 'Invalid invoice status']);
        }

        if (in_array($invoice->status, ['paid', 'cancelled'], true) && $status !== $invoice->status) {
            return back()->withErrors(['status' => 'Paid or cancelled invoices cannot change status this way']);
        }

        $invoice->status = $status;
        if ($status === 'issued' && ! $invoice->issued_at) {
            $invoice->issued_at = now();
        }
        $invoice->save();

        $this->audit->log(auth()->user(), 'invoice', (int) $invoice->id, 'UPDATE_INVOICE_STATUS', [
            'status' => $status,
        ], $request);

        return back()->with('status', 'Invoice status updated');
    }

    public function document(Request $request, Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);
        $format = strtolower((string) $request->query('format', 'pdf'));

        try {
            $snapshot = $this->documents->snapshotForInvoice($invoice, auth()->user());
        } catch (\RuntimeException $e) {
            abort(400, $e->getMessage());
        }

        $filename = preg_replace('/[^\w.-]+/', '_', $invoice->invoice_number) ?: 'invoice';

        if ($format === 'html') {
            return response($this->documents->renderHtml($snapshot), 200, [
                'Content-Type' => 'text/html; charset=utf-8',
            ]);
        }

        return response($this->documents->renderPdf($snapshot), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.pdf"',
        ]);
    }

    public function composePdf(Request $request): Response
    {
        $this->authorize('create', Invoice::class);

        $payload = $request->all();
        if (isset($payload['document_json']) && is_string($payload['document_json'])) {
            $payload = json_decode($payload['document_json'], true) ?: [];
        }

        $snapshot = $this->documents->assemble($payload);
        $error = $this->documents->validateDocumentRules($snapshot);
        if ($error) {
            abort(400, $error);
        }

        $filename = preg_replace('/[^\w.-]+/', '_', (string) ($snapshot['doc_number'] ?: 'invoice')) ?: 'invoice';

        return response($this->documents->renderPdf($snapshot), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.pdf"',
        ]);
    }

    private function parseDocument(Request $request): ?array
    {
        $raw = $request->input('document_json');
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $document = $request->input('document');

        return is_array($document) ? $document : null;
    }
}
