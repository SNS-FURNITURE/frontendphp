<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Services\AuditService;
use App\Services\DocumentService;
use App\Services\InvoiceNumberService;
use Illuminate\Http\JsonResponse;
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

        $user = auth()->user();

        $base = Invoice::query()
            ->when(! $user->isAdmin(), function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('status', '!=', 'draft')
                        ->orWhere('created_by', $user->id);
                });
            });

        $drafts = (clone $base)
            ->where('status', 'draft')
            ->when(! $user->isAdmin(), fn ($q) => $q->where('created_by', $user->id))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        $invoices = (clone $base)
            ->where('status', '!=', 'draft')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25);

        return view('invoices.index', compact('invoices', 'drafts'));
    }

    public function create(): View
    {
        $this->authorize('create', Invoice::class);

        $document = $this->documents->blankDraft(auth()->user(), '');

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
            return back()->withErrors([
                'document' => 'Could not read invoice details. Please fill the form and click Save again.',
            ])->withInput();
        }

        // Force draft-friendly defaults before save. Drafts accept any partial content.
        $document['doc_type'] = 'PROFORMA';
        $document['doc_date'] = $this->normalizeDate($document['doc_date'] ?? null) ?? date('Y-m-d');
        $document['valid_until'] = $this->normalizeDate($document['valid_until'] ?? null) ?? date('Y-m-d');
        $document['supplier'] = array_merge(DocumentService::SNS_SUPPLIER, [
            'name' => DocumentService::SNS_SUPPLIER['name'],
            'address_line' => DocumentService::SNS_SUPPLIER['address_line'],
        ]);
        if (! isset($document['customer']) || ! is_array($document['customer'])) {
            $document['customer'] = ['name' => '', 'address_line' => ''];
        }
        $document['customer']['name'] = (string) ($document['customer']['name'] ?? '');
        $document['customer']['address_line'] = (string) ($document['customer']['address_line'] ?? '');
        if (! isset($document['lines']) || ! is_array($document['lines']) || $document['lines'] === []) {
            $document['lines'] = [[
                'line_no' => 1,
                'name' => '',
                'description' => '',
                'uom_code' => 'pcs',
                'quantity' => '1.000',
                'unit_count' => null,
                'unit_price' => '0.00',
            ]];
        }

        $soId = $request->input('sales_order_id') ?: null;
        if ($soId && ! SalesOrder::query()->where('id', $soId)->exists()) {
            return back()->withErrors(['sales_order_id' => 'Sales order not found'])->withInput();
        }

        $assembled = $this->documents->assemble($document);
        $user = auth()->user();
        $assembled['doc_type'] = 'PROFORMA';
        $assembled['doc_date'] = $this->normalizeDate($assembled['doc_date'] ?? null) ?? date('Y-m-d');
        $assembled['valid_until'] = $this->normalizeDate($assembled['valid_until'] ?? null) ?? date('Y-m-d');
        $assembled['supplier'] = array_merge(DocumentService::SNS_SUPPLIER, [
            'name' => DocumentService::SNS_SUPPLIER['name'],
            'address_line' => DocumentService::SNS_SUPPLIER['address_line'],
        ]);
        $assembled['prepared_by'] = [
            'name' => (string) ($user?->full_name ?: ''),
            'phone' => (string) ($user?->phone ?: ''),
        ];
        $assembled['approved_by'] = ['name' => '', 'phone' => ''];
        $assembled['created_by_user_id'] = (int) $user->id;
        $invoiceStatus = $request->input('status') === 'issued' ? 'issued' : 'draft';
        $generatedNumber = '';
        $invoice = null;

        $grandTotal = (float) ($assembled['totals']['grand_total'] ?? 0);

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $generatedNumber = $this->numbers->allocateUnique();
            $assembled['doc_number'] = $generatedNumber;

            try {
                $invoice = Invoice::query()->create([
                    'sales_order_id' => $soId ?: null,
                    'invoice_number' => $generatedNumber,
                    'amount' => $grandTotal,
                    'status' => $invoiceStatus,
                    'issued_at' => $invoiceStatus === 'issued' ? now() : null,
                    'due_date' => $request->input('due_date') ?: null,
                    'snapshot_json' => $assembled,
                    'created_by' => $user->id,
                ]);
                break;
            } catch (\Throwable $e) {
                report($e);
                if ($attempt < 5 && str_contains($e->getMessage(), 'Duplicate')) {
                    continue;
                }

                return back()->withErrors([
                    'document' => 'Could not save invoice: '.$e->getMessage(),
                ])->withInput();
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

        return redirect()
            ->route('invoices.index')
            ->with('status', $invoiceStatus === 'issued'
                ? "Invoice {$generatedNumber} saved"
                : "Invoice {$generatedNumber} saved as draft");
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
            && ! in_array($invoice->status, ['approved', 'paid', 'cancelled'], true);
        $canApprove = auth()->user()->can('approve', $invoice);

        return view('invoices.show', compact('invoice', 'document', 'canEdit', 'canApprove'));
    }

    public function edit(Invoice $invoice): View|RedirectResponse
    {
        $this->authorize('update', $invoice);

        if (in_array($invoice->status, ['approved', 'paid', 'cancelled'], true)) {
            return redirect()->route('invoices.show', $invoice)
                ->withErrors(['status' => 'Approved, paid, or cancelled invoices cannot be edited']);
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

        if (in_array($invoice->status, ['approved', 'paid', 'cancelled'], true)) {
            return back()->withErrors(['status' => 'Approved, paid, or cancelled invoices cannot be edited']);
        }

        $document = $this->parseDocument($request);
        if ($document === null) {
            return back()->withErrors(['document' => 'Could not read invoice details. Please click Save again.']);
        }

        // Draft updates accept any partial content.
        if ($invoice->status === 'draft') {
            $document['doc_type'] = 'PROFORMA';
            $document['doc_date'] = $this->normalizeDate($document['doc_date'] ?? null) ?? date('Y-m-d');
            $document['valid_until'] = $this->normalizeDate($document['valid_until'] ?? null) ?? date('Y-m-d');
            if (! isset($document['customer']) || ! is_array($document['customer'])) {
                $document['customer'] = ['name' => '', 'address_line' => ''];
            }
            $document['customer']['name'] = (string) ($document['customer']['name'] ?? '');
            if (! isset($document['lines']) || ! is_array($document['lines']) || $document['lines'] === []) {
                $document['lines'] = [[
                    'line_no' => 1,
                    'name' => '',
                    'description' => '',
                    'uom_code' => 'pcs',
                    'quantity' => '1.000',
                    'unit_count' => null,
                    'unit_price' => '0.00',
                ]];
            }
        } else {
            $error = $this->documents->validateDocumentRules($document);
            if ($error) {
                return back()->withErrors(['document' => $error]);
            }
        }

        $status = $request->input('status');
        if ($status === 'paid') {
            return back()->withErrors(['status' => 'Mark invoices paid by recording a payment, not by status change']);
        }

        $assembled = $this->documents->assemble($document);
        $assembled['doc_number'] = $invoice->invoice_number;
        $assembled['doc_type'] = 'PROFORMA';
        $assembled['supplier'] = array_merge(DocumentService::SNS_SUPPLIER, [
            'name' => DocumentService::SNS_SUPPLIER['name'],
            'address_line' => DocumentService::SNS_SUPPLIER['address_line'],
        ]);

        $existing = is_array($invoice->snapshot_json) ? $invoice->snapshot_json : [];
        $assembled['doc_date'] = $this->normalizeDate($assembled['doc_date'] ?? null) ?? date('Y-m-d');
        $assembled['valid_until'] = $this->normalizeDate($assembled['valid_until'] ?? null) ?? date('Y-m-d');
        $assembled['prepared_by'] = [
            'name' => (string) ($existing['prepared_by']['name'] ?? auth()->user()?->full_name ?? ''),
            'phone' => (string) ($existing['prepared_by']['phone'] ?? auth()->user()?->phone ?? ''),
        ];
        $assembled['approved_by'] = [
            'name' => (string) ($existing['approved_by']['name'] ?? ''),
            'phone' => (string) ($existing['approved_by']['phone'] ?? ''),
        ];
        if (isset($existing['approval']) && is_array($existing['approval'])) {
            $assembled['approval'] = $existing['approval'];
        }
        $assembled['created_by_user_id'] = (int) ($invoice->created_by ?: auth()->id());

        // Save button issues the invoice into the main list; autosave keeps drafts.
        if ($invoice->status === 'draft' && $status === 'issued') {
            $nextStatus = 'issued';
        } elseif ($invoice->status === 'draft') {
            $nextStatus = 'draft';
        } else {
            $nextStatus = ($status && in_array($status, ['draft', 'issued', 'overdue', 'cancelled'], true))
                ? $status
                : $invoice->status;
        }

        $invoice->amount = $assembled['totals']['grand_total'];
        $invoice->status = $nextStatus;
        $invoice->snapshot_json = $assembled;
        if ($nextStatus === 'issued' && ! $invoice->issued_at) {
            $invoice->issued_at = now();
        }
        $invoice->save();

        $this->audit->log(auth()->user(), 'invoice', (int) $invoice->id, 'UPDATE_INVOICE', [
            'invoice_number' => $invoice->invoice_number,
            'status' => $nextStatus,
        ], $request);

        return redirect()
            ->route('invoices.index')
            ->with('status', $nextStatus === 'draft'
                ? 'Draft invoice updated'
                : ($nextStatus === 'issued' && $invoice->wasChanged('status')
                    ? 'Invoice saved to invoice list'
                    : 'Invoice updated'));
    }

    public function autosave(Request $request, ?Invoice $invoice = null): JsonResponse
    {
        $document = $this->parseDocument($request);
        if ($document === null) {
            return response()->json(['ok' => false, 'message' => 'Could not read invoice details'], 422);
        }

        $document = $this->normalizeDraftDocument($document);
        $user = auth()->user();

        if ($invoice) {
            $this->authorize('update', $invoice);

            if ($invoice->status !== 'draft') {
                return response()->json(['ok' => false, 'message' => 'Only draft invoices can autosave'], 422);
            }

            $assembled = $this->assembleDraftSnapshot($document, $user, $invoice);
            $invoice->amount = $assembled['totals']['grand_total'] ?? 0;
            $invoice->status = 'draft';
            $invoice->snapshot_json = $assembled;
            $invoice->save();

            return response()->json([
                'ok' => true,
                'id' => (int) $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'edit_url' => route('invoices.edit', $invoice),
                'update_url' => route('invoices.update', $invoice),
                'autosave_url' => route('invoices.autosave.existing', $invoice),
            ]);
        }

        $this->authorize('create', Invoice::class);

        $soId = $request->input('sales_order_id') ?: null;
        if ($soId && ! SalesOrder::query()->where('id', $soId)->exists()) {
            return response()->json(['ok' => false, 'message' => 'Sales order not found'], 422);
        }

        $assembled = $this->assembleDraftSnapshot($document, $user);
        $grandTotal = (float) ($assembled['totals']['grand_total'] ?? 0);
        $generatedNumber = '';
        $created = null;

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $generatedNumber = $this->numbers->allocateUnique();
            $assembled['doc_number'] = $generatedNumber;

            try {
                $created = Invoice::query()->create([
                    'sales_order_id' => $soId ?: null,
                    'invoice_number' => $generatedNumber,
                    'amount' => $grandTotal,
                    'status' => 'draft',
                    'issued_at' => null,
                    'due_date' => $request->input('due_date') ?: null,
                    'snapshot_json' => $assembled,
                    'created_by' => $user->id,
                ]);
                break;
            } catch (\Throwable $e) {
                report($e);
                if ($attempt < 5 && str_contains($e->getMessage(), 'Duplicate')) {
                    continue;
                }

                return response()->json(['ok' => false, 'message' => 'Could not save draft'], 500);
            }
        }

        if (! $created) {
            return response()->json(['ok' => false, 'message' => 'Could not allocate a unique invoice number'], 500);
        }

        $this->audit->log($user, 'invoice', (int) $created->id, 'CREATE_INVOICE', [
            'invoice_number' => $generatedNumber,
            'amount' => $assembled['totals']['grand_total'] ?? 0,
            'status' => 'draft',
            'autosave' => true,
        ], $request);

        return response()->json([
            'ok' => true,
            'id' => (int) $created->id,
            'invoice_number' => $generatedNumber,
            'edit_url' => route('invoices.edit', $created),
            'update_url' => route('invoices.update', $created),
            'autosave_url' => route('invoices.autosave.existing', $created),
        ]);
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        $number = $invoice->invoice_number;
        $id = (int) $invoice->id;
        $invoice->delete();

        $this->audit->log(auth()->user(), 'invoice', $id, 'DELETE_INVOICE', [
            'invoice_number' => $number,
        ], $request);

        return redirect()
            ->route('invoices.index')
            ->with('status', "Draft invoice {$number} deleted");
    }

    public function updateStatus(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $status = $request->input('status');
        if ($status === 'paid') {
            return back()->withErrors(['status' => 'Mark invoices paid by recording a payment, not by status change']);
        }
        if ($status === 'approved') {
            return back()->withErrors(['status' => 'Use the Approve action to approve an invoice']);
        }

        $valid = ['draft', 'issued', 'overdue', 'cancelled'];
        if (! in_array($status, $valid, true)) {
            return back()->withErrors(['status' => 'Invalid invoice status']);
        }

        if (in_array($invoice->status, ['approved', 'paid', 'cancelled'], true) && $status !== $invoice->status) {
            return back()->withErrors(['status' => 'Approved, paid, or cancelled invoices cannot change status this way']);
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

    public function approve(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('approve', $invoice);

        if (in_array($invoice->status, ['approved', 'paid', 'cancelled'], true)) {
            return back()->withErrors(['status' => 'This invoice cannot be approved']);
        }

        $user = auth()->user();
        $snapshot = is_array($invoice->snapshot_json) ? $invoice->snapshot_json : [];
        $snapshot['approved_by'] = [
            'name' => (string) ($user->full_name ?: $user->username ?: 'Admin'),
            'phone' => (string) ($user->phone ?: ''),
        ];
        $snapshot['approval'] = [
            'status' => 'approved',
            'approved_at' => now()->toIso8601String(),
            'approved_by_user_id' => (int) $user->id,
            'approved_by_name' => (string) ($user->full_name ?: $user->username ?: 'Admin'),
        ];

        $invoice->status = 'approved';
        $invoice->snapshot_json = $snapshot;
        if (! $invoice->issued_at) {
            $invoice->issued_at = now();
        }
        $invoice->save();

        $this->audit->log($user, 'invoice', (int) $invoice->id, 'APPROVE_INVOICE', [
            'invoice_number' => $invoice->invoice_number,
            'approved_by' => $user->full_name,
        ], $request);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'Invoice approved by '.$user->full_name);
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
        $user = auth()->user();
        $prepName = trim((string) ($payload['prepared_by']['name'] ?? ''));
        $snapshot['prepared_by'] = [
            'name' => $prepName !== '' ? $prepName : (string) ($user?->full_name ?: ''),
            'phone' => trim((string) ($payload['prepared_by']['phone'] ?? $user?->phone ?? '')),
        ];
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
        if (is_array($document)) {
            return $document;
        }

        // JSON body posted by autosave (document at root).
        if ($request->isJson()) {
            $all = $request->json()->all();
            if (isset($all['document']) && is_array($all['document'])) {
                return $all['document'];
            }
            if (isset($all['document_json']) && is_string($all['document_json'])) {
                $decoded = json_decode($all['document_json'], true);

                return is_array($decoded) ? $decoded : null;
            }
        }

        return null;
    }

    private function normalizeDraftDocument(array $document): array
    {
        $document['doc_type'] = 'PROFORMA';
        $document['doc_date'] = $this->normalizeDate($document['doc_date'] ?? null) ?? date('Y-m-d');
        $document['valid_until'] = $this->normalizeDate($document['valid_until'] ?? null) ?? date('Y-m-d');
        $document['supplier'] = array_merge(DocumentService::SNS_SUPPLIER, [
            'name' => DocumentService::SNS_SUPPLIER['name'],
            'address_line' => DocumentService::SNS_SUPPLIER['address_line'],
        ]);
        if (! isset($document['customer']) || ! is_array($document['customer'])) {
            $document['customer'] = ['name' => '', 'address_line' => ''];
        }
        $document['customer']['name'] = (string) ($document['customer']['name'] ?? '');
        $document['customer']['address_line'] = (string) ($document['customer']['address_line'] ?? '');
        if (! isset($document['lines']) || ! is_array($document['lines']) || $document['lines'] === []) {
            $document['lines'] = [[
                'line_no' => 1,
                'name' => '',
                'description' => '',
                'uom_code' => 'pcs',
                'quantity' => '1.000',
                'unit_count' => null,
                'unit_price' => '0.00',
            ]];
        }

        return $document;
    }

    private function assembleDraftSnapshot(array $document, $user, ?Invoice $invoice = null): array
    {
        $assembled = $this->documents->assemble($document);
        $assembled['doc_type'] = 'PROFORMA';
        $assembled['doc_date'] = $this->normalizeDate($assembled['doc_date'] ?? null) ?? date('Y-m-d');
        $assembled['valid_until'] = $this->normalizeDate($assembled['valid_until'] ?? null) ?? date('Y-m-d');
        $assembled['supplier'] = array_merge(DocumentService::SNS_SUPPLIER, [
            'name' => DocumentService::SNS_SUPPLIER['name'],
            'address_line' => DocumentService::SNS_SUPPLIER['address_line'],
        ]);

        if ($invoice) {
            $existing = is_array($invoice->snapshot_json) ? $invoice->snapshot_json : [];
            $assembled['doc_number'] = $invoice->invoice_number;
            $assembled['prepared_by'] = [
                'name' => (string) ($existing['prepared_by']['name'] ?? $user?->full_name ?? ''),
                'phone' => (string) ($existing['prepared_by']['phone'] ?? $user?->phone ?? ''),
            ];
            $assembled['approved_by'] = [
                'name' => (string) ($existing['approved_by']['name'] ?? ''),
                'phone' => (string) ($existing['approved_by']['phone'] ?? ''),
            ];
            if (isset($existing['approval']) && is_array($existing['approval'])) {
                $assembled['approval'] = $existing['approval'];
            }
            $assembled['created_by_user_id'] = (int) ($invoice->created_by ?: $user?->id);
        } else {
            $assembled['prepared_by'] = [
                'name' => (string) ($user?->full_name ?: ''),
                'phone' => (string) ($user?->phone ?: ''),
            ];
            $assembled['approved_by'] = ['name' => '', 'phone' => ''];
            $assembled['created_by_user_id'] = (int) $user->id;
        }

        return $assembled;
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        $ts = strtotime($value);

        return $ts ? date('Y-m-d', $ts) : null;
    }
}
