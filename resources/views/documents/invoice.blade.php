<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<title>{{ ($doc['doc_type'] ?? 'PROFORMA') === 'CREDIT_NOTE' ? 'Credit Note' : 'Proforma Invoice' }} {{ $doc['doc_number'] ?? '' }}</title>
@php
    $fmtMoney = function ($value, bool $negate = false): string {
        $n = round((float) $value, 2);
        if ($negate) {
            $n = -$n;
        }
        $neg = $n < 0;
        $abs = abs($n);
        $parts = explode('.', number_format($abs, 2, '.', ''));
        $withSep = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $parts[0]);
        $body = $withSep.'.'.($parts[1] ?? '00');

        return $neg ? '('.$body.')' : $body;
    };
    $fmtQty = function ($value): string {
        return number_format((float) $value, 2, '.', '');
    };
    $fmtDate = function (?string $iso): string {
        $iso = trim((string) $iso);
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $iso, $m)) {
            return $iso;
        }

        return gmdate('D M j Y', gmmktime(0, 0, 0, (int) $m[2], (int) $m[3], (int) $m[1]));
    };
    $title = (($doc['doc_type'] ?? 'PROFORMA') === 'CREDIT_NOTE') ? 'Credit Note' : 'Proforma Invoice';
    $docDate = $fmtDate($doc['doc_date'] ?? '');
    $validUntil = ! empty($doc['valid_until']) ? $fmtDate($doc['valid_until']) : null;
    $discountLabel = (($doc['discount']['type'] ?? 'PERCENT') === 'PERCENT')
        ? (($doc['discount']['value'] ?? '0').'% Discount')
        : 'Discount';
    $taxLabel = 'VAT '.($doc['tax']['rate'] ?? '15').'%';
    $preparedName = trim((string) ($doc['prepared_by']['name'] ?? ''));
    $preparedPhone = trim((string) ($doc['prepared_by']['phone'] ?? ''));
    $approvedName = trim((string) ($doc['approved_by']['name'] ?? ''));
    $approvedPhone = trim((string) ($doc['approved_by']['phone'] ?? ''));
    $showWords = in_array($doc['doc_type'] ?? '', ['TAX_INVOICE', 'CREDIT_NOTE'], true);
    $forPdf = $forPdf ?? false;
    $logoPath = public_path('sns-logo.png');
    $logoSrc = (is_file($logoPath) && extension_loaded('gd'))
        ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath))
        : '';
    $fontRegular = '';
    $fontBold = '';
    $fontEthiopic = '';
    if (! $forPdf) {
        $fontDir = resource_path('documents/fonts');
        if (! is_dir($fontDir)) {
            $fontDir = storage_path('app/documents/fonts');
        }
        $fontRegular = is_file($fontDir.'/NotoSans-Regular.ttf')
            ? 'data:font/ttf;base64,'.base64_encode((string) file_get_contents($fontDir.'/NotoSans-Regular.ttf'))
            : '';
        $fontBold = is_file($fontDir.'/NotoSans-Bold.ttf')
            ? 'data:font/ttf;base64,'.base64_encode((string) file_get_contents($fontDir.'/NotoSans-Bold.ttf'))
            : '';
        $fontEthiopic = is_file($fontDir.'/NotoSansEthiopic-Regular.ttf')
            ? 'data:font/ttf;base64,'.base64_encode((string) file_get_contents($fontDir.'/NotoSansEthiopic-Regular.ttf'))
            : '';
    }
@endphp
<style>
@if (! $forPdf && $fontRegular)
  @font-face { font-family: 'NotoSans'; src: url('{{ $fontRegular }}') format('truetype'); font-weight: 400; }
@endif
@if (! $forPdf && $fontBold)
  @font-face { font-family: 'NotoSans'; src: url('{{ $fontBold }}') format('truetype'); font-weight: 700; }
@endif
@if (! $forPdf && $fontEthiopic)
  @font-face { font-family: 'NotoEthiopic'; src: url('{{ $fontEthiopic }}') format('truetype'); font-weight: 400; }
@endif
  @page { size: A4 portrait; margin: 10mm 10mm 12mm 14mm; }
  * { box-sizing: border-box; }
  body {
    font-family: @if($forPdf) DejaVu Sans, sans-serif @else 'NotoSans', 'NotoEthiopic', DejaVu Sans, sans-serif @endif;
    font-size: 10pt;
    color: #000;
    margin: 0;
    padding: 0;
    position: relative;
  }
  .sheet { position: relative; z-index: 1; }
  .header { width: 100%; border-collapse: collapse; margin: 0; padding: 0; }
  .header td { vertical-align: top; }
  .logo { height: 110px; width: auto; display: block; margin: 0; }
  .title { font-size: 22pt; font-weight: 700; text-align: right; margin: 0; padding-top: 18px; color: #000; }
  .supplier { margin-top: 8px; font-size: 9pt; line-height: 1.35; color: #000; }
  .supplier .name { font-weight: 700; font-size: 11pt; color: #000; }
  .meta { border-collapse: collapse; font-size: 9pt; margin-top: 4px; margin-left: auto; }
  .meta .lbl { text-align: right; padding-right: 8px; color: #000; white-space: nowrap; }
  .meta .val { font-weight: 700; text-align: right; color: #000; }
  .customer { margin: 16px 0 10px; font-size: 9pt; line-height: 1.35; color: #000; }
  .customer h3 { margin: 0 0 4px; font-size: 10pt; color: #000; }
  table.lines { width: 100%; border-collapse: collapse; font-size: 12px; }
  table.lines thead { display: table-header-group; }
  table.lines th, table.lines td { border: 1px solid #000; padding: 4px 5px; vertical-align: top; color: #000; font-weight: 400; font-size: 12px; }
  table.lines th { background: #f0f0f0; font-weight: 400; color: #000; }
  .c { text-align: center; }
  .r { text-align: right; white-space: nowrap; }
  .litem-name { font-weight: 700; color: #000; font-size: 12px; }
  .litem-desc { font-weight: 400; color: #000; font-size: 12px; }
  .bottom { width: 100%; border-collapse: collapse; margin-top: 14px; }
  .bottom > tbody > tr > td { vertical-align: top; }
  .notes-terms { font-size: 8.5pt; line-height: 1.4; }
  .notes-terms h4 { margin: 0 0 4px; font-size: 9pt; font-weight: 700; color: #000; }
  .notes-terms .term-lbl { font-weight: 400; color: #000; }
  ul.notes { margin: 0; padding-left: 1.1em; list-style: none; font-weight: 400; }
  ul.notes li:before { content: "- "; margin-left: -1em; }
  .totals { width: 240px; border-collapse: collapse; font-size: 9pt; margin-left: auto; }
  .totals td { padding: 3px 6px; }
  .totals .lbl { text-align: right; font-weight: 700; color: #000; }
  .totals .amt { text-align: right; font-variant-numeric: tabular-nums; font-weight: 400; }
  .totals .grand td { font-size: 10.5pt; border-top: 2px solid #111; padding-top: 6px; }
  .totals .grand .lbl { font-weight: 700; }
  .totals .grand .amt { font-weight: 400; }
  .words { margin-top: 10px; font-size: 9pt; }
  .sigs { width: 100%; border-collapse: collapse; margin-top: 28px; }
  .sigs td { width: 45%; font-size: 8.5pt; vertical-align: top; }
</style>
</head>
<body>
<div class="sheet">
  <table class="header">
    <tr>
      <td style="width:55%">
        @if ($logoSrc !== '')
          <img class="logo" src="{{ $logoSrc }}" alt="SNS logo"/>
        @endif
        <div class="supplier">
          <div class="name">{{ $doc['supplier']['name'] ?? 'SNS Furniture Manufacturing' }}</div>
          <div>{{ $doc['supplier']['address_line'] ?? '' }}</div>
        </div>
      </td>
      <td style="width:45%">
        <h1 class="title">{{ $title }}</h1>
        <table class="meta">
          <tr><td class="lbl">Invoice Id</td><td class="val">{{ $doc['doc_number'] ?? '' }}</td></tr>
          <tr><td class="lbl">Date</td><td class="val">{{ $docDate }}</td></tr>
          @if (($doc['doc_type'] ?? '') === 'CREDIT_NOTE' && !empty($doc['parent_doc_number']))
            <tr><td class="lbl">Against Invoice</td><td class="val">{{ $doc['parent_doc_number'] }}</td></tr>
          @endif
        </table>
      </td>
    </tr>
  </table>

  <div class="customer">
    <h3>Customer</h3>
    <div><strong>{{ $doc['customer']['name'] ?? '' }}</strong></div>
    <div>{{ $doc['customer']['address_line'] ?? '' }}</div>
  </div>

  <table class="lines">
    <thead>
      <tr>
        <th>#</th>
        <th>Name</th>
        <th>Unit</th>
        <th>Qty</th>
        <th>Pieces</th>
        <th>Unit Price</th>
        <th>Total Price</th>
      </tr>
    </thead>
    <tbody>
    @foreach (($doc['lines'] ?? []) as $i => $line)
      <tr>
        <td class="c">{{ $line['line_no'] ?? ($i + 1) }}</td>
        <td>
          <span class="litem-name">{{ $line['name'] ?? '' }}</span>@if (!empty(trim((string) ($line['description'] ?? ''))))<span class="litem-desc">: {{ $line['description'] }}</span>@endif
        </td>
        <td class="c">{{ $line['uom_code'] ?? '' }}</td>
        <td class="r">{{ $fmtQty($line['quantity'] ?? 0) }}</td>
        <td class="c">{{ $line['unit_count'] ?? '' }}</td>
        <td class="r">{{ $fmtMoney($line['unit_price'] ?? 0) }}</td>
        <td class="r">{{ $fmtMoney($line['line_total'] ?? 0) }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>

  <table class="bottom">
    <tr>
      <td style="width:58%">
        <div class="notes-terms">
          <h4>Notes:</h4>
          @php
            $notes = array_values(array_filter(
              array_map('strval', $doc['notes'] ?? []),
              fn ($n) => trim($n) !== ''
            ));
          @endphp
          @if (count($notes))
            <ul class="notes">
              @foreach ($notes as $note)
                <li>{{ $note }}</li>
              @endforeach
            </ul>
          @else
            <p>—</p>
          @endif
          <h4>Terms &amp; Conditions:</h4>
          <div><span class="term-lbl">Payment:</span> {{ $doc['terms']['payment'] ?? '' }}</div>
          <div><span class="term-lbl">Delivery:</span></div>
          <div><span class="term-lbl">Place:</span> {{ $doc['terms']['delivery_place'] ?? '' }}</div>
          <div><span class="term-lbl">Time:</span> {{ $doc['terms']['delivery_days'] ?? '' }}{{ isset($doc['terms']['delivery_days']) && $doc['terms']['delivery_days'] !== '' && !preg_match('/[a-zA-Z]/', (string) $doc['terms']['delivery_days']) ? ' days' : '' }}</div>
          <div><span class="term-lbl">Validity:</span> {{ $doc['terms']['validity_days'] ?? '' }}{{ isset($doc['terms']['validity_days']) && $doc['terms']['validity_days'] !== '' && !preg_match('/[a-zA-Z]/', (string) $doc['terms']['validity_days']) ? ' days' : '' }}</div>
          <div><span class="term-lbl">Warranty:</span> {{ $doc['terms']['warranty'] ?? '' }}{{ isset($doc['terms']['warranty']) && $doc['terms']['warranty'] !== '' && !preg_match('/[a-zA-Z]/', (string) $doc['terms']['warranty']) ? ' years' : '' }}</div>
        </div>
      </td>
      <td style="width:42%">
        <table class="totals">
          <tr><td class="lbl">Total</td><td class="amt">{{ $fmtMoney($doc['totals']['subtotal'] ?? 0) }}</td></tr>
          <tr><td class="lbl">{{ $discountLabel }}</td><td class="amt">{{ $fmtMoney($doc['discount']['amount'] ?? 0, true) }}</td></tr>
          <tr><td class="lbl">S. Total</td><td class="amt">{{ $fmtMoney($doc['totals']['after_discount'] ?? 0) }}</td></tr>
          <tr><td class="lbl">{{ $taxLabel }}</td><td class="amt">{{ $fmtMoney($doc['tax']['amount'] ?? 0) }}</td></tr>
          <tr class="grand"><td class="lbl">G. Total</td><td class="amt">{{ $fmtMoney($doc['totals']['grand_total'] ?? 0) }}</td></tr>
        </table>
      </td>
    </tr>
  </table>

  @if ($showWords && !empty($doc['amount_in_words']))
    <p class="words"><strong>Amount in words:</strong> {{ $doc['amount_in_words'] }}</p>
  @endif

  <table class="sigs">
    <tr>
      <td>
        @if ($preparedName !== '')
          <div>Name: {{ $preparedName }}</div>
        @endif
        @if ($preparedPhone !== '')
          <div>Tel: {{ $preparedPhone }}</div>
        @endif
        <div>Prepared By</div>
      </td>
      <td>
        @if ($approvedName !== '')
          <div>Name: {{ $approvedName }}</div>
        @endif
        @if ($approvedPhone !== '')
          <div>Tel: {{ $approvedPhone }}</div>
        @endif
        <div>Approved By</div>
      </td>
    </tr>
  </table>
</div>
</body>
</html>
