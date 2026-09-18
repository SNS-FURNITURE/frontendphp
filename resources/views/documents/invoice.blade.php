<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<title>{{ ($doc['doc_type'] ?? 'PROFORMA') === 'CREDIT_NOTE' ? 'Credit Note' : 'Proforma Invoice' }} {{ $doc['doc_number'] ?? '' }}</title>
<style>
  @page { size: A4 portrait; margin: 8mm 10mm 12mm 8mm; }
  * { box-sizing: border-box; }
  body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 10pt;
    color: #111;
    margin: 0;
    padding: 0;
  }
  .sheet { position: relative; }
  .header { width: 100%; }
  .header td { vertical-align: top; }
  .logo { height: 72px; width: auto; }
  .title { font-size: 20pt; font-weight: 700; text-align: right; margin: 0; padding-top: 12px; }
  .supplier { margin-top: 6px; font-size: 9pt; line-height: 1.35; }
  .supplier .name { font-weight: 700; font-size: 11pt; }
  .meta { border-collapse: collapse; font-size: 9pt; margin-top: 4px; margin-left: auto; }
  .meta .lbl { text-align: right; padding-right: 8px; color: #444; white-space: nowrap; }
  .meta .val { font-weight: 700; text-align: right; }
  .customer { margin: 14px 0 10px; font-size: 9pt; line-height: 1.35; }
  .customer h3 { margin: 0 0 4px; font-size: 10pt; }
  table.lines { width: 100%; border-collapse: collapse; font-size: 9pt; }
  table.lines th, table.lines td { border: 1px solid #333; padding: 4px 5px; vertical-align: top; }
  table.lines th { background: #f0f0f0; font-weight: 700; }
  .c { text-align: center; }
  .r { text-align: right; white-space: nowrap; }
  .desc { font-weight: 400; color: #333; font-size: 8.5pt; }
  .bottom { width: 100%; margin-top: 12px; }
  .bottom td { vertical-align: top; }
  .notes-terms { font-size: 8.5pt; line-height: 1.4; }
  .notes-terms h4 { margin: 0 0 4px; font-size: 9pt; }
  ul.notes { margin: 0; padding-left: 1.1em; list-style: none; }
  ul.notes li:before { content: "- "; margin-left: -1em; }
  .totals { width: 240px; border-collapse: collapse; font-size: 9pt; margin-left: auto; }
  .totals td { padding: 3px 6px; }
  .totals .lbl { text-align: right; }
  .totals .amt { text-align: right; }
  .totals .grand td { font-weight: 700; font-size: 10.5pt; border-top: 2px solid #111; padding-top: 6px; }
  .words { margin-top: 10px; font-size: 9pt; }
  .sigs { width: 100%; margin-top: 28px; }
  .sigs td { width: 45%; font-size: 8.5pt; vertical-align: top; }
  .sig-line { border-bottom: 1px solid #666; height: 28px; margin: 8px 0; }
</style>
</head>
<body>
@php
    $title = (($doc['doc_type'] ?? 'PROFORMA') === 'CREDIT_NOTE') ? 'Credit Note' : 'Proforma Invoice';
    $docDate = $doc['doc_date'] ?? '';
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $docDate, $m)) {
        $docDate = gmdate('D M j Y', gmmktime(0, 0, 0, (int)$m[2], (int)$m[3], (int)$m[1]));
    }
    $discountLabel = (($doc['discount']['type'] ?? 'PERCENT') === 'PERCENT')
        ? (($doc['discount']['value'] ?? '0').'% Discount')
        : 'Discount';
    $taxLabel = 'VAT '.($doc['tax']['rate'] ?? '15').'%';
    $prepared = trim(($doc['prepared_by']['name'] ?? '').' '.($doc['prepared_by']['phone'] ?? ''));
    $approved = trim(($doc['approved_by']['name'] ?? '').' '.($doc['approved_by']['phone'] ?? ''));
    $logoPath = public_path('sns-logo.png');
    $logoSrc = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : '';
@endphp
<div class="sheet">
  <table class="header">
    <tr>
      <td style="width:55%">
        @if ($logoSrc)
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
          @if (($doc['doc_type'] ?? '') === 'PROFORMA' && !empty($doc['valid_until']))
            <tr><td class="lbl">Valid Until</td><td class="val">{{ $doc['valid_until'] }}</td></tr>
          @endif
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
          <strong>{{ $line['name'] ?? '' }}</strong>
          @if (!empty($line['description']))
            <br/><span class="desc">{{ $line['description'] }}</span>
          @endif
        </td>
        <td class="c">{{ $line['uom_code'] ?? '' }}</td>
        <td class="r">{{ $line['quantity'] ?? '' }}</td>
        <td class="c">{{ $line['unit_count'] ?? '' }}</td>
        <td class="r">{{ $line['unit_price'] ?? '' }}</td>
        <td class="r">{{ $line['line_total'] ?? '' }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>

  <table class="bottom">
    <tr>
      <td style="width:58%">
        <div class="notes-terms">
          <h4>Notes:</h4>
          @if (!empty($doc['notes']))
            <ul class="notes">
              @foreach ($doc['notes'] as $note)
                @if (trim((string)$note) !== '')
                  <li>{{ $note }}</li>
                @endif
              @endforeach
            </ul>
          @else
            <p>—</p>
          @endif
          <h4>Terms &amp; Conditions:</h4>
          <div>Payment: {{ $doc['terms']['payment'] ?? '' }}</div>
          <div>Delivery:</div>
          <div>Place: {{ $doc['terms']['delivery_place'] ?? '' }}</div>
          <div>Time: {{ $doc['terms']['delivery_days'] ?? '' }}</div>
          <div>Validity: {{ $doc['terms']['validity_days'] ?? '' }}</div>
          <div>Warrenty: {{ $doc['terms']['warranty'] ?? '' }}</div>
        </div>
      </td>
      <td style="width:42%">
        <table class="totals">
          <tr><td class="lbl">Total</td><td class="amt">{{ $doc['totals']['subtotal'] ?? '0.00' }} {{ $doc['currency'] ?? 'ETB' }}</td></tr>
          <tr><td class="lbl">{{ $discountLabel }}</td><td class="amt">{{ $doc['discount']['amount'] ?? '0.00' }} {{ $doc['currency'] ?? 'ETB' }}</td></tr>
          <tr><td class="lbl">S. Total</td><td class="amt">{{ $doc['totals']['after_discount'] ?? '0.00' }} {{ $doc['currency'] ?? 'ETB' }}</td></tr>
          <tr><td class="lbl">{{ $taxLabel }}</td><td class="amt">{{ $doc['tax']['amount'] ?? '0.00' }} {{ $doc['currency'] ?? 'ETB' }}</td></tr>
          <tr class="grand"><td class="lbl">G. Total</td><td class="amt">{{ $doc['totals']['grand_total'] ?? '0.00' }} {{ $doc['currency'] ?? 'ETB' }}</td></tr>
        </table>
      </td>
    </tr>
  </table>

  @if (!empty($doc['amount_in_words']))
    <p class="words"><strong>Amount in words:</strong> {{ $doc['amount_in_words'] }}</p>
  @endif

  <table class="sigs">
    <tr>
      <td>
        <div class="sig-line"></div>
        <div>Prepared By Phone Number</div>
        <div>Name: {{ $doc['prepared_by']['name'] ?? '' }}</div>
        <div>Tel: {{ $doc['prepared_by']['phone'] ?? '' }}</div>
      </td>
      <td>
        <div class="sig-line"></div>
        <div>Approved By Contact</div>
        <div>Name: {{ $doc['approved_by']['name'] ?? '' }}</div>
        <div>Tel: {{ $doc['approved_by']['phone'] ?? '' }}</div>
      </td>
    </tr>
  </table>
</div>
</body>
</html>
