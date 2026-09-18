<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<title>{{ $vm['title'] }} {{ $vm['docNumber'] }}</title>
<style>
  @page { margin: 0; size: {{ $page['w'] }}pt {{ $page['h'] }}pt; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 9pt;
    color: #111;
    width: {{ $page['w'] }}pt;
    height: {{ $page['h'] }}pt;
  }
  .page {
    position: relative;
    width: {{ $page['w'] }}pt;
    height: {{ $page['h'] }}pt;
    padding: 24pt {{ $page['mr'] }}pt 24pt {{ $page['ml'] }}pt;
  }
  .header { width: 100%; border-collapse: collapse; }
  .header td { vertical-align: top; }
  .logo { height: 78pt; width: auto; display: block; margin-left: -18pt; }
  .title {
    font-size: 18pt;
    font-weight: 700;
    text-align: right;
    margin: 8pt 0 6pt;
    line-height: 1.1;
  }
  .meta-line {
    text-align: right;
    font-size: 9pt;
    line-height: 1.45;
  }
  .supplier {
    margin-top: 10pt;
    font-size: 9pt;
    line-height: 1.3;
  }
  .supplier .name { font-weight: 700; font-size: 11pt; }
  .customer { margin-top: 14pt; margin-bottom: 12pt; font-size: 9pt; line-height: 1.3; }
  .customer .label { font-weight: 700; font-size: 10pt; }
  .customer .cname { font-weight: 700; font-size: 10pt; margin-top: 2pt; }
  table.lines {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 7.5pt;
  }
  table.lines th {
    background: #E8E8E8;
    border: 0.6pt solid #222;
    font-weight: 700;
    font-size: 8pt;
    padding: 4pt 2pt;
  }
  table.lines td {
    border: 0.6pt solid #333;
    padding: 3pt 2pt;
    vertical-align: top;
  }
  .c { text-align: center; }
  .r { text-align: right; white-space: nowrap; }
  .l { text-align: left; }
  .desc { font-weight: 400; color: #333; font-size: 7.5pt; display: block; margin-top: 1pt; }
  .bottom {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10pt;
  }
  .bottom > tbody > tr > td { vertical-align: top; }
  .notes-terms { font-size: 8.5pt; line-height: 1.35; padding-right: 8pt; }
  .notes-terms .h { font-weight: 700; font-size: 9pt; margin: 0 0 2pt; }
  .notes-terms .gap { height: 8pt; }
  .totals { width: 100%; border-collapse: collapse; font-size: 9pt; }
  .totals td { padding: 1pt 0; vertical-align: baseline; }
  .totals .lbl { text-align: left; width: 55%; }
  .totals .amt { text-align: right; width: 45%; white-space: nowrap; }
  .totals .grand td { font-weight: 700; font-size: 10pt; padding-top: 2pt; }
  .sigs {
    width: 100%;
    border-collapse: collapse;
    margin-top: 16pt;
    font-size: 8.5pt;
  }
  .sigs td { width: 48%; vertical-align: top; }
  .sigs .label { font-size: 8pt; margin-top: 2pt; }
  .words { margin-top: 12pt; font-size: 8pt; }
</style>
</head>
<body>
@php
    $prepared = trim(implode(' ', array_filter([
        $vm['preparedBy']['name'] ?? '',
        $vm['preparedBy']['phone'] ?? '',
    ], fn ($v) => trim((string) $v) !== '')));
    $approved = trim(implode(' ', array_filter([
        $vm['approvedBy']['name'] ?? '',
        $vm['approvedBy']['phone'] ?? '',
    ], fn ($v) => trim((string) $v) !== '')));
@endphp
<div class="page">
  <table class="header">
    <tr>
      <td style="width:58%">
        @if ($logoSrc !== '')
          <img class="logo" src="{{ $logoSrc }}" alt="SNS"/>
        @endif
        <div class="supplier">
          <div class="name">{{ $vm['supplier']['name'] }}</div>
          <div>{{ $vm['supplier']['address_line'] }}</div>
        </div>
      </td>
      <td style="width:42%">
        <div class="title">{{ $vm['title'] }}</div>
        <div class="meta-line">Invoice Id&nbsp;&nbsp;{{ $vm['docNumber'] }}</div>
        <div class="meta-line">Date&nbsp;&nbsp;{{ $vm['docDate'] }}</div>
        @if ($vm['showValidUntil'] && $vm['validUntil'])
          <div class="meta-line">Valid Until&nbsp;&nbsp;{{ $vm['validUntil'] }}</div>
        @endif
        @if ($vm['showAgainstInvoice'] && $vm['parentDocNumber'])
          <div class="meta-line">Against Invoice&nbsp;&nbsp;{{ $vm['parentDocNumber'] }}</div>
        @endif
      </td>
    </tr>
  </table>

  <div class="customer">
    <div class="label">Customer</div>
    <div class="cname">{{ $vm['customer']['name'] }}</div>
    @if (trim((string) $vm['customer']['address_line']) !== '')
      <div>{{ $vm['customer']['address_line'] }}</div>
    @endif
  </div>

  <table class="lines">
    <thead>
      <tr>
        <th class="c" style="width:{{ $cols['lineNo'] }}pt">#</th>
        <th class="l" style="width:{{ $cols['name'] }}pt">Name</th>
        <th class="c" style="width:{{ $cols['unit'] }}pt">Unit</th>
        <th class="r" style="width:{{ $cols['qty'] }}pt">Qty</th>
        <th class="c" style="width:{{ $cols['pieces'] }}pt">Pieces</th>
        <th class="r" style="width:{{ $cols['unitPrice'] }}pt">Unit Price</th>
        <th class="r" style="width:{{ $cols['total'] }}pt">Total Price</th>
      </tr>
    </thead>
    <tbody>
    @foreach ($vm['lines'] as $line)
      <tr>
        <td class="c">{{ $line['lineNo'] }}</td>
        <td class="l">
          <strong>{{ $line['name'] }}</strong>
          @if ($line['description'] !== '')
            <span class="desc">{{ $line['description'] }}</span>
          @endif
        </td>
        <td class="c">{{ $line['uom'] }}</td>
        <td class="r">{{ $line['qty'] }}</td>
        <td class="c">{{ $line['pieces'] }}</td>
        <td class="r">{{ $line['unitPrice'] }}</td>
        <td class="r">{{ $line['lineTotal'] }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>

  <table class="bottom">
    <tr>
      <td style="width:58%">
        <div class="notes-terms">
          <div class="h">Notes:</div>
          @if (count($vm['notes']) === 0)
            <div>-</div>
          @else
            @foreach ($vm['notes'] as $note)
              <div>- {{ $note }}</div>
            @endforeach
          @endif
          <div class="gap"></div>
          <div class="h">Terms &amp; Conditions:</div>
          <div>Payment: {{ $vm['terms']['payment'] }}</div>
          <div>Delivery:</div>
          <div>Place: {{ $vm['terms']['delivery_place'] }}</div>
          <div>Time: {{ $vm['terms']['delivery_days'] }}</div>
          <div>Validity: {{ $vm['terms']['validity_days'] }}</div>
          <div>Warrenty: {{ $vm['terms']['warranty'] }}</div>
        </div>
      </td>
      <td style="width:42%">
        <table class="totals">
          <tr><td class="lbl">Total</td><td class="amt">{{ $vm['subtotalDisplay'] }}</td></tr>
          <tr><td class="lbl">{{ $vm['discountLabel'] }}</td><td class="amt">{{ $vm['discountDisplay'] }}</td></tr>
          <tr><td class="lbl">S. Total</td><td class="amt">{{ $vm['afterDiscountDisplay'] }}</td></tr>
          <tr><td class="lbl">{{ $vm['taxLabel'] }}</td><td class="amt">{{ $vm['taxDisplay'] }}</td></tr>
          <tr class="grand"><td class="lbl">G. Total</td><td class="amt">{{ $vm['grandTotalDisplay'] }}</td></tr>
        </table>
      </td>
    </tr>
  </table>

  <table class="sigs">
    <tr>
      <td>
        <div>{{ $prepared !== '' ? $prepared : '&nbsp;' }}</div>
        <div class="label">Prepared By Phone Number</div>
      </td>
      <td>
        <div>{{ $approved !== '' ? $approved : '&nbsp;' }}</div>
        <div class="label">Approved By Contact</div>
      </td>
    </tr>
  </table>

  @if (!empty($vm['amountInWords']))
    <div class="words">Amount in words: {{ $vm['amountInWords'] }}</div>
  @endif
</div>
</body>
</html>
