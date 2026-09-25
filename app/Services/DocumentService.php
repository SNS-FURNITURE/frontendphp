<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Party;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use App\Support\UnitOfMeasure;

class DocumentService
{
    public const SNS_SUPPLIER = [
        'name' => 'SNS Furniture Manufacturing',
        'address_line' => 'Addis Ababa, Ak/k, 05, 1110',
        'tin' => '',
        'vat_reg_no' => '',
        'phone' => '',
    ];

    public function __construct(
        private EtbWordsService $etbWords,
        private CustomerIdentityService $customerIdentity,
    ) {}

    public function assemble(array $params): array
    {
        $discount = $params['discount'] ?? ['type' => 'PERCENT', 'value' => '0'];
        $taxRate = (string) ($params['tax']['rate'] ?? $params['tax_rate'] ?? '15');
        $taxCode = (string) ($params['tax']['code'] ?? $params['tax_code'] ?? 'VAT');
        $linesIn = $params['lines'] ?? [];

        $computed = $this->computeTotals($linesIn, $discount, $taxRate);
        $lines = [];
        foreach ($linesIn as $i => $line) {
            $lines[] = array_merge($line, [
                'line_no' => (int) ($line['line_no'] ?? ($i + 1)),
                'line_total' => number_format($computed['lineTotals'][$i], 2, '.', ''),
                'quantity' => (string) ($line['quantity'] ?? '0'),
                'unit_price' => (string) ($line['unit_price'] ?? '0'),
                'description' => (string) ($line['description'] ?? ''),
                'uom_code' => UnitOfMeasure::normalize($line['uom_code'] ?? null),
                'unit_count' => $line['unit_count'] ?? null,
            ]);
        }

        $supplier = $params['supplier'] ?? self::SNS_SUPPLIER;
        $customer = $params['customer'] ?? ['name' => '', 'address_line' => ''];

        $docType = $params['doc_type'] ?? 'PROFORMA';
        if ($docType === 'TAX_INVOICE') {
            $docType = 'PROFORMA';
        }

        $notes = $params['notes'] ?? [];
        if (is_string($notes)) {
            $notes = preg_split("/\r\n|\n|\r/", $notes) ?: [];
        }

        return [
            'doc_type' => $docType,
            'doc_number' => $params['doc_number'] ?? '',
            'doc_date' => $params['doc_date'] ?? date('Y-m-d'),
            'valid_until' => $params['valid_until'] ?? null,
            'parent_doc_number' => $params['parent_doc_number'] ?? null,
            'currency' => 'ETB',
            'supplier' => array_merge(self::SNS_SUPPLIER, is_array($supplier) ? $supplier : [], [
                'address_line' => $this->filledAddress(
                    is_array($supplier) ? ($supplier['address_line'] ?? null) : null,
                    self::SNS_SUPPLIER['address_line'],
                ),
            ]),
            'customer' => $this->normalizeCustomer(is_array($customer) ? $customer : []),
            'lines' => $lines,
            'discount' => [
                'type' => strtoupper((string) ($discount['type'] ?? 'PERCENT')),
                'value' => (string) ($discount['value'] ?? '0'),
                'amount' => number_format($computed['discountAmount'], 2, '.', ''),
            ],
            'tax' => [
                'code' => $taxCode,
                'rate' => $taxRate,
                'amount' => number_format($computed['taxAmount'], 2, '.', ''),
            ],
            'totals' => [
                'subtotal' => number_format($computed['subtotal'], 2, '.', ''),
                'after_discount' => number_format($computed['afterDiscount'], 2, '.', ''),
                'grand_total' => number_format($computed['grandTotal'], 2, '.', ''),
            ],
            'notes' => array_values(array_filter(array_map('strval', $notes), fn ($n) => trim($n) !== '')),
            'terms' => array_merge([
                'payment' => '50% up on Order / 50% up on dispatch',
                'delivery_place' => 'A.A',
                'delivery_days' => '35',
                'validity_days' => '5',
                'warranty' => '1',
            ], $params['terms'] ?? []),
            'prepared_by' => $params['prepared_by'] ?? ['name' => '', 'phone' => ''],
            'approved_by' => $params['approved_by'] ?? ['name' => '', 'phone' => ''],
            'amount_in_words' => $this->etbWords->amountInWords($computed['grandTotal']),
        ];
    }

    public function blankDraft(?User $user = null, string $docNumber = ''): array
    {
        $doc = $this->assemble([
            'doc_type' => 'PROFORMA',
            'doc_number' => $docNumber,
            'doc_date' => date('Y-m-d'),
            'valid_until' => date('Y-m-d'),
            'customer' => [
                'name' => '',
                'address_line' => '',
            ],
            'lines' => [[
                'line_no' => 1,
                'name' => '',
                'description' => '',
                'uom_code' => UnitOfMeasure::DEFAULT,
                'quantity' => '1.000',
                'unit_count' => null,
                'unit_price' => '0.00',
            ]],
            'discount' => ['type' => 'PERCENT', 'value' => '0'],
            'tax_rate' => '15',
            'notes' => [],
            'prepared_by' => ['name' => '', 'phone' => ''],
            'approved_by' => [
                'name' => '',
                'phone' => '',
            ],
        ]);
        // Match Next blankInvoiceDraft: empty customer address until compose.
        $doc['customer']['address_line'] = '';

        return $doc;
    }

    public function snapshotForInvoice(Invoice $invoice, ?User $user = null): array
    {
        $snapshot = $invoice->snapshot_json;
        if (is_array($snapshot) && $snapshot !== []) {
            $snapshot['doc_number'] = $invoice->invoice_number;
            if (empty($snapshot['amount_in_words']) && isset($snapshot['totals']['grand_total'])) {
                $snapshot['amount_in_words'] = $this->etbWords->amountInWords($snapshot['totals']['grand_total']);
            }
            $snapshot['customer'] = $this->resolveCustomerFromParty($snapshot['customer'] ?? []);

            return $snapshot;
        }

        if (! $invoice->sales_order_id) {
            throw new \RuntimeException('Invoice has no saved document. Open Edit and Save first.');
        }

        $order = SalesOrder::query()
            ->with(['customer', 'lines.item'])
            ->find($invoice->sales_order_id);

        if (! $order) {
            throw new \RuntimeException('Sales order not found for invoice');
        }

        if ($order->lines->isEmpty()) {
            throw new \RuntimeException('Invoice sales order has no lines');
        }

        $issued = $invoice->issued_at?->format('Y-m-d')
            ?? $invoice->created_at?->format('Y-m-d')
            ?? date('Y-m-d');

        $lines = [];
        foreach ($order->lines as $idx => $line) {
            $specs = $line->custom_specs;
            $description = is_string($specs)
                ? $specs
                : ($specs ? json_encode($specs) : '');

            $lines[] = [
                'line_no' => $idx + 1,
                'name' => $line->item?->name ?: ('Item '.$line->item_id),
                'description' => $description,
                'uom_code' => UnitOfMeasure::normalize($line->item?->unit_of_measure),
                'quantity' => number_format((float) $line->quantity, 3, '.', ''),
                'unit_count' => null,
                'unit_price' => number_format((float) $line->unit_price, 2, '.', ''),
            ];
        }

        return $this->assemble([
            'doc_type' => 'PROFORMA',
            'doc_number' => $invoice->invoice_number,
            'doc_date' => $issued,
            'customer' => [
                'name' => $order->customer?->name ?: 'Customer',
                'address_line' => $order->customer?->address ?: 'Addis Ababa, Ethiopia',
            ],
            'lines' => $lines,
            'discount' => ['type' => 'PERCENT', 'value' => '0'],
            'tax_rate' => '15',
            'supplier' => self::SNS_SUPPLIER,
            'prepared_by' => ['name' => '', 'phone' => ''],
            'approved_by' => ['name' => '', 'phone' => ''],
        ]);
    }

    public function validateDocumentRules(array $doc): ?string
    {
        $type = $doc['doc_type'] ?? 'PROFORMA';
        if ($type === 'CREDIT_NOTE' && empty(trim((string) ($doc['parent_doc_number'] ?? '')))) {
            return 'Parent invoice number is required for credit notes';
        }
        if (empty(trim((string) ($doc['customer']['name'] ?? '')))) {
            return 'Customer name is required';
        }
        $lines = $doc['lines'] ?? [];
        $hasLine = false;
        foreach ($lines as $line) {
            if (! empty(trim((string) ($line['name'] ?? '')))) {
                $hasLine = true;
                break;
            }
        }
        if (! $hasLine) {
            return 'Add at least one line item';
        }
        if (empty(trim((string) ($doc['prepared_by']['name'] ?? '')))) {
            return 'Prepared By is required';
        }
        if (empty(trim((string) ($doc['prepared_by']['phone'] ?? '')))) {
            return 'Phone Number is required';
        }

        return null;
    }

    public function applyInvoicePriceAccess(array $document, User $user, ?array $existingSnapshot = null): array
    {
        if ($user->canEditInvoicePrices()) {
            return $document;
        }

        $existingLines = is_array($existingSnapshot['lines'] ?? null) ? $existingSnapshot['lines'] : [];
        $productPrices = Product::query()->pluck('unit_price', 'id');

        foreach ($document['lines'] ?? [] as &$line) {
            if (! is_array($line)) {
                continue;
            }

            $price = null;
            $productId = $line['product_id'] ?? null;

            if ($productId !== null && isset($productPrices[$productId])) {
                $price = $productPrices[$productId];
            } else {
                foreach ($existingLines as $existingLine) {
                    if (! is_array($existingLine)) {
                        continue;
                    }

                    $matchesProduct = $productId !== null
                        && ($existingLine['product_id'] ?? null) == $productId;
                    $matchesName = ($line['name'] ?? '') !== ''
                        && ($line['name'] ?? '') === ($existingLine['name'] ?? '');

                    if ($matchesProduct || $matchesName) {
                        $price = $existingLine['unit_price'] ?? null;
                        break;
                    }
                }
            }

            if ($price !== null) {
                $line['unit_price'] = number_format((float) $price, 2, '.', '');
            }
        }
        unset($line);

        if (is_array($existingSnapshot['discount'] ?? null)) {
            $document['discount'] = $existingSnapshot['discount'];
        } else {
            $document['discount'] = ['type' => 'PERCENT', 'value' => '0'];
        }

        return $document;
    }

    public function renderHtml(array $snapshot, bool $forPdf = false, bool $showPrices = true): string
    {
        return view('documents.invoice', [
            'doc' => $snapshot,
            'forPdf' => $forPdf,
            'showPrices' => $showPrices,
        ])->render();
    }

    public function renderPdf(array $snapshot, bool $showPrices = true): string
    {
        $script = base_path('tools/invoice-pdf/render.mjs');
        $nodeModules = base_path('tools/invoice-pdf/node_modules/pdfkit');

        if (is_file($script) && is_dir($nodeModules)) {
            try {
                return $this->renderPdfViaNode($snapshot, $script);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return app(InvoicePdfRenderer::class)->render($snapshot, $showPrices);
    }

    private function renderPdfViaNode(array $snapshot, string $script): string
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $cmd = ['node', $script];
        $process = proc_open($cmd, $descriptors, $pipes, base_path('tools/invoice-pdf'), null, [
            'bypass_shell' => true,
        ]);

        if (! is_resource($process)) {
            throw new \RuntimeException('Failed to start invoice PDF renderer');
        }

        fwrite($pipes[0], json_encode($snapshot, JSON_THROW_ON_ERROR));
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $code = proc_close($process);
        if ($code !== 0 || $stdout === false || $stdout === '') {
            throw new \RuntimeException('Invoice PDF renderer failed: '.$stderr);
        }

        return $stdout;
    }

    public function formatDisplayDate(string $iso): string
    {
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})/', trim($iso), $m)) {
            return $iso;
        }
        $ts = gmmktime(0, 0, 0, (int) $m[2], (int) $m[3], (int) $m[1]);

        return gmdate('D M j Y', $ts);
    }

    /**
     * @return array{lineTotals:float[], subtotal:float, discountAmount:float, afterDiscount:float, taxAmount:float, grandTotal:float}
     */
    public function computeTotals(array $lines, array $discount, string $taxRate): array
    {
        $lineTotals = [];
        foreach ($lines as $line) {
            $lineTotals[] = round(((float) ($line['quantity'] ?? 0)) * ((float) ($line['unit_price'] ?? 0)), 2);
        }
        $subtotal = round(array_sum($lineTotals), 2);
        $type = strtoupper((string) ($discount['type'] ?? 'PERCENT'));
        $value = (float) ($discount['value'] ?? 0);
        $discountAmount = $type === 'AMOUNT'
            ? round($value, 2)
            : round($subtotal * $value / 100, 2);
        $afterDiscount = round($subtotal - $discountAmount, 2);
        $taxAmount = round($afterDiscount * ((float) $taxRate) / 100, 2);
        $grandTotal = round($afterDiscount + $taxAmount, 2);

        return compact('lineTotals', 'subtotal', 'discountAmount', 'afterDiscount', 'taxAmount', 'grandTotal');
    }

    public function resolveCustomerFromParty(array $customer): array
    {
        $customer = $this->normalizeCustomer($customer);
        $id = $customer['id'] ?? null;
        $name = trim((string) ($customer['name'] ?? ''));

        $party = null;
        if ($id) {
            $party = Party::query()->where('party_type', 'customer')->find($id);
        }

        if (! $party && $name !== '') {
            $party = $this->customerIdentity->findCustomer(
                $name,
                trim((string) ($customer['address_line'] ?? '')),
            );
        }

        if (! $party) {
            return $customer;
        }

        return $this->normalizeCustomer([
            'id' => (int) $party->id,
            'name' => (string) $party->name,
            'address_line' => (string) ($party->address ?? ''),
            'phone' => (string) ($party->phone ?? ''),
            'tin' => $customer['tin'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $customer
     * @return array{id: ?int, name: string, address_line: string, phone: string, tin: mixed}
     */
    private function normalizeCustomer(array $customer): array
    {
        $id = $customer['id'] ?? null;

        return [
            'id' => ($id !== null && $id !== '') ? (int) $id : null,
            'name' => trim((string) ($customer['name'] ?? '')),
            'address_line' => trim((string) ($customer['address_line'] ?? '')),
            'phone' => trim((string) ($customer['phone'] ?? '')),
            'tin' => $customer['tin'] ?? null,
        ];
    }

    private function filledAddress(mixed $value, string $fallback = 'Addis Ababa, Ethiopia'): string
    {
        $trimmed = is_string($value) ? trim($value) : '';

        return $trimmed !== '' ? $trimmed : $fallback;
    }
}
