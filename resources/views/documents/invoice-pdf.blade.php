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
    color: #000;
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
  .logo { height: 96pt; width: auto; display: block; margin: 0; }
  .title {
    font-size: 18pt;
    font-weight: 700;
    text-align: right;
    margin: 8pt 0 6pt;
    line-height: 1.1;
    color: #000;
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
    font-size: 12px;
    color: #000;
  }
  table.lines th {
    background: #E8E8E8;
    border: 0.6pt solid #000;
    font-weight: 400;
    font-size: 12px;
    padding: 4pt 2pt;
    color: #000;
  }
  table.lines td {
    border: 0.6pt solid #000;
    padding: 3pt 2pt;
    vertical-align: top;
    color: #000;
    font-weight: 400;
    font-size: 12px;
  }
  .c { text-align: center; }
  .r { text-align: right; white-space: nowrap; }
  .l { text-align: left; }
  .litem-name { font-weight: 700; color: #000; font-size: 12px; }
  .litem-desc { font-weight: 400; color: #000; font-size: 12px; }
  .bottom {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10pt;
  }
  .bottom > tbody > tr > td { vertical-align: top; }
  .notes-terms { font-size: 8.5pt; line-height: 1.35; padding-right: 8pt; }
  .notes-terms .h { font-weight: 700; font-size: 9pt; margin: 0 0 2pt; color: #000; }
  .notes-terms .term-lbl { font-weight: 400; color: #000; }
  .notes-terms .gap { height: 8pt; }
  .totals { width: 100%; border-collapse: collapse; font-size: 9pt; }
  .totals td { padding: 1pt 0; vertical-align: baseline; }
  .totals .lbl { text-align: left; width: 55%; font-weight: 700; color: #000; }
  .totals .amt { text-align: right; width: 45%; white-space: nowrap; font-weight: 400; }
  .totals .grand td { font-size: 10pt; padding-top: 2pt; }
  .totals .grand .lbl { font-weight: 700; }
  .totals .grand .amt { font-weight: 400; }
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
    $preparedName = trim((string) ($vm['preparedBy']['name'] ?? ''));
    $preparedPhone = trim((string) ($vm['preparedBy']['phone'] ?? ''));
    $approvedName = trim((string) ($vm['approvedBy']['name'] ?? ''));
    $approvedPhone = trim((string) ($vm['approvedBy']['phone'] ?? ''));
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
          <span class="litem-name">{{ $line['name'] }}</span>@if ($line['description'] !== '')<span class="litem-desc">: {{ $line['description'] }}</span>@endif
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
          <div><span class="term-lbl">Payment:</span> {{ $vm['terms']['payment'] }}</div>
          <div><span class="term-lbl">Delivery:</span></div>
          <div><span class="term-lbl">Place:</span> {{ $vm['terms']['delivery_place'] }}</div>
          <div><span class="term-lbl">Time:</span> {{ $vm['terms']['delivery_days'] }}</div>
          <div><span class="term-lbl">Validity:</span> {{ $vm['terms']['validity_days'] }}</div>
          <div><span class="term-lbl">Warranty:</span> {{ $vm['terms']['warranty'] }}</div>
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
        <div>{{ $preparedName !== '' ? 'Name: '.$preparedName : '&nbsp;' }}</div>
        @if ($preparedPhone !== '')
          <div>Tel: {{ $preparedPhone }}</div>
        @endif
        <div class="label">Prepared By</div>
      </td>
      <td>
        <div>{{ $approvedName !== '' ? 'Name: '.$approvedName : '&nbsp;' }}</div>
        @if ($approvedPhone !== '')
          <div>Tel: {{ $approvedPhone }}</div>
        @endif
        <div class="label">Approved By</div>
      </td>
    </tr>
  </table>

  @if (!empty($vm['amountInWords']))
    <div class="words">Amount in words: {{ $vm['amountInWords'] }}</div>
  @endif
</div>
</body>
</html>
