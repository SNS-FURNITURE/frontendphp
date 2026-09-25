@php
    $editorId = $editorId ?? 'invoice-editor';
    $readOnly = $readOnly ?? false;
    $formAction = $formAction ?? null;
    $formMethod = $formMethod ?? 'POST';
    $submitStatus = $submitStatus ?? null;
    $logoPath = public_path('sns-logo.png');
    $invoiceLogoSrc = is_file($logoPath)
        ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath))
        : asset('sns-logo.png');
    
    $user = auth()->user();
    $canViewPrices = (bool) ($user?->canViewInvoicePrices());
    $canEditPrices = (bool) ($user?->canEditInvoicePrices());
    $catalogProducts = \App\Models\Product::all();
    $catalogCategories = $catalogProducts->pluck('category')->unique()->values();
    $customerOptions = isset($customers)
        ? $customers->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'address' => $c->address, 'phone' => $c->phone])->values()->toArray()
        : [];

    $invoiceFontRegular = '';
    $invoiceFontBold = '';
    $invoiceFontEthiopic = '';
    $invoiceFontDir = resource_path('documents/fonts');
    if (! is_dir($invoiceFontDir)) {
        $invoiceFontDir = storage_path('app/documents/fonts');
    }
    if (is_file($invoiceFontDir.'/NotoSans-Regular.ttf')) {
        $invoiceFontRegular = 'data:font/ttf;base64,'.base64_encode((string) file_get_contents($invoiceFontDir.'/NotoSans-Regular.ttf'));
    }
    if (is_file($invoiceFontDir.'/NotoSans-Bold.ttf')) {
        $invoiceFontBold = 'data:font/ttf;base64,'.base64_encode((string) file_get_contents($invoiceFontDir.'/NotoSans-Bold.ttf'));
    }
    if (is_file($invoiceFontDir.'/NotoSansEthiopic-Regular.ttf')) {
        $invoiceFontEthiopic = 'data:font/ttf;base64,'.base64_encode((string) file_get_contents($invoiceFontDir.'/NotoSansEthiopic-Regular.ttf'));
    }
    $invoiceFontStack = "'Neris', 'NotoSans', 'NotoEthiopic', DejaVu Sans, sans-serif";
@endphp

@push('styles')
<link href="https://fonts.cdnfonts.com/css/neris" rel="stylesheet">
<style>
@if ($invoiceFontRegular)
@font-face { font-family: 'NotoSans'; src: url('{{ $invoiceFontRegular }}') format('truetype'); font-weight: 400; }
@endif
@if ($invoiceFontBold)
@font-face { font-family: 'NotoSans'; src: url('{{ $invoiceFontBold }}') format('truetype'); font-weight: 700; }
@endif
@if ($invoiceFontEthiopic)
@font-face { font-family: 'NotoEthiopic'; src: url('{{ $invoiceFontEthiopic }}') format('truetype'); font-weight: 400; }
@endif
/* Pixel match: sns ERP InvoiceDocumentEditor (Tailwind → CSS) */
.invoice-desk {
    display: flex;
    justify-content: center;
    overflow: auto;
    background: rgba(229, 229, 229, 0.8);
    padding: 2rem 1rem;
}
.invoice-a4 {
    position: relative;
    flex-shrink: 0;
    overflow: hidden;
    background: #fff;
    color: #171717;
    font-size: 13px;
    line-height: 1.375;
    font-family: {!! $invoiceFontStack !!};
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    border: 1px solid #d4d4d4;
    box-sizing: border-box;
    width: 210mm;
    height: 297mm;
    min-width: 210mm;
    min-height: 297mm;
    max-width: 210mm;
    max-height: 297mm;
    padding: 0 12mm 12mm 14mm;
}
.invoice-a4,
.invoice-a4 * {
    font-family: {!! $invoiceFontStack !!};
}
.invoice-a4 input,
.invoice-a4 textarea,
.invoice-a4 button,
.invoice-a4 select {
    font-family: inherit;
}
.invoice-a4 [contenteditable]:focus,
.invoice-a4 input.ghost:focus {
    outline: none;
    background: rgba(255, 251, 235, 0.4);
    box-shadow: 0 0 0 1px rgba(252, 211, 77, 0.7);
    border-radius: 2px;
}
.invoice-a4 input.ghost,
.invoice-a4 textarea.ghost {
    background: transparent;
    border: 0;
    border-bottom: 1px solid transparent;
    color: inherit;
    padding: 0;
    margin: 0;
    width: 100%;
    min-width: 0;
    font: inherit;
}
.invoice-a4 textarea.ghost {
    resize: vertical;
    line-height: 1.4;
}
.invoice-a4 input.ghost.paper-editable:focus,
.invoice-a4 textarea.ghost.paper-editable:focus {
    border-bottom-color: rgba(252, 211, 77, 0.9);
}
.inv-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: calc(-4rem + 2px);
}
.inv-logo {
    width: 12rem;
    height: auto;
    object-fit: contain;
    object-position: left top;
    display: block;
    margin-top: -16px;
    margin-left: calc(-5mm - 2px);
}
.inv-title {
    margin: 8px 0 0 0;
    padding-top: 28px;
    font-size: 1.25rem;
    line-height: 1.5rem;
    font-weight: 700;
    letter-spacing: 0.025em;
    text-align: right;
}
.inv-supplier-meta {
    display: flex;
    justify-content: space-between;
    gap: 1.5rem;
    padding-top: 2px;
    margin-bottom: 0.25rem;
}
.inv-supplier { flex: 1; min-width: 0; padding-top: 4px; }
.inv-supplier .name { font-weight: 700; font-size: 14px; display: block; margin-bottom: -0.1rem; }
.inv-supplier .addr { color: #404040; display: block; white-space: pre-wrap; font-weight: 700; font-size: 14px; line-height: 1.25; }
.inv-meta { width: 42%; flex-shrink: 0; text-align: right; font-size: 14px; }
.inv-meta-row {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    align-items: baseline;
    margin-bottom: 0.25rem;
    font-size: 14px;
}
.inv-meta-row .lbl { color: #737373; font-size: 14px; }
.inv-meta-row .val {
    font-weight: 600;
    text-align: left;
    min-width: 8rem;
    font-variant-numeric: tabular-nums;
    font-size: 14px;
}
.inv-customer {
    margin-top: 16px;
    margin-bottom: 0.5rem;
    border-bottom: 1px solid #e5e5e5;
    padding: 5px 0 8px;
}
.inv-customer .cap {
    font-size: 13px;
    text-transform: none;
    letter-spacing: normal;
    color: #737373;
    margin-right: 0.5rem;
}
.inv-customer .cname { font-weight: 700; display: inline; font-size: 13px; }
.inv-customer .caddr { color: #404040; display: inline; font-size: 13px; white-space: pre-wrap; }
.inv-lines { width: 100%; border-collapse: collapse; margin-top: 8px; margin-bottom: 0.5rem; }
.inv-lines th {
    border-bottom: 2px solid #000;
    text-align: left;
    font-size: 13px;
    font-weight: bold;
    color: #000;
    padding: 0.05rem 0.06rem 0.05rem 0;
}
.inv-lines th.r, .inv-lines td.r { text-align: right; }
.inv-lines td {
    border-bottom: 1px solid #d4d4d4;
    vertical-align: top;
    padding: 4px 0.06rem 4px 0;
    color: #000;
    font-weight: 400;
    font-size: 12px;
}
.inv-lines .litem {
    font-size: 12px;
    line-height: 1.3;
    color: #000;
    white-space: pre-wrap;
    margin: 0;
    padding: 0;
}
.inv-lines .lname { font-weight: 700; display: inline; color: #000; font-size: 12px; }
.inv-lines .lsep { font-weight: 400; display: inline; }
.inv-lines .ldesc { color: #000; font-size: 12px; font-weight: 400; display: inline; white-space: pre-wrap; }
.invoice-a4.is-advisor-paper .inv-lines {
    margin-left: -6px;
    width: calc(100% + 6px);
    margin-top: 10px;
    margin-bottom: 12px;
    border-collapse: collapse;
    font-size: 11px;
}
.invoice-a4.is-advisor-paper .inv-lines th {
    background: #e8e8e8;
    border: 0;
    font-weight: 700;
    font-size: 11px;
    padding: 2px 2px 2px 0;
    text-align: left;
    color: #000;
}
.invoice-a4.is-advisor-paper .inv-lines td {
    border: 0;
    border-bottom: 1px solid #d4d4d4;
    padding: 4px 2px 4px 0;
    vertical-align: top;
    font-size: 11px;
    font-weight: 400;
    color: #000;
}
.invoice-a4.is-advisor-paper .inv-lines thead th {
    border-bottom: 1px solid #d4d4d4;
}
.invoice-a4.is-advisor-paper .inv-lines th:nth-child(1),
.invoice-a4.is-advisor-paper .inv-lines td:nth-child(1) {
    width: 1.1rem;
    min-width: 1.1rem;
    max-width: 1.1rem;
    text-align: center;
    padding-left: 0;
    padding-right: 2px;
}
.invoice-a4.is-advisor-paper .inv-lines th:nth-child(2),
.invoice-a4.is-advisor-paper .inv-lines td:nth-child(2) {
    text-align: left;
}
.invoice-a4.is-advisor-paper .inv-lines th:nth-child(3),
.invoice-a4.is-advisor-paper .inv-lines td:nth-child(3) {
    width: 2.25rem;
    text-align: center;
    padding-left: 0;
    padding-right: 0;
}
.invoice-a4.is-advisor-paper .inv-lines th:nth-child(4),
.invoice-a4.is-advisor-paper .inv-lines td:nth-child(4) {
    width: 2.75rem;
    text-align: right;
    white-space: normal;
    font-variant-numeric: tabular-nums;
    padding-left: 0;
    padding-right: 0;
}
.invoice-a4.is-advisor-paper .inv-lines th:nth-child(5),
.invoice-a4.is-advisor-paper .inv-lines td:nth-child(5) {
    width: 2.25rem;
    text-align: center;
    font-variant-numeric: tabular-nums;
    padding-left: 0;
    padding-right: 0;
}
.invoice-a4.is-advisor-paper .inv-lines .litem,
.invoice-a4.is-advisor-paper .inv-lines .lname,
.invoice-a4.is-advisor-paper .inv-lines .lsep,
.invoice-a4.is-advisor-paper .inv-lines .ldesc {
    font-size: 11px;
    line-height: 1.25;
}
.invoice-a4.is-advisor-paper .inv-lines td:nth-child(1).muted {
    color: #000;
}
.invoice-a4.is-advisor-paper {
    font-size: 10px;
    line-height: 1.15;
}
.invoice-a4.is-advisor-paper .inv-supplier .name,
.invoice-a4.is-advisor-paper .inv-supplier .addr,
.invoice-a4.is-advisor-paper .inv-meta,
.invoice-a4.is-advisor-paper .inv-meta-row,
.invoice-a4.is-advisor-paper .inv-meta-row .lbl,
.invoice-a4.is-advisor-paper .inv-meta-row .val {
    font-size: 14px;
    line-height: 1.25;
}
.invoice-a4.is-advisor-paper .inv-supplier-meta {
    margin-bottom: 0;
}
.invoice-a4.is-advisor-paper .inv-customer {
    margin-top: 16px;
    margin-bottom: 0.2rem;
    padding: 5px 0 8px;
}
.invoice-a4.is-advisor-paper .inv-customer .cap,
.invoice-a4.is-advisor-paper .inv-customer .cname,
.invoice-a4.is-advisor-paper .inv-customer .caddr,
.invoice-a4.is-advisor-paper .inv-customer > div {
    font-size: 13px !important;
    line-height: 1.25;
}
.invoice-a4.is-advisor-paper .inv-bottom {
    margin-top: 20px;
    gap: 0.75rem;
}
.invoice-a4.is-advisor-paper .inv-notes .cap,
.invoice-a4.is-advisor-paper .inv-notes .term-title,
.invoice-a4.is-advisor-paper .inv-term-row,
.invoice-a4.is-advisor-paper .inv-term-row .lbl,
.invoice-a4.is-advisor-paper .inv-term-row .val,
.invoice-a4.is-advisor-paper .inv-term-row .term-unit-suffix,
.invoice-a4.is-advisor-paper .inv-notes textarea,
.invoice-a4.is-advisor-paper .inv-notes .block > div,
.invoice-a4.is-advisor-paper .inv-notes input.ghost {
    font-size: 12px;
    line-height: 1.3;
}
.invoice-a4.is-advisor-paper .inv-notes .block > div {
    min-height: 1.6rem !important;
}
.invoice-a4.is-advisor-paper .inv-notes .cap {
    text-transform: none;
    letter-spacing: normal;
    margin-bottom: 0.05rem;
}
.invoice-a4.is-advisor-paper .inv-notes .block {
    margin-bottom: 0.3rem;
}
.invoice-a4.is-advisor-paper .inv-notes .advisor-notes-field {
    min-height: 1.6rem !important;
}
.invoice-a4.is-advisor-paper .inv-term-row {
    margin-bottom: 0.06rem;
}
.invoice-a4.is-advisor-paper .term-title {
    margin-bottom: 0.06rem !important;
}
.invoice-a4.is-advisor-paper .inv-sigs {
    margin-top: 18px;
    gap: 0.5rem;
}
.invoice-a4.is-advisor-paper .inv-sig-val,
.invoice-a4.is-advisor-paper .inv-sig-lbl,
.invoice-a4.is-advisor-paper .inv-sig-val input.ghost {
    font-size: 12px;
    line-height: 1.3;
}
.invoice-a4.is-advisor-paper .inv-sig-val {
    min-height: 1rem;
    padding-bottom: 0.1rem;
}
.invoice-a4.is-advisor-paper .inv-lines .litem {
    white-space: normal;
}
.inv-lines .ghost { font-size: 12px; line-height: 1.2; }
.inv-add-line {
    margin-bottom: 1.5rem;
    font-size: 12px;
    color: #525252;
    border: 1px dashed #d4d4d4;
    background: #fff;
    padding: 0.25rem 0.75rem;
    border-radius: 2px;
    cursor: pointer;
}
.inv-add-line:hover { background: rgba(255, 251, 235, 0.5); border-color: #fcd34d; }
.inv-bottom { display: flex; gap: 2rem; margin-top: 1rem; }
.inv-notes { flex: 1; min-width: 0; }
.inv-notes .cap {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #000;
    font-weight: 700;
    margin-bottom: 0.15rem;
}
.inv-notes .block { margin-bottom: 1rem; }
.inv-notes textarea,
.inv-notes .block > div,
.inv-notes .term-title,
.inv-term-row,
.inv-term-row .lbl,
.inv-term-row .val,
.inv-term-row .term-unit-suffix {
    font-size: 12px;
    line-height: 1.3;
}
.inv-term-row {
    display: flex;
    gap: 0.25rem;
    align-items: baseline;
    margin-bottom: 0.25rem;
}
.inv-term-row .lbl,
.inv-notes .term-title {
    color: #000;
    font-weight: 400;
    flex-shrink: 0;
}
.inv-term-row .val { flex: 1; min-width: 0; font-weight: 400; }
.inv-term-row .term-unit-field {
    display: inline-flex;
    align-items: baseline;
    gap: 0.2rem;
    flex-wrap: nowrap;
}
.inv-term-row .term-unit-input {
    width: auto;
    max-width: 4rem;
    display: inline-block;
}
.inv-term-row .term-unit-suffix {
    font-weight: 400;
    white-space: nowrap;
}
.inv-term-indent { padding-left: 0.5rem; }
.inv-totals { width: 42%; flex-shrink: 0; font-size: 0.875rem; }
.inv-tot-row {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.1rem;
    align-items: baseline;
}
.inv-tot-row .lbl { color: #000; font-weight: 700; }
.inv-tot-row .amt { font-variant-numeric: tabular-nums; font-weight: 400; }
.inv-grand {
    border-top: 1px solid #262626;
    padding-top: 0.5rem;
    margin-top: 0.5rem;
    font-size: 15px;
}
.inv-grand .lbl { font-weight: 700; }
.inv-grand .amt { font-weight: 400; }
.inv-words {
    margin-top: 0.5rem;
    color: #737373;
    font-style: italic;
    font-size: 12px;
}
.inv-sigs {
    margin-top: 2rem;
    display: flex;
    justify-content: space-between;
    gap: 1.5rem;
}
.inv-sig-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
}
.inv-sig-val {
    min-height: 1.5rem;
    padding-bottom: 0.25rem;
    font-size: 0.9rem;
    border-bottom: 1px solid #000;
}
.inv-sig-lbl {
    padding-top: 0.25rem;
    font-size: 0.8rem;
    font-weight: 700;
    color: #000;
}
.inv-remove {
    background: none;
    border: 0;
    color: rgba(220, 38, 38, 0.8);
    cursor: pointer;
    font-size: 12px;
    text-decoration: underline;
    text-underline-offset: 2px;
    padding: 0;
}
@media print {
    .catalog-view { display: none !important; }
    html, body {
        background: #fff !important;
        color: #111 !important;
        height: auto !important;
        overflow: visible !important;
    }
    .shell, .main, .content {
        display: block !important;
        height: auto !important;
        overflow: visible !important;
        background: #fff !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .invoice-desk {
        background: #fff !important;
        padding: 0 !important;
        display: block !important;
        justify-content: flex-start !important;
    }
    .invoice-a4 {
        box-shadow: none !important;
        border: 0 !important;
        width: 210mm !important;
        min-width: 210mm !important;
        max-width: 210mm !important;
        height: 297mm !important;
        min-height: 297mm !important;
        max-height: 297mm !important;
        padding: 0 12mm 12mm 14mm !important;
        margin: 0 auto !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .no-print { display: none !important; }
    @page { size: A4 portrait; margin: 0; }
}
@media (max-width: 900px) {
    .invoice-desk { padding: 1rem 0.5rem; }
    .invoice-a4 {
        width: 100%;
        min-width: 0;
        max-width: 100%;
        height: auto;
        min-height: 0;
        max-height: none;
        padding: 1rem;
    }
}
.invoice-sticky-actions {
    position: sticky;
    top: 0;
    z-index: 40;
    margin: 0 -1.5rem 1.25rem;
    padding: 0.85rem 1.5rem;
    background: var(--purple-dark, #0d0b21);
    border-bottom: 1px solid var(--border, #2a2550);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
}
.invoice-sticky-actions .toolbar {
    flex-shrink: 0;
}
@media (max-width: 900px) {
    .invoice-sticky-actions {
        margin-left: -1rem;
        margin-right: -1rem;
        padding-left: 1rem;
        padding-right: 1rem;
    }
}
@media (max-width: 640px) {
    .invoice-sticky-actions {
        margin-left: -0.75rem;
        margin-right: -0.75rem;
        padding-left: 0.75rem;
        padding-right: 0.75rem;
        flex-wrap: nowrap;
        align-items: center;
    }
    .invoice-sticky-actions h1 {
        font-size: 1.15rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 42vw;
    }
    .invoice-sticky-actions .muted {
        display: none;
    }
    .invoice-sticky-actions .toolbar {
        flex-wrap: nowrap;
        margin-left: auto;
    }
    .invoice-sticky-actions .toolbar .btn {
        width: auto;
        white-space: nowrap;
        padding: 0.45rem 0.7rem;
        font-size: 0.82rem;
    }
}
.dropdown-item:hover { background-color: var(--nav-hover) !important; }

/* Product catalog — matches app card / btn / table styling */
.catalog-view { margin-bottom: 1.25rem; }
.catalog-tabs {
    display: flex;
    gap: 0.25rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid var(--border);
}
.catalog-tab-btn {
    padding: 0.55rem 1rem;
    border: 0;
    border-bottom: 2px solid transparent;
    background: transparent;
    color: var(--muted);
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    margin-bottom: -1px;
    transition: color 0.15s ease, border-color 0.15s ease;
}
.catalog-tab-btn:hover:not(.is-active) {
    color: var(--text);
}
.catalog-tab-btn.is-active {
    color: var(--text);
    border-bottom-color: var(--purple);
}
.catalog-tab-count {
    margin-left: 0.25rem;
    font-size: 0.82rem;
    font-weight: 500;
    color: var(--muted);
}
.catalog-tab-btn.is-active .catalog-tab-count {
    color: var(--purple);
}
.catalog-added-panel {
    min-width: 0;
}
.catalog-empty {
    padding: 2rem 1rem;
    text-align: center;
    color: var(--muted);
    font-size: 0.92rem;
    border: 1px dashed var(--border);
    border-radius: 8px;
}
.catalog-layout {
    display: flex;
    gap: 1.25rem;
    align-items: flex-start;
}
.catalog-sidebar {
    width: 168px;
    flex-shrink: 0;
}
.catalog-sidebar .nav-section { margin-top: 0; }
.catalog-cat-btn {
    display: block;
    width: 100%;
    text-align: left;
    padding: 0.42rem 0.65rem;
    margin-bottom: 0.2rem;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: transparent;
    color: var(--text);
    font-size: 0.88rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}
.catalog-cat-btn:hover:not(.is-active) {
    background: var(--nav-hover);
    text-decoration: none;
}
.catalog-cat-btn.is-active {
    background: var(--purple);
    color: #fff;
    border-color: var(--purple);
    font-weight: 600;
}
.catalog-products {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
    background: var(--panel);
}
.catalog-product-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.38rem 0.7rem;
    border: 0;
    border-bottom: 1px solid var(--border);
    background: transparent;
}
.catalog-product-row:last-child {
    border-bottom: 0;
}
.catalog-product-info {
    flex: 1;
    min-width: 0;
}
.catalog-product-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text);
    margin: 0;
    line-height: 1.25;
}
.catalog-product-desc {
    font-size: 0.72rem;
    color: var(--muted);
    margin: 0.12rem 0 0;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 42rem;
}
.catalog-product-actions {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    flex-shrink: 0;
}
.catalog-price {
    font-weight: 600;
    font-size: 0.88rem;
    color: var(--text);
    min-width: 4.5rem;
    text-align: right;
    white-space: nowrap;
}
.catalog-qty-field {
    width: 52px;
    flex-shrink: 0;
}
.catalog-qty-field label {
    display: block;
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 0.12rem;
}
.catalog-qty-field input {
    width: 100%;
    margin-bottom: 0;
    padding: 0.32rem 0.4rem;
    font-size: 0.82rem;
    border-radius: 6px;
}
.catalog-unit-field {
    width: 3.25rem;
    flex-shrink: 0;
}
.catalog-unit-field label {
    display: block;
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 0.12rem;
}
.catalog-unit-value {
    display: block;
    padding: 0.32rem 0;
    font-size: 0.66rem;
    font-weight: 600;
    color: var(--muted);
    line-height: 1.15;
}
.catalog-add-btn {
    padding: 0.38rem 0.7rem;
    font-size: 0.82rem;
    white-space: nowrap;
}
.catalog-added-mark {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 4.5rem;
    height: 2rem;
    padding: 0.38rem 0.7rem;
    border-radius: 6px;
    background: #166534;
    color: #fff;
    font-size: 0.95rem;
    line-height: 1;
    flex-shrink: 0;
    box-sizing: border-box;
}
.catalog-remove-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    padding: 0;
    border: 0;
    border-radius: 6px;
    background: var(--red, #dc2626);
    color: #fff;
    font-size: 1.05rem;
    line-height: 1;
    cursor: pointer;
    flex-shrink: 0;
    box-shadow: none;
}
.catalog-remove-btn:hover {
    filter: brightness(0.92);
}
.catalog-back-btn {
    color: var(--role-text);
    background: var(--role-bg);
    border: 1.5px solid var(--accent);
    font-weight: 600;
    box-shadow: var(--btn-shadow);
}
.catalog-back-btn:hover {
    color: var(--role-text);
    background: var(--role-bg);
    border-color: var(--accent);
    filter: brightness(1.08);
}
.invoice-a4.is-print-preview input.ghost:not(.paper-editable),
.invoice-a4.is-print-preview textarea:not(.paper-editable) {
    pointer-events: none;
    border: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
    padding: 0;
}
.invoice-a4.is-print-preview input.ghost.paper-editable,
.invoice-a4.is-print-preview textarea.ghost.paper-editable {
    pointer-events: auto;
    border: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
    padding: 0;
}
.invoice-a4.is-print-preview input.ghost.paper-editable:focus,
.invoice-a4.is-print-preview textarea.ghost.paper-editable:focus {
    background: rgba(255, 251, 235, 0.4) !important;
    box-shadow: 0 0 0 1px rgba(252, 211, 77, 0.7) !important;
}
.inv-term-row .val input.ghost {
    font-weight: 400;
}
.inv-sig-val input.ghost {
    font-size: 0.9rem;
}
.inv-lines input.ghost.paper-editable {
    font-size: 12px;
    text-align: right;
    font-variant-numeric: tabular-nums;
}
.inv-tot-row input.ghost.paper-editable {
    width: 2.75rem;
    display: inline-block;
    text-align: center;
    font-variant-numeric: tabular-nums;
}
.invoice-a4.is-print-preview .inv-lines td,
.invoice-a4.is-print-preview .inv-lines th {
    border-bottom-color: #d4d4d4;
}
@media (max-width: 900px) {
    .catalog-layout { flex-direction: column; }
    .catalog-sidebar { width: 100%; }
    .catalog-products { width: 100%; }
    .catalog-product-row {
        flex-wrap: wrap;
        align-items: flex-start;
    }
    .catalog-product-actions {
        width: 100%;
        justify-content: flex-end;
        flex-wrap: wrap;
    }
}
</style>
@endpush

<div
    id="{{ $editorId }}"
    x-data="invoiceEditor(@js($document), {{ $readOnly ? 'true' : 'false' }}, {{ isset($invoiceId) && $invoiceId ? (int) $invoiceId : 'null' }}, @js($invoiceStatus ?? ($status ?? null)), @js($catalogProducts), @js($catalogCategories), @js($canViewPrices), @js($canEditPrices), @js($customerOptions))"
    x-cloak
>
    @if ($formAction)
    <form method="POST" action="{{ $formAction }}" @submit="if(prepareSubmit($event, 'issued') === false) { $event.preventDefault(); return false; } submitting = true;">
        @csrf
        @if (strtoupper($formMethod) !== 'POST')
            @method($formMethod)
        @endif
        <input type="hidden" name="document_json" id="invoice-document-json" value="">
        <input type="hidden" name="amount" id="invoice-amount-field" value="">
        <input type="hidden" name="sales_order_id" value="{{ $salesOrderId ?? '' }}">
        <input type="hidden" name="status" id="invoice-status-field" value="draft">

        <div class="page-head invoice-sticky-actions no-print" x-show="!showCatalog">
            <div>
                <h1>{{ $pageTitle ?? 'Order' }}</h1>
                @if (!empty($pageDescription))
                    <p class="muted" style="margin:.35rem 0 0">{{ $pageDescription }}</p>
                @endif
            </div>
            <div class="toolbar">
                @if (($invoiceStatus ?? null) === 'approved')
                    <span class="badge" style="background:#166534;color:#bbf7d0">Approved</span>
                @endif
                @if ($readOnly)
                    <a class="btn ghost" href="{{ route('invoices.index') }}">Back to log</a>
                @else
                    <button type="button" class="btn catalog-back-btn" x-show="doc.customer.is_valid" @click="showCatalog = true">&larr; Back to products</button>
                    <a class="btn ghost" href="{{ route('invoices.index') }}" x-show="!doc.customer.is_valid">Back to log</a>
                @endif
                @if ($readOnly && $canViewPrices)
                    <button type="button" class="btn ghost" @click="printDoc()">Print</button>
                @elseif (!$readOnly)
                    <button type="submit" class="btn">Save</button>
                @endif
            </div>
        </div>

        @unless ($readOnly)
        <div class="card no-print" style="margin-bottom:1rem">
            <h2 style="margin:0 0 1rem;font-size:1.05rem">Order details</h2>
            <div>
                <div x-data="{ open: false }" @click.outside="open = false" style="position: relative;">
                    <label>Select customer</label>
                    <input type="text" id="customer-name-input" x-model="doc.customer.name" @focus="open = true" @input="open = true; onCustomerInput()" autocomplete="off" placeholder="Select customer">
                    <div x-show="open && filteredCustomers.length > 0" style="position: absolute; top: 100%; left: 0; right: 0; max-height: 200px; overflow-y: auto; background: var(--panel); border: 1px solid var(--border); z-index: 10; border-radius: 4px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <template x-for="c in filteredCustomers" :key="c.id">
                            <div class="dropdown-item" @click="selectCustomer(c); open = false" x-text="c.name" style="padding: 0.5rem 0.8rem; cursor: pointer; border-bottom: 1px solid var(--border); color: var(--text);"></div>
                        </template>
                    </div>
                </div>

            </div>


        </div>
        @endunless
    @endif

    <div x-show="showCatalog && !readOnly && doc.customer.is_valid" class="catalog-view card no-print">
        <div class="page-head" style="margin-bottom:1rem">
            <div>
                <h1 style="margin:0;font-size:1.35rem">Select products</h1>
                <p class="muted" style="margin:0.35rem 0 0">Pick items to add to the order paper.</p>
            </div>
            <button type="button" class="btn" @click="showCatalog = false">View paper &rarr;</button>
        </div>

        <div class="catalog-tabs">
            <button
                type="button"
                class="catalog-tab-btn"
                :class="{ 'is-active': catalogTab === 'browse' }"
                @click="catalogTab = 'browse'"
            >All products</button>
            <button
                type="button"
                class="catalog-tab-btn"
                :class="{ 'is-active': catalogTab === 'added' }"
                @click="catalogTab = 'added'"
            >
                Added items
                <span class="catalog-tab-count" x-show="addedLineCount > 0" x-text="'(' + addedLineCount + ')'"></span>
            </button>
        </div>

        <div x-show="catalogTab === 'browse'" class="catalog-layout">
            <aside class="catalog-sidebar">
                <div class="nav-section">Categories</div>
                <button
                    type="button"
                    class="catalog-cat-btn"
                    :class="{ 'is-active': selectedCategory === null }"
                    @click="selectedCategory = null"
                >All products</button>
                <template x-for="cat in catalogCategories" :key="cat">
                    <button
                        type="button"
                        class="catalog-cat-btn"
                        :class="{ 'is-active': selectedCategory === cat }"
                        @click="selectedCategory = cat"
                        x-text="cat"
                    ></button>
                </template>
            </aside>

            <div class="catalog-products">
                <template x-for="product in filteredProducts" :key="product.id">
                    <div
                        x-data="{ qty: '', pieces: '' }"
                        x-init="if (isProductOnPaper(product)) { const line = doc.lines[findProductLineIndex(product)]; if (line) { qty = line.quantity != null && line.quantity !== '' ? Number(line.quantity) : ''; pieces = line.unit_count != null && line.unit_count !== '' ? Number(line.unit_count) : ''; } }"
                        class="catalog-product-row"
                    >
                        <div class="catalog-product-info">
                            <p class="catalog-product-name" x-text="product.name"></p>
                            <p class="catalog-product-desc" x-show="product.description" x-text="product.description"></p>
                        </div>
                        <div class="catalog-product-actions">
                            @if($canViewPrices)
                            <div class="catalog-price" x-text="money(product.unit_price) + ' ETB'"></div>
                            @endif
                            <div class="catalog-unit-field">
                                <label>Unit</label>
                                <span class="catalog-unit-value" x-text="formatUnitLabel(product.unit)"></span>
                            </div>
                            <div class="catalog-qty-field">
                                <label>Qty</label>
                                <input type="number" x-model.number="qty" min="1">
                            </div>
                            <div class="catalog-qty-field">
                                <label>Pieces</label>
                                <input type="number" x-model.number="pieces" min="1" placeholder="-">
                            </div>
                            <button type="button" class="btn catalog-add-btn" x-show="!isProductFlashing(product)" @click="addProductFromCatalog(product, qty, pieces)">Add &rarr;</button>
                            <span class="catalog-added-mark" x-show="isProductFlashing(product)" aria-label="Added">&#10003;</span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="catalogTab === 'added'" class="catalog-added-panel">
            <p x-show="addedLineCount === 0" class="catalog-empty">No items added yet. Switch to All products to add items.</p>
            <div class="catalog-products" x-show="addedLineCount > 0">
                <template x-for="entry in addedLineEntries" :key="'added-' + entry.index + '-' + (entry.line.product_id || entry.line.name)">
                    <div class="catalog-product-row">
                        <div class="catalog-product-info">
                            <p class="catalog-product-name" x-text="entry.line.name"></p>
                            <p class="catalog-product-desc" x-show="entry.line.description" x-text="entry.line.description"></p>
                        </div>
                        <div class="catalog-product-actions">
                            @if($canViewPrices)
                            <div class="catalog-qty-field" style="width:4.75rem">
                                <label>Price</label>
                                @if($canEditPrices)
                                <input type="number" step="0.01" min="0" x-model="entry.line.unit_price">
                                @else
                                <span class="catalog-price" style="display:block;padding:0.32rem 0" x-text="money(entry.line.unit_price)"></span>
                                @endif
                            </div>
                            @endif
                            <div class="catalog-unit-field">
                                <label>Unit</label>
                                <span class="catalog-unit-value" x-text="formatUnitLabel(entry.line.uom_code)"></span>
                            </div>
                            <div class="catalog-qty-field">
                                <label>Qty</label>
                                <input type="number" x-model.number="entry.line.quantity" min="1">
                            </div>
                            <div class="catalog-qty-field">
                                <label>Pieces</label>
                                <input type="number" x-model.number="entry.line.unit_count" min="1" placeholder="-">
                            </div>
                            <button type="button" class="catalog-remove-btn" aria-label="Remove" @click="removeAddedLine(entry.index)">&times;</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <div class="invoice-desk" x-show="(!showCatalog && doc.customer.is_valid) || readOnly">
        <div class="invoice-a4 @unless($canViewPrices) is-advisor-paper @endunless" id="invoice-a4-sheet" :class="{ 'is-print-preview': paperReadOnly }">
                <div class="inv-head">
                    <img class="inv-logo" src="{{ $invoiceLogoSrc }}" alt="SNS">
                    <h1 class="inv-title" x-text="docTitle"></h1>
                </div>

                <div class="inv-supplier-meta">
                    <div class="inv-supplier">
                        <div class="name" x-text="doc.supplier.name"></div>
                        <div class="addr" x-text="doc.supplier.address_line"></div>
                    </div>
                    <div class="inv-meta">
                        <div class="inv-meta-row">
                            <span class="lbl">Order Id:</span>
                            <span class="val" x-text="doc.doc_number || 'Auto-generated'"></span>
                        </div>
                        <div class="inv-meta-row">
                            <span class="lbl">Date:</span>
                            <span class="val" style="font-weight:600" x-text="formatDate(doc.doc_date)"></span>
                        </div>
                        <template x-if="doc.doc_type === 'CREDIT_NOTE'">
                            <div class="inv-meta-row">
                                <span class="lbl">Against Order:</span>
                                <span class="val" x-text="doc.parent_doc_number || ''"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="inv-customer">
                    <div class="cap" style="font-weight:bold;">Customer:</div>
                    <div style="font-size:13px; line-height:1.5;">
                        <span class="cname" x-text="doc.customer.name"></span><template x-if="doc.customer.address_line"><span>, <span class="caddr" x-text="doc.customer.address_line"></span></span></template>
                    </div>
                </div>

                <table class="inv-lines">
                    <thead>
                    <tr>
                        <th @if($canViewPrices) style="width:2rem" @endif>#</th>
                        <th>Name</th>
                        <th @if($canViewPrices) style="width:3.5rem" @endif>Unit</th>
                        <th @if($canViewPrices) class="r" style="width:4rem" @endif>Qty</th>
                        <th @if($canViewPrices) class="r" style="width:4rem" @endif>Pieces</th>
                        @if($canViewPrices)
                        <th class="r" style="width:6rem">Unit Price</th>
                        <th class="r" style="width:7rem">Total Price</th>
                        @endif
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="(line, index) in doc.lines" :key="index">
                        <tr>
                            <td class="muted" x-text="index + 1"></td>
                            <td><div class="litem"><strong class="lname" x-text="line.name"></strong><span class="lsep" x-show="line.name"> : </span><span class="ldesc" x-text="line.description"></span></div></td>
                            <td x-text="formatUnitLabel(line.uom_code)"></td>
                            <td @if($canViewPrices) class="r" @endif x-text="line.quantity"></td>
                            <td @if($canViewPrices) class="r" @endif x-text="line.unit_count ?? ''"></td>
                            @if($canViewPrices)
                            <td class="r">
                                @if($canEditPrices)
                                <template x-if="paperFieldsEditable">
                                    <input type="number" step="0.01" min="0" class="ghost paper-editable" x-model="line.unit_price" style="width:5.5rem">
                                </template>
                                <template x-if="!paperFieldsEditable">
                                    <span x-text="money(line.unit_price)"></span>
                                </template>
                                @else
                                <span x-text="money(line.unit_price)"></span>
                                @endif
                            </td>
                            <td class="r" style="font-variant-numeric:tabular-nums;white-space:nowrap" x-text="money(lineTotal(index))"></td>
                            @endif
                        </tr>
                    </template>
                    </tbody>
                </table>

                <div class="inv-bottom">
                    <div class="inv-notes">
                        <div class="block">
                            <div class="cap">Notes:</div>
                            <template x-if="paperFieldsEditable">
                                <textarea class="ghost paper-editable @unless($canViewPrices) advisor-notes-field @endunless" x-model="notesText" rows="{{ $canViewPrices ? 3 : 2 }}" style="min-height:{{ $canViewPrices ? '3rem' : '1.4rem' }};white-space:pre-wrap;display:block" :placeholder="notesPlaceholder"></textarea>
                            </template>
                            <template x-if="!paperFieldsEditable">
                                <div style="min-height:3rem;white-space:pre-wrap;display:block" x-text="(doc.notes || []).filter(n => String(n).trim()).join('\n') || '—'"></div>
                            </template>
                        </div>
                        <div>
                            <div class="cap">Terms & Conditions:</div>
                            <div class="inv-term-row">
                                <span class="lbl">Payment:</span>
                                <span class="val">
                                    <template x-if="paperFieldsEditable">
                                        <input type="text" class="ghost paper-editable" x-model="doc.terms.payment" placeholder="—">
                                    </template>
                                    <template x-if="!paperFieldsEditable">
                                        <span x-text="doc.terms.payment || '—'"></span>
                                    </template>
                                </span>
                            </div>
                            <div class="term-title" style="margin-bottom:.25rem">Delivery:</div>
                            <div class="inv-term-row inv-term-indent">
                                <span class="lbl">Place:</span>
                                <span class="val">
                                    <template x-if="paperFieldsEditable">
                                        <input type="text" class="ghost paper-editable" x-model="doc.terms.delivery_place" placeholder="—">
                                    </template>
                                    <template x-if="!paperFieldsEditable">
                                        <span x-text="doc.terms.delivery_place || '—'"></span>
                                    </template>
                                </span>
                            </div>
                            <div class="inv-term-row inv-term-indent">
                                <span class="lbl">Time:</span>
                                <span class="val">
                                    <span class="term-unit-field">
                                        <template x-if="paperFieldsEditable">
                                            <input type="text" class="ghost paper-editable term-unit-input" x-model="doc.terms.delivery_days" placeholder="—">
                                        </template>
                                        <template x-if="!paperFieldsEditable">
                                            <span x-text="termDisplayUnit(doc.terms.delivery_days, 'days')"></span>
                                        </template>
                                        <span class="term-unit-suffix" x-show="paperFieldsEditable">days</span>
                                    </span>
                                </span>
                            </div>
                            <div class="inv-term-row inv-term-indent">
                                <span class="lbl">Validity:</span>
                                <span class="val">
                                    <span class="term-unit-field">
                                        <template x-if="paperFieldsEditable">
                                            <input type="text" class="ghost paper-editable term-unit-input" x-model="doc.terms.validity_days" placeholder="—">
                                        </template>
                                        <template x-if="!paperFieldsEditable">
                                            <span x-text="termDisplayUnit(doc.terms.validity_days, 'days')"></span>
                                        </template>
                                        <span class="term-unit-suffix" x-show="paperFieldsEditable">days</span>
                                    </span>
                                </span>
                            </div>
                            <div class="inv-term-row">
                                <span class="lbl">Warranty:</span>
                                <span class="val">
                                    <span class="term-unit-field">
                                        <template x-if="paperFieldsEditable">
                                            <input type="text" class="ghost paper-editable term-unit-input" x-model="doc.terms.warranty" placeholder="—">
                                        </template>
                                        <template x-if="!paperFieldsEditable">
                                            <span x-text="termDisplayUnit(doc.terms.warranty, 'years')"></span>
                                        </template>
                                        <span class="term-unit-suffix" x-show="paperFieldsEditable">years</span>
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                    @if($canViewPrices)
                    <div class="inv-totals">
                        <div class="inv-tot-row">
                            <span class="lbl">Total</span>
                            <span class="amt" x-text="money(totals.subtotal) + ' ' + (doc.currency || 'ETB')"></span>
                        </div>
                        <div class="inv-tot-row">
                            <span class="lbl">
                                Discount (
                                @if($canEditPrices)
                                <template x-if="paperFieldsEditable">
                                    <input type="number" step="0.01" min="0" max="100" class="ghost paper-editable" x-model="doc.discount.value">
                                </template>
                                <template x-if="!paperFieldsEditable">
                                    <span x-text="doc.discount?.value || '0'"></span>
                                </template>
                                @else
                                <span x-text="doc.discount?.value || '0'"></span>
                                @endif
                                %)
                            </span>
                            <span class="amt" x-text="money(totals.discountAmount) + ' ' + (doc.currency || 'ETB')"></span>
                        </div>
                        <div class="inv-tot-row">
                            <span class="lbl">S. Total</span>
                            <span class="amt" x-text="money(totals.afterDiscount) + ' ' + (doc.currency || 'ETB')"></span>
                        </div>
                        <div class="inv-tot-row">
                            <span class="lbl">VAT <span x-text="doc.tax?.rate || '15'"></span>%</span>
                            <span class="amt" x-text="money(totals.taxAmount) + ' ' + (doc.currency || 'ETB')"></span>
                        </div>
                        <div class="inv-tot-row inv-grand">
                            <span class="lbl">G. Total</span>
                            <span class="amt" x-text="money(totals.grandTotal) + ' ' + (doc.currency || 'ETB')"></span>
                        </div>
                    </div>
                    @endif
                </div>

                <template x-if="doc.doc_type === 'TAX_INVOICE' || doc.doc_type === 'CREDIT_NOTE'">
                    <div class="inv-words no-print">Amount in words: — will appear when printed</div>
                </template>

                <div class="inv-sigs">
                    <div class="inv-sig-col">
                        <div class="inv-sig-val">
                            <template x-if="paperFieldsEditable">
                                <input type="text" id="prepared-by-name-input" class="ghost paper-editable" x-model="doc.prepared_by.name" placeholder="—">
                            </template>
                            <template x-if="!paperFieldsEditable">
                                <span x-text="personName(doc.prepared_by.name)"></span>
                            </template>
                        </div>
                        <div class="inv-sig-lbl">Prepared By</div>
                    </div>
                    <div class="inv-sig-col">
                        <div class="inv-sig-val">
                            <template x-if="paperFieldsEditable">
                                <input type="text" id="prepared-by-phone-input" class="ghost paper-editable" x-model="doc.prepared_by.phone" placeholder="—">
                            </template>
                            <template x-if="!paperFieldsEditable">
                                <span x-text="doc.prepared_by.phone"></span>
                            </template>
                        </div>
                        <div class="inv-sig-lbl">Phone Number</div>
                    </div>
                    <div class="inv-sig-col">
                        <div class="inv-sig-val">
                            <span x-text="personName(doc.approved_by.name)"></span>
                        </div>
                        <div class="inv-sig-lbl">Approved By</div>
                    </div>
                    <div class="inv-sig-col">
                        <div class="inv-sig-val">
                            <span x-text="doc.approved_by.phone"></span>
                        </div>
                        <div class="inv-sig-lbl">Contact</div>
                    </div>
                </div>
            </div>
        </div>


    @if ($formAction)
    </form>
    @endif
</div>

@push('scripts')
<script>
function invoiceEditor(initialDoc, readOnly, invoiceId, invoiceStatus, catalogProducts, catalogCategories, canViewPrices, canEditPrices, customerCatalog) {
    const customers = Array.isArray(customerCatalog) ? customerCatalog : [];
    const DEFAULT_UNIT = @js(\App\Support\UnitOfMeasure::DEFAULT);
    const snsSupplier = @js(\App\Services\DocumentService::SNS_SUPPLIER);
    const formatUnitLabel = (unit) => {
        const value = String(unit || '').trim().toLowerCase();
        if (!value || value === 'pc' || value === 'pcs' || value === 'piece') {
            return DEFAULT_UNIT;
        }
        return String(unit || '').trim();
    };
    const today = @js(date('Y-m-d'));
    const autosaveCreateUrl = @json(route('invoices.autosave'));
    const salesOrderId = @json($salesOrderId ?? '');
    const invoicesBaseUrl = @json(url('/finance/invoices'));

    const doc = initialDoc || {};
    if (!doc.supplier) doc.supplier = { name: '', address_line: '' };
    if (!doc.customer) doc.customer = { id: null, name: '', address_line: '', phone: '' };
    if (!doc.terms) doc.terms = { payment: '', delivery_place: '', delivery_days: '', validity_days: '', warranty: '' };
    if (!doc.discount) doc.discount = { type: 'PERCENT', value: '0' };
    if (!doc.tax) doc.tax = { rate: '15' };
    if (!doc.prepared_by) doc.prepared_by = { name: '', phone: '' };
    if (!doc.approved_by) doc.approved_by = { name: '', phone: '' };
    if (!Array.isArray(doc.lines) || !doc.lines.length) {
        doc.lines = [{ line_no: 1, name: '', description: '', uom_code: DEFAULT_UNIT, quantity: '1.000', unit_count: null, unit_price: '0.00' }];
    } else {
        doc.lines.forEach((line) => {
            if (line && typeof line === 'object') {
                line.uom_code = formatUnitLabel(line.uom_code);
            }
        });
    }
    if (!Array.isArray(doc.notes)) doc.notes = [];

    // Autofill locked header fields for create/edit forms.
    if (! readOnly) {
        doc.doc_type = 'PROFORMA';
        const isValidDate = (v) => /^\d{4}-\d{2}-\d{2}$/.test(String(v || ''));
        // Defaults: both dates = today unless already set (edit) or changed manually.
        if (! isValidDate(doc.doc_date)) doc.doc_date = today;
        if (! isValidDate(doc.valid_until)) doc.valid_until = today;
        doc.supplier = {
            name: snsSupplier.name,
            address_line: snsSupplier.address_line,
            tin: snsSupplier.tin || '',
            vat_reg_no: snsSupplier.vat_reg_no || '',
            phone: snsSupplier.phone || '',
        };
        doc.prepared_by = {
            name: String(doc.prepared_by?.name || '').trim(),
            phone: String(doc.prepared_by?.phone || '').trim(),
        };
        if (! String(doc.approved_by?.name || '').trim()) {
            doc.approved_by = { name: '', phone: '' };
        }
    }
    return {
        doc,
        formatUnitLabel,
        canViewPrices: !!canViewPrices,
        canEditPrices: !!canEditPrices,
        readOnly: readOnly,
        invoiceId: invoiceId || null,
        invoiceStatus: invoiceStatus || null,
        autosaveUrl: invoiceId ? (invoicesBaseUrl + '/' + invoiceId + '/autosave') : autosaveCreateUrl,
        dirty: false,
        saving: false,
        submitting: false,
        autosaveTimer: null,
        baselineJson: '',
        _autosaveChain: Promise.resolve(),
        _leaveSaving: false,
        _lastServerSyncAt: 0,
        _localDraftTimer: null,
        get canAutosave() {
            if (this.readOnly || this.submitting) return false;
            return !this.invoiceId || this.invoiceStatus === 'draft' || this.invoiceStatus === null;
        },
        get notesText() {
            return (this.doc.notes || []).join('\n');
        },
        set notesText(value) {
            this.doc.notes = String(value || '').split(/\r?\n/);
        },
        get notesPlaceholder() {
            if (!this.invoiceStatus || this.invoiceStatus === 'draft') {
                return 'Provide notes';
            }
            return '';
        },
        get docTitle() {
            return this.doc.doc_type === 'CREDIT_NOTE' ? 'Credit Note' : 'Proforma Invoice';
        },
        get paperReadOnly() {
            return this.readOnly || !this.showCatalog;
        },
        get paperFieldsEditable() {
            return !this.readOnly && !this.showCatalog;
        },
        get discountLabel() {
            return (this.doc.discount?.type || 'PERCENT') === 'PERCENT'
                ? ((this.doc.discount?.value || '0') + '% Discount')
                : 'Discount';
        },
        get totals() {
            const lines = this.doc.lines || [];
            const lineTotals = lines.map(l => this.round2((Number(l.quantity)||0) * (Number(l.unit_price)||0)));
            const subtotal = this.round2(lineTotals.reduce((a,b)=>a+b,0));
            const type = (this.doc.discount?.type || 'PERCENT').toUpperCase();
            const value = Number(this.doc.discount?.value) || 0;
            const discountAmount = type === 'AMOUNT' ? this.round2(value) : this.round2(subtotal * value / 100);
            const afterDiscount = this.round2(subtotal - discountAmount);
            const taxRate = Number(this.doc.tax?.rate) || 0;
            const taxAmount = this.round2(afterDiscount * taxRate / 100);
            const grandTotal = this.round2(afterDiscount + taxAmount);
            return { lineTotals, subtotal, discountAmount, afterDiscount, taxAmount, grandTotal };
        },
        showCatalog: !readOnly,
        catalogTab: 'browse',
        productAddFlash: {},
        _productAddFlashTimers: {},
        selectedCategory: null,
        catalogProducts: catalogProducts || [],
        catalogCategories: catalogCategories || [],
        get addedLineEntries() {
            return (this.doc.lines || [])
                .map((line, index) => ({ line, index }))
                .filter(({ line }) => Boolean(String(line.name || '').trim()) || Boolean(String(line.description || '').trim()));
        },
        get addedLineCount() {
            return this.addedLineEntries.length;
        },
        get filteredProducts() {
            if (!this.selectedCategory) return this.catalogProducts;
            return this.catalogProducts.filter(p => p.category === this.selectedCategory);
        },
        findProductLineIndex(product) {
            return (this.doc.lines || []).findIndex((line) => (
                (line.product_id != null && line.product_id === product.id)
                || (line.product_id == null && line.name === product.name)
            ));
        },
        isProductOnPaper(product) {
            return this.findProductLineIndex(product) !== -1;
        },
        isProductFlashing(product) {
            return Boolean(this.productAddFlash[product.id]);
        },
        flashProductAdded(product) {
            const id = product.id;
            if (this._productAddFlashTimers[id]) {
                clearTimeout(this._productAddFlashTimers[id]);
            }
            this.productAddFlash = { ...this.productAddFlash, [id]: true };
            this._productAddFlashTimers[id] = setTimeout(() => {
                const next = { ...this.productAddFlash };
                delete next[id];
                this.productAddFlash = next;
                delete this._productAddFlashTimers[id];
            }, 1000);
        },
        isValidCatalogQty(value) {
            if (value === '' || value === null || value === undefined) {
                return false;
            }
            const n = Number(value);
            return Number.isFinite(n) && n >= 1;
        },
        addProductFromCatalog(product, qty, pieces) {
            if (!this.isValidCatalogQty(qty) || !this.isValidCatalogQty(pieces)) {
                window.erpToast('<b>Qty and Pieces must both be 1 or greater.</b>', 'warn');
                return;
            }
            this.addProductToPaper(product, qty, pieces);
            this.flashProductAdded(product);
        },
        addProductToPaper(product, qty = 1, pieces = null) {
            const lineData = {
                product_id: product.id,
                name: product.name,
                description: product.description || '',
                quantity: Number(qty),
                unit_count: Number(pieces),
                unit_price: product.unit_price,
                uom_code: formatUnitLabel(product.unit),
            };
            const existingIndex = this.findProductLineIndex(product);
            if (existingIndex !== -1) {
                Object.assign(this.doc.lines[existingIndex], lineData);
                this.queueImmediateAutosave();
                return;
            }
            if (this.doc.lines.length === 1 && !this.doc.lines[0].name && !this.doc.lines[0].description && this.doc.lines[0].quantity == 1) {
                this.doc.lines = [];
            }
            this.doc.lines.push(lineData);
            this.queueImmediateAutosave();
        },
        removeProductFromPaper(product) {
            const index = this.findProductLineIndex(product);
            if (index === -1) {
                return;
            }
            this.removeAddedLine(index);
        },
        removeAddedLine(index) {
            if (!Array.isArray(this.doc.lines) || index < 0 || index >= this.doc.lines.length) {
                return;
            }
            this.doc.lines.splice(index, 1);
            this.doc.lines.forEach((line, idx) => {
                line.line_no = idx + 1;
            });
            this.queueImmediateAutosave();
        },
        normalizeLinesForSave() {
            const lines = Array.isArray(this.doc.lines) ? this.doc.lines : [];
            const namedLines = lines.filter((line) => String(line?.name || '').trim() !== '');
            if (namedLines.length > 0) {
                this.doc.lines = namedLines;
            } else if (lines.length === 0) {
                this.doc.lines = [{ line_no: 1, name: '', description: '', uom_code: DEFAULT_UNIT, quantity: '1.000', unit_count: null, unit_price: '0.00' }];
            }
        },
        isNavigatingAway(href) {
            const current = new URL(window.location.href);
            const target = new URL(href, window.location.href);
            if (target.origin !== current.origin) {
                return false;
            }
            if (target.pathname === current.pathname && target.search === current.search && target.hash) {
                return false;
            }

            return target.href !== current.href;
        },
        queueImmediateAutosave() {
            if (!this.canAutosave || this.readOnly) {
                return;
            }
            clearTimeout(this.autosaveTimer);
            this.autosaveTimer = null;
            this.syncDirtyState();
            this.dirty = true;
            this.persistLocalDraft();
            this.flushAutosave(false);
        },
        get filteredCustomers() {
            const term = String(this.doc.customer?.name || '').trim().toLowerCase();
            if (!term) return customers;
            return customers.filter(c => String(c.name || '').toLowerCase().startsWith(term));
        },
        findCustomerMatch() {
            const c = this.doc.customer || {};
            if (c.id) {
                const byId = customers.find(p => p.id === c.id);
                if (byId) return byId;
            }
            const name = String(c.name || '').trim();
            if (!name) return null;
            return customers.find(p => p.name === name) || null;
        },
        applyCustomerParty(party) {
            if (!party) return false;
            this.doc.customer = {
                id: party.id,
                name: party.name,
                address_line: party.address || '',
                phone: party.phone || '',
                is_valid: true,
            };
            return true;
        },
        selectCustomer(party) {
            this.applyCustomerParty(party);
            this.showCatalog = true;
            if (this.canAutosave) {
                clearTimeout(this.autosaveTimer);
                this.dirty = true;
                this.persistLocalDraft();
                this.flushAutosave(false);
            }
        },
        onCustomerInput() {
            this.doc.customer.is_valid = false;
            this.doc.customer.id = null;
            this.doc.customer.address_line = '';
            this.doc.customer.phone = '';
        },
        resolveCustomerFromCatalog() {
            return this.applyCustomerParty(this.findCustomerMatch());
        },
        syncDirtyState() {
            this.dirty = JSON.stringify(this.doc) !== this.baselineJson;
            return this.dirty;
        },
        hasDraftContent() {
            if (String(this.doc.customer?.name || '').trim()) {
                return true;
            }
            if (this.doc.customer?.is_valid || this.findCustomerMatch()) {
                return true;
            }
            if ((this.doc.lines || []).some((line) => String(line?.name || '').trim() !== '')) {
                return true;
            }
            if ((this.doc.notes || []).some((note) => String(note || '').trim() !== '')) {
                return true;
            }
            if (String(this.doc.prepared_by?.name || '').trim() || String(this.doc.prepared_by?.phone || '').trim()) {
                return true;
            }

            return false;
        },
        needsDraftPersistence() {
            if (!this.canAutosave) {
                return false;
            }

            this.syncDirtyState();

            return this.dirty || this.hasDraftContent() || !!this.autosaveTimer || this.saving;
        },
        sendBeaconAutosave() {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!token || !this.autosaveUrl) {
                return false;
            }

            let payload;
            try {
                payload = this.prepareAutosavePayload();
            } catch (_) {
                return false;
            }

            const formData = new FormData();
            formData.append('_token', token);
            formData.append('document_json', payload.document_json);
            formData.append('amount', payload.amount);
            formData.append('status', payload.status);
            if (payload.sales_order_id) {
                formData.append('sales_order_id', payload.sales_order_id);
            }

            if (navigator.sendBeacon) {
                return navigator.sendBeacon(this.autosaveUrl, formData);
            }

            try {
                fetch(this.autosaveUrl, {
                    method: 'POST',
                    body: formData,
                    keepalive: true,
                    credentials: 'same-origin',
                });

                return true;
            } catch (_) {
                return false;
            }
        },
        async persistDraftBeforeLeave({ awaitServer = true, useBeacon = false } = {}) {
            if (!this.canAutosave) {
                return;
            }

            clearTimeout(this.autosaveTimer);
            this.autosaveTimer = null;
            clearTimeout(this._localDraftTimer);
            this._localDraftTimer = null;
            this.syncDirtyState();
            this.persistLocalDraft();

            if (!this.dirty && !this.hasDraftContent() && !this.saving) {
                return;
            }

            if (useBeacon) {
                this.sendBeaconAutosave();

                return;
            }

            if (awaitServer) {
                await this.flushAutosave(false, true);
            } else {
                this.flushAutosave(true, true);
            }
        },
        localDraftStorageKey() {
            const id = this.invoiceId || ('create-' + (salesOrderId || 'none'));

            return 'sns-invoice-draft:' + id;
        },
        localDraftCreateKey() {
            return 'sns-invoice-draft:create-' + (salesOrderId || 'none');
        },
        readLocalDraft(key) {
            try {
                const raw = sessionStorage.getItem(key);
                if (!raw) {
                    return null;
                }

                return JSON.parse(raw);
            } catch (_) {
                return null;
            }
        },
        persistLocalDraft() {
            if (!this.canAutosave || this.readOnly) {
                return;
            }

            const key = this.localDraftStorageKey();
            try {
                sessionStorage.setItem(key, JSON.stringify({
                    doc: JSON.parse(JSON.stringify(this.doc)),
                    invoiceId: this.invoiceId,
                    autosaveUrl: this.autosaveUrl,
                    updatedAt: Date.now(),
                    lastServerSyncAt: this._lastServerSyncAt || 0,
                }));
            } catch (_) {}
        },
        scheduleLocalDraftPersist() {
            if (!this.canAutosave || this.readOnly) {
                return;
            }
            clearTimeout(this._localDraftTimer);
            this._localDraftTimer = setTimeout(() => {
                this._localDraftTimer = null;
                this.persistLocalDraft();
            }, 200);
        },
        migrateLocalDraftKey(fromKey, toKey) {
            if (!fromKey || fromKey === toKey) {
                return;
            }
            try {
                const raw = sessionStorage.getItem(fromKey);
                if (raw) {
                    sessionStorage.setItem(toKey, raw);
                    sessionStorage.removeItem(fromKey);
                }
            } catch (_) {}
        },
        clearLocalDraft() {
            try {
                sessionStorage.removeItem(this.localDraftStorageKey());
                sessionStorage.removeItem(this.localDraftCreateKey());
            } catch (_) {}
        },
        restoreLocalDraft(serverSnapshot) {
            if (!this.canAutosave || this.readOnly) {
                return;
            }

            let stored = this.readLocalDraft(this.localDraftStorageKey());
            if (!stored && !this.invoiceId) {
                stored = this.readLocalDraft(this.localDraftCreateKey());
            }
            if (!stored || !stored.doc) {
                return;
            }

            const storedSnapshot = JSON.stringify(stored.doc);
            if (storedSnapshot === serverSnapshot) {
                return;
            }

            const storedHasContent = (stored.doc.lines || []).some((line) => String(line?.name || '').trim() !== '')
                || stored.doc.customer?.is_valid
                || (stored.doc.notes || []).some((note) => String(note || '').trim() !== '')
                || String(stored.doc.prepared_by?.name || '').trim()
                || String(stored.doc.prepared_by?.phone || '').trim();

            if (!storedHasContent) {
                return;
            }

            const restored = JSON.parse(storedSnapshot);
            Object.keys(this.doc).forEach((key) => {
                delete this.doc[key];
            });
            Object.assign(this.doc, restored);
            if (stored.invoiceId) {
                this.invoiceId = stored.invoiceId;
            }
            if (stored.autosaveUrl) {
                this.autosaveUrl = stored.autosaveUrl;
            }
            if (this.doc.customer && (this.doc.customer.name || this.doc.customer.id)) {
                this.resolveCustomerFromCatalog();
            } else if (this.doc.customer) {
                this.doc.customer.is_valid = false;
            }

            this.dirty = true;
            this.$nextTick(() => {
                this.queueImmediateAutosave();
            });
        },
        prepareAutosavePayload() {
            this.normalizeLinesForSave();
            this.resolveCustomerFromCatalog();
            const t = this.totals;
            this.doc.currency = 'ETB';
            this.doc.doc_type = 'PROFORMA';
            this.doc.doc_date = this.doc.doc_date || today;
            this.doc.valid_until = this.doc.valid_until || today;
            this.doc.discount = this.doc.discount || { type: 'PERCENT', value: '0' };
            this.doc.discount.type = 'PERCENT';
            this.doc.tax = this.doc.tax || { rate: '15' };
            this.doc.discount.amount = String(t.discountAmount.toFixed(2));
            this.doc.tax.amount = String(t.taxAmount.toFixed(2));
            this.doc.totals = {
                subtotal: String(t.subtotal.toFixed(2)),
                after_discount: String(t.afterDiscount.toFixed(2)),
                grand_total: String(t.grandTotal.toFixed(2)),
            };
            this.doc.lines = this.doc.lines.map((line, i) => ({
                ...line,
                line_no: i + 1,
                line_total: String((t.lineTotals[i] || 0).toFixed(2)),
            }));

            return {
                document_json: JSON.stringify(this.doc),
                amount: String(t.grandTotal),
                status: 'draft',
                sales_order_id: salesOrderId || '',
            };
        },
        init() {
            if (this.doc.customer && (this.doc.customer.name || this.doc.customer.id)) {
                this.resolveCustomerFromCatalog();
            } else if (this.doc.customer) {
                this.doc.customer.is_valid = false;
            }
            if (!this.canAutosave) return;
            const serverSnapshot = JSON.stringify(this.doc);
            this.restoreLocalDraft(serverSnapshot);
            this.baselineJson = JSON.stringify(this.doc);
            this.$watch('doc', () => {
                this.syncDirtyState();
                this.scheduleLocalDraftPersist();
                this.scheduleAutosave();
            }, { deep: true });
            const flushOnLeave = () => {
                if (!this.needsDraftPersistence()) {
                    return;
                }
                this.persistDraftBeforeLeave({ awaitServer: false, useBeacon: true });
            };
            window.addEventListener('pagehide', flushOnLeave);
            window.addEventListener('beforeunload', flushOnLeave);
            window.addEventListener('popstate', flushOnLeave);
            this._reloadKeyHandler = async (event) => {
                if (this.readOnly || !this.canAutosave || this.submitting || this._leaveSaving) {
                    return;
                }
                const key = String(event.key || '').toLowerCase();
                const isReload = key === 'f5' || ((event.ctrlKey || event.metaKey) && key === 'r');
                if (!isReload || event.altKey) {
                    return;
                }
                if (!this.needsDraftPersistence()) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                this._leaveSaving = true;
                try {
                    await this.persistDraftBeforeLeave({ awaitServer: true });
                } finally {
                    this._leaveSaving = false;
                }
                window.location.reload();
            };
            window.addEventListener('keydown', this._reloadKeyHandler, true);
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden' && this.needsDraftPersistence()) {
                    this.persistDraftBeforeLeave({ awaitServer: false, useBeacon: true });
                }
            });
            this._leaveClickHandler = async (event) => {
                if (this.readOnly || !this.canAutosave || this.submitting || this._leaveSaving) {
                    return;
                }
                const link = event.target.closest('a[href]');
                if (!link || link.target === '_blank' || event.defaultPrevented) {
                    return;
                }
                if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }
                if (link.hasAttribute('download')) {
                    return;
                }
                if (!this.isNavigatingAway(link.href)) {
                    return;
                }
                if (!this.needsDraftPersistence()) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                this._leaveSaving = true;
                try {
                    await this.persistDraftBeforeLeave({ awaitServer: true });
                } finally {
                    this._leaveSaving = false;
                }
                window.location.href = link.href;
            };
            document.addEventListener('click', this._leaveClickHandler, true);
            this._leaveSubmitHandler = async (event) => {
                if (this.readOnly || !this.canAutosave || this.submitting || this._leaveSaving) {
                    return;
                }
                const form = event.target;
                if (!form || form.tagName !== 'FORM' || form.closest('[x-data*="invoiceEditor"]')) {
                    return;
                }
                const action = form.getAttribute('action') || window.location.href;
                if (!this.isNavigatingAway(action)) {
                    return;
                }
                if (!this.needsDraftPersistence()) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                this._leaveSaving = true;
                try {
                    await this.persistDraftBeforeLeave({ awaitServer: true });
                } finally {
                    this._leaveSaving = false;
                }
                form.submit();
            };
            document.addEventListener('submit', this._leaveSubmitHandler, true);
        },
        scheduleAutosave() {
            if (!this.canAutosave || this.readOnly) {
                return;
            }
            if (!this.dirty && !this.hasDraftContent()) {
                return;
            }
            clearTimeout(this.autosaveTimer);
            this.autosaveTimer = setTimeout(() => {
                this.autosaveTimer = null;
                this.flushAutosave(false);
            }, 800);
        },
        buildPayload() {
            if (this.prepareSubmit({ preventDefault(){} }) === false) {
                return null;
            }

            return this.prepareAutosavePayload();
        },
        applyAutosaveResponse(data) {
            if (!data || !data.ok) return;
            const previousKey = this.localDraftStorageKey();
            this.invoiceId = data.id;
            this.invoiceStatus = 'draft';
            this.autosaveUrl = data.autosave_url || this.autosaveUrl;
            this.doc.doc_number = data.invoice_number || this.doc.doc_number;
            this._lastServerSyncAt = Date.now();
            this.migrateLocalDraftKey(previousKey, this.localDraftStorageKey());
            this.migrateLocalDraftKey(this.localDraftCreateKey(), this.localDraftStorageKey());
            this.baselineJson = JSON.stringify(this.doc);
            this.dirty = false;
            this.persistLocalDraft();
            const form = this.$el.querySelector('form');
            if (form && data.update_url) {
                form.setAttribute('action', data.update_url);
                let methodInput = form.querySelector('input[name="_method"]');
                if (!methodInput) {
                    methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    form.appendChild(methodInput);
                }
                methodInput.value = 'PUT';
            }
            if (data.edit_url && window.location.pathname.indexOf('/edit') === -1) {
                try { history.replaceState(null, '', data.edit_url); } catch (_) {}
            }
        },
        async flushAutosave(keepalive, forceLeave = false) {
            if (!this.canAutosave) {
                return;
            }
            clearTimeout(this.autosaveTimer);
            this.autosaveTimer = null;
            this.syncDirtyState();
            if (!forceLeave && !keepalive && !this.dirty) {
                return;
            }
            if ((forceLeave || keepalive) && !this.dirty && !this.hasDraftContent()) {
                return;
            }
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!token || !this.autosaveUrl) {
                return;
            }

            if (keepalive) {
                this.persistLocalDraft();
                this.sendBeaconAutosave();

                return;
            }

            const runSave = async () => {
                this.syncDirtyState();
                if (!forceLeave && !this.dirty) {
                    return;
                }
                if (forceLeave && !this.dirty && !this.hasDraftContent()) {
                    return;
                }

                const payload = this.prepareAutosavePayload();
                this.saving = true;
                try {
                    const res = await fetch(this.autosaveUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(payload),
                        credentials: 'same-origin',
                    });
                    if (!res.ok) {
                        return;
                    }
                    const data = await res.json();
                    this.applyAutosaveResponse(data);
                } catch (_) {
                    // Best-effort; user can still click Save.
                } finally {
                    this.saving = false;
                }

                this.syncDirtyState();
                if (this.dirty) {
                    await runSave();
                }
            };

            this._autosaveChain = this._autosaveChain.then(runSave).catch(() => {});

            return this._autosaveChain;
        },
        round2(n) { return Math.round((n + Number.EPSILON) * 100) / 100; },
        personName(name) {
            return String(name || '').replace(/\s*\([^)]*\)\s*$/u, '').trim();
        },
        money(n) {
            return this.round2(Number(n)||0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        lineTotal(i) { return this.totals.lineTotals[i] || 0; },
        formatDate(iso) {
            const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(iso||'').trim());
            if (!m) return iso || '';
            const d = new Date(Date.UTC(+m[1], +m[2]-1, +m[3]));
            return d.toLocaleDateString('en-US', { weekday:'short', month:'short', day:'numeric', year:'numeric', timeZone:'UTC' });
        },
        termDisplayUnit(value, unit) {
            const text = String(value ?? '').trim();
            if (!text) {
                return '—';
            }
            if (/[a-zA-Z]/.test(text)) {
                return text;
            }
            return `${text} ${unit}`;
        },
        addLine() {
            const next = (this.doc.lines.reduce((m,l)=>Math.max(m, Number(l.line_no)||0), 0)) + 1;
            this.doc.lines.push({ line_no: next, name:'', description:'', uom_code: DEFAULT_UNIT, quantity:'1.000', unit_count:null, unit_price:'0.00' });
        },
        removeLine(i) {
            if (this.doc.lines.length <= 1) return;
            this.doc.lines.splice(i,1);
            this.doc.lines.forEach((l,idx)=> l.line_no = idx+1);
        },
        setStatus(status) {
            const el = document.getElementById('invoice-status-field');
            if (el) el.value = status;
        },
        validatePreparedByForSave() {
            const name = String(this.doc.prepared_by?.name || '').trim();
            const phone = String(this.doc.prepared_by?.phone || '').trim();
            if (!name && !phone) {
                window.erpToast('<b>Please fill in Prepared By and Phone Number before saving.</b>', 'warn');
                return false;
            }
            if (!name) {
                window.erpToast('<b>Prepared By is required before saving.</b>', 'warn');
                return false;
            }
            if (!phone) {
                window.erpToast('<b>Phone Number is required before saving.</b>', 'warn');
                return false;
            }
            return true;
        },
        focusPreparedByField(field) {
            this.showCatalog = false;
            setTimeout(() => {
                const input = document.getElementById(field === 'phone' ? 'prepared-by-phone-input' : 'prepared-by-name-input');
                if (input) {
                    input.focus();
                    input.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }
            }, 10);
        },
        prepareSubmit(e, statusOverride) {
            const isFormSubmit = e && e.target && e.target.tagName === 'FORM' && !this.readOnly;
            if (isFormSubmit) {
                if (!this.doc.customer?.is_valid || !this.findCustomerMatch()) {
                    window.erpToast('<b>Please select a valid customer from the dropdown.</b>', 'error');
                    this.submitting = false;
                    setTimeout(() => {
                        const input = document.getElementById('customer-name-input');
                        if (input) input.focus();
                    }, 10);
                    return false;
                }
                if (!this.validatePreparedByForSave()) {
                    this.submitting = false;
                    const name = String(this.doc.prepared_by?.name || '').trim();
                    this.focusPreparedByField(name ? 'phone' : 'name');
                    return false;
                }
            }
            // Drafts save with any partial data — no required-field blocking.
            this.normalizeLinesForSave();
            const t = this.totals;
            this.doc.currency = 'ETB';
            this.doc.doc_type = 'PROFORMA';
            this.doc.doc_date = this.doc.doc_date || today;
            this.doc.valid_until = this.doc.valid_until || today;
            
            this.doc.discount = this.doc.discount || {};
            this.doc.discount.type = 'PERCENT';

            this.resolveCustomerFromCatalog();
            this.doc.customer = this.doc.customer || { id: null, name: '', address_line: '', phone: '' };
            this.doc.customer.id = this.doc.customer.id || null;
            this.doc.customer.name = String(this.doc.customer.name || '');
            this.doc.customer.address_line = String(this.doc.customer.address_line || '');
            this.doc.customer.phone = String(this.doc.customer.phone || '');
            this.doc.supplier = {
                name: snsSupplier.name,
                address_line: snsSupplier.address_line,
                tin: snsSupplier.tin || '',
                vat_reg_no: snsSupplier.vat_reg_no || '',
                phone: snsSupplier.phone || '',
            };
            this.doc.prepared_by = {
                name: String(this.doc.prepared_by?.name || '').trim(),
                phone: String(this.doc.prepared_by?.phone || '').trim(),
            };
            if (!String(this.doc.approved_by?.name || '').trim()) {
                this.doc.approved_by = { name: '', phone: '' };
            }
            this.doc.discount = this.doc.discount || { type: 'PERCENT', value: '0' };
            this.doc.tax = this.doc.tax || { rate: '15' };
            this.doc.discount.amount = String(t.discountAmount.toFixed(2));
            this.doc.tax.amount = String(t.taxAmount.toFixed(2));
            this.doc.totals = {
                subtotal: String(t.subtotal.toFixed(2)),
                after_discount: String(t.afterDiscount.toFixed(2)),
                grand_total: String(t.grandTotal.toFixed(2)),
            };
            this.doc.lines = this.doc.lines.map((line, i) => ({
                ...line,
                line_no: i+1,
                line_total: String((t.lineTotals[i]||0).toFixed(2)),
            }));

            const form = e && e.target ? e.target : document.querySelector('form');
            if (form && form.querySelector) {
                const jsonInput = form.querySelector('#invoice-document-json') || form.querySelector('[name="document_json"]');
                const amountInput = form.querySelector('#invoice-amount-field') || form.querySelector('[name="amount"]');
                const statusInput = form.querySelector('#invoice-status-field');
                if (jsonInput) jsonInput.value = JSON.stringify(this.doc);
                if (amountInput) amountInput.value = String(t.grandTotal);
                if (statusInput) statusInput.value = statusOverride || 'draft';
            }
            if (isFormSubmit) {
                this.clearLocalDraft();
            }
            return true;
        },
        printDoc() {
            window.print();
        },
    }
}
</script>
@endpush
