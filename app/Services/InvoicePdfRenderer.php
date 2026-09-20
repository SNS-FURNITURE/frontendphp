<?php

namespace App\Services;

/**
 * Port of sns-erp-backend/src/documents/render-pdf.ts (PDFKit Sofya layout).
 * DomPDF HTML is shaped to that geometry — not the Word editor sheet.
 */
class InvoicePdfRenderer
{
    private const PAGE_W = 595.28;

    private const PAGE_H = 841.89;

    private const MARGIN_L = 48.0;

    private const MARGIN_R = 36.0;

    private const COLS = [
        'lineNo' => 26,
        'name' => 200,
        'unit' => 40,
        'qty' => 52,
        'pieces' => 46,
        'unitPrice' => 72,
        'total' => 87,
    ];

    public function __construct(private EtbWordsService $etbWords) {}

    public function render(array $doc): string
    {
        $vm = $this->buildViewModel($doc);
        $html = view('documents.invoice-pdf', [
            'vm' => $vm,
            'page' => [
                'w' => self::PAGE_W,
                'h' => self::PAGE_H,
                'ml' => self::MARGIN_L,
                'mr' => self::MARGIN_R,
                'contentW' => self::PAGE_W - self::MARGIN_L - self::MARGIN_R,
            ],
            'cols' => self::COLS,
            'logoSrc' => $this->logoDataUri(),
        ])->render();

        return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper([0, 0, self::PAGE_W, self::PAGE_H], 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('dpi', 72)
            ->output();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildViewModel(array $input): array
    {
        $type = (string) ($input['doc_type'] ?? 'PROFORMA');
        $profile = $this->profile($type);
        $negate = (bool) $profile['negateAmounts'];

        $discountType = strtoupper((string) ($input['discount']['type'] ?? 'PERCENT'));
        $discountLabel = $discountType === 'PERCENT'
            ? (($input['discount']['value'] ?? '0').'% Discount')
            : 'Discount';

        $lines = [];
        foreach ($input['lines'] ?? [] as $i => $line) {
            $lines[] = [
                'lineNo' => (string) ($line['line_no'] ?? ($i + 1)),
                'name' => (string) ($line['name'] ?? ''),
                'description' => $this->formatLineDescription($line['description'] ?? ''),
                'uom' => (string) ($line['uom_code'] ?? ''),
                'qty' => $this->formatQty($line['quantity'] ?? 0),
                'pieces' => ($line['unit_count'] === null || $line['unit_count'] === '')
                    ? ''
                    : (string) $line['unit_count'],
                'unitPrice' => $this->formatMoney($line['unit_price'] ?? 0, $negate),
                'lineTotal' => $this->formatMoney($line['line_total'] ?? 0, $negate),
            ];
        }

        $amountInWords = null;
        if ($profile['requireAmountInWords']) {
            $amountInWords = $this->etbWords->amountInWords($input['totals']['grand_total'] ?? 0);
        }

        return [
            'title' => $profile['title'],
            'showValidUntil' => $profile['showValidUntil'],
            'showAgainstInvoice' => $profile['showAgainstInvoice'],
            'docNumber' => (string) ($input['doc_number'] ?? ''),
            'docDate' => $this->formatDocDate((string) ($input['doc_date'] ?? '')),
            'validUntil' => ! empty($input['valid_until'])
                ? $this->formatDocDate((string) $input['valid_until'])
                : null,
            'parentDocNumber' => $input['parent_doc_number'] ?? null,
            'supplier' => [
                'name' => (string) ($input['supplier']['name'] ?? 'SNS Furniture Manufacturing'),
                'address_line' => (string) ($input['supplier']['address_line'] ?? ''),
            ],
            'customer' => [
                'name' => (string) ($input['customer']['name'] ?? ''),
                'address_line' => (string) ($input['customer']['address_line'] ?? ''),
            ],
            'lines' => $lines,
            'notes' => array_values(array_filter(
                array_map('strval', $input['notes'] ?? []),
                fn ($n) => trim($n) !== '',
            )),
            'terms' => [
                'payment' => (string) ($input['terms']['payment'] ?? ''),
                'delivery_place' => (string) ($input['terms']['delivery_place'] ?? ''),
                'delivery_days' => $this->formatTermUnit($input['terms']['delivery_days'] ?? '', 'days'),
                'validity_days' => $this->formatTermUnit($input['terms']['validity_days'] ?? '', 'days'),
                'warranty' => $this->formatTermUnit($input['terms']['warranty'] ?? '', 'years'),
            ],
            'discountLabel' => $discountLabel,
            'taxLabel' => 'VAT '.($input['tax']['rate'] ?? '15').'%',
            'subtotalDisplay' => $this->formatMoney($input['totals']['subtotal'] ?? 0, $negate),
            'discountDisplay' => $this->formatMoney($input['discount']['amount'] ?? 0, true),
            'afterDiscountDisplay' => $this->formatMoney($input['totals']['after_discount'] ?? 0, $negate),
            'taxDisplay' => $this->formatMoney($input['tax']['amount'] ?? 0, $negate),
            'grandTotalDisplay' => $this->formatMoney($input['totals']['grand_total'] ?? 0, $negate),
            'amountInWords' => $amountInWords,
            'preparedBy' => [
                'name' => (string) ($input['prepared_by']['name'] ?? ''),
                'phone' => (string) ($input['prepared_by']['phone'] ?? ''),
            ],
            'approvedBy' => [
                'name' => (string) ($input['approved_by']['name'] ?? ''),
                'phone' => (string) ($input['approved_by']['phone'] ?? ''),
            ],
        ];
    }

    /**
     * @return array{title:string,showValidUntil:bool,showAgainstInvoice:bool,requireAmountInWords:bool,negateAmounts:bool}
     */
    private function profile(string $type): array
    {
        return match ($type) {
            'CREDIT_NOTE' => [
                'title' => 'Credit Note',
                'showValidUntil' => false,
                'showAgainstInvoice' => true,
                'requireAmountInWords' => true,
                'negateAmounts' => true,
            ],
            'TAX_INVOICE' => [
                'title' => 'Proforma Invoice',
                'showValidUntil' => false,
                'showAgainstInvoice' => false,
                'requireAmountInWords' => true,
                'negateAmounts' => false,
            ],
            'SALES_ORDER' => [
                'title' => 'Sales Order',
                'showValidUntil' => false,
                'showAgainstInvoice' => false,
                'requireAmountInWords' => false,
                'negateAmounts' => false,
            ],
            default => [
                'title' => 'Proforma Invoice',
                'showValidUntil' => false,
                'showAgainstInvoice' => false,
                'requireAmountInWords' => false,
                'negateAmounts' => false,
            ],
        };
    }

    private function formatMoney(mixed $value, bool $negate = false): string
    {
        $n = round((float) $value, 2);
        if ($negate) {
            $n = -$n;
        }
        $neg = $n < 0;
        $abs = abs($n);
        $parts = explode('.', number_format($abs, 2, '.', ''));
        $withSep = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $parts[0]) ?? $parts[0];
        $body = $withSep.'.'.($parts[1] ?? '00');

        return $neg ? '('.$body.')' : $body;
    }

    private function formatQty(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function formatDocDate(string $iso): string
    {
        $iso = trim($iso);
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $iso, $m)) {
            return $iso;
        }

        return gmdate('D M j Y', gmmktime(0, 0, 0, (int) $m[2], (int) $m[3], (int) $m[1]));
    }

    private function formatLineDescription(mixed $raw): string
    {
        $trimmed = trim((string) $raw);
        if ($trimmed === '') {
            return '';
        }
        if (! (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}'))) {
            return $trimmed;
        }
        try {
            $parsed = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return $trimmed;
        }
        if (! is_array($parsed)) {
            return $trimmed;
        }
        $parts = [];
        foreach ($parsed as $key => $value) {
            $label = str_replace('_', ' ', (string) $key);
            if ($value === null) {
                $parts[] = $label.': —';
            } elseif (is_array($value)) {
                $parts[] = $label.': '.json_encode($value);
            } else {
                $parts[] = $label.': '.(string) $value;
            }
        }

        return implode(' · ', $parts);
    }

    private function formatTermUnit(mixed $value, string $unit): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }
        if (preg_match('/[a-zA-Z]/', $raw)) {
            return $raw;
        }

        return $raw.' '.$unit;
    }

    private function logoDataUri(): string
    {
        $logoPath = public_path('sns-logo.png');
        if (! is_file($logoPath) || ! extension_loaded('gd')) {
            return '';
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath));
    }
}
