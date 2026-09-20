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
@endphp

@push('styles')
<style>
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
    font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
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
.invoice-a4 [contenteditable]:focus,
.invoice-a4 input.ghost:focus {
    outline: none;
    background: rgba(255, 251, 235, 0.4);
    box-shadow: 0 0 0 1px rgba(252, 211, 77, 0.7);
    border-radius: 2px;
}
.invoice-a4 input.ghost {
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
.inv-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}
.inv-logo {
    height: 7rem;
    width: auto;
    object-fit: contain;
    object-position: left top;
    display: block;
}
.inv-title {
    margin: 0;
    padding-top: 1.75rem;
    font-size: 1.875rem;
    line-height: 2.25rem;
    font-weight: 700;
    letter-spacing: 0.025em;
    text-align: right;
}
.inv-supplier-meta {
    display: flex;
    justify-content: space-between;
    gap: 2rem;
    margin-bottom: 1.5rem;
}
.inv-supplier { flex: 1; min-width: 0; }
.inv-supplier .name { font-weight: 700; font-size: 0.875rem; display: block; }
.inv-supplier .addr { color: #404040; display: block; white-space: pre-wrap; }
.inv-meta { width: 42%; flex-shrink: 0; text-align: right; }
.inv-meta-row {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    align-items: baseline;
    margin-bottom: 0.25rem;
}
.inv-meta-row .lbl { color: #737373; }
.inv-meta-row .val {
    font-weight: 600;
    text-align: left;
    min-width: 8rem;
    font-variant-numeric: tabular-nums;
}
.inv-customer {
    margin-bottom: 1.5rem;
    border-top: 1px solid #e5e5e5;
    border-bottom: 1px solid #e5e5e5;
    padding: 0.75rem 0;
}
.inv-customer .cap {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #737373;
    margin-bottom: 0.25rem;
}
.inv-customer .cname { font-weight: 700; display: block; }
.inv-customer .caddr { color: #404040; display: block; white-space: pre-wrap; }
.inv-lines { width: 100%; border-collapse: collapse; margin-bottom: 0.5rem; }
.inv-lines th {
    border-bottom: 2px solid #000;
    text-align: left;
    font-size: 12px;
    font-weight: 400;
    color: #000;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    padding: 0.375rem 0.25rem 0.375rem 0;
}
.inv-lines th.r, .inv-lines td.r { text-align: right; }
.inv-lines td {
    border-bottom: 1px solid #d4d4d4;
    vertical-align: top;
    padding: 0.5rem 0.25rem 0.5rem 0;
    color: #000;
    font-weight: 400;
    font-size: 12px;
}
.inv-lines .lname { font-weight: 700; display: inline; color: #000; font-size: 12px; }
.inv-lines .ldesc { color: #000; font-size: 12px; font-weight: 400; display: inline; white-space: pre-wrap; }
.inv-lines .litem { font-size: 12px; line-height: 1.35; color: #000; }
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
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #000;
    font-weight: 700;
    margin-bottom: 0.25rem;
}
.inv-notes .block { margin-bottom: 1rem; }
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
.inv-term-indent { padding-left: 0.5rem; }
.inv-totals { width: 42%; flex-shrink: 0; font-size: 0.875rem; }
.inv-tot-row {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.375rem;
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
    margin-top: 1.5rem;
    color: #737373;
    font-style: italic;
    font-size: 12px;
}
.inv-sigs {
    margin-top: 3rem;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
}
.inv-sig .cap {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #737373;
}
.inv-sig-line {
    border-bottom: 1px solid #a3a3a3;
    min-height: 2rem;
    padding-top: 1.5rem;
    margin-bottom: 0.25rem;
}
.inv-sig-row {
    display: flex;
    gap: 0.25rem;
    align-items: baseline;
    padding-top: 0.25rem;
}
.inv-sig-row .lbl { color: #737373; flex-shrink: 0; }
.inv-sig-row .val { flex: 1; }
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
</style>
@endpush

<div
    id="{{ $editorId }}"
    x-data="invoiceEditor(@js($document), {{ $readOnly ? 'true' : 'false' }}, {{ isset($invoiceId) && $invoiceId ? (int) $invoiceId : 'null' }}, @js($invoiceStatus ?? ($status ?? null)))"
    x-cloak
>
    @if ($formAction)
    <form method="POST" action="{{ $formAction }}" @submit="submitting = true; prepareSubmit($event, 'issued')">
        @csrf
        @if (strtoupper($formMethod) !== 'POST')
            @method($formMethod)
        @endif
        <input type="hidden" name="document_json" id="invoice-document-json" value="">
        <input type="hidden" name="amount" id="invoice-amount-field" value="">
        <input type="hidden" name="sales_order_id" value="{{ $salesOrderId ?? '' }}">
        <input type="hidden" name="status" id="invoice-status-field" value="draft">

        <div class="page-head invoice-sticky-actions no-print">
            <div>
                <h1>{{ $pageTitle ?? 'Invoice' }}</h1>
                @if (!empty($pageDescription))
                    <p class="muted" style="margin:.35rem 0 0">{{ $pageDescription }}</p>
                @endif
            </div>
            <div class="toolbar">
                @if (($invoiceStatus ?? null) === 'approved')
                    <span class="badge" style="background:#166534;color:#bbf7d0">Approved</span>
                @endif
                <a class="btn ghost" href="{{ route('invoices.index') }}">Back to log</a>
                @if ($readOnly)
                    <button type="button" class="btn ghost" @click="printDoc()">Print</button>
                @else
                    <button type="submit" class="btn">Save</button>
                @endif
            </div>
        </div>

        @unless ($readOnly)
        <div class="card no-print" style="margin-bottom:1rem">
            <h2 style="margin:0 0 1rem;font-size:1.05rem">Invoice details</h2>
            <div class="grid-2">
                <div>
                    <label>Document type</label>
                    <input type="text" value="Proforma Invoice" readonly disabled>
                </div>
                <div>
                    <label class="muted">Invoice Id (auto)</label>
                    <input type="text" :value="doc.doc_number || 'Auto-generated'" readonly disabled>
                </div>
                <div>
                    <label>Invoice date</label>
                    <input type="date" x-model="doc.doc_date">
                </div>
                <div>
                    <label>Supplier name</label>
                    <input type="text" :value="doc.supplier.name" readonly disabled>
                </div>
                <div>
                    <label>Supplier address</label>
                    <textarea :value="doc.supplier.address_line" rows="2" style="margin-bottom:0.85rem" readonly disabled></textarea>
                </div>
                <div>
                    <label>Customer name</label>
                    <input type="text" x-model="doc.customer.name">
                </div>
                <div>
                    <label>Customer address</label>
                    <textarea x-model="doc.customer.address_line" rows="2" style="margin-bottom:0.85rem"></textarea>
                </div>
            </div>

            <h3 style="margin:1.25rem 0 .75rem;font-size:.95rem;color:var(--muted)">Line items</h3>
            <template x-for="(line, index) in doc.lines" :key="index">
                <div class="card" style="margin-bottom:.75rem;padding:1rem;background:#0f0d1f">
                    <div class="grid-2">
                        <div>
                            <label x-text="'Item #' + (index + 1) + ' name'"></label>
                            <input type="text" x-model="line.name" placeholder="Item name">
                        </div>
                        <div>
                            <label>Unit</label>
                            <input type="text" x-model="line.uom_code" placeholder="pcs">
                        </div>
                        <div style="grid-column:1 / -1">
                            <label>Description</label>
                            <textarea x-model="line.description" rows="2" style="margin-bottom:0.85rem" placeholder="Optional description"></textarea>
                        </div>
                        <div>
                            <label>Quantity</label>
                            <input type="number" step="any" min="0" x-model="line.quantity">
                        </div>
                        <div>
                            <label>Pieces</label>
                            <input type="number" step="1" min="0"
                                   :value="line.unit_count ?? ''"
                                   @input="line.unit_count = $event.target.value === '' ? null : Number($event.target.value)">
                        </div>
                        <div>
                            <label>Unit price (ETB)</label>
                            <input type="number" step="0.01" min="0" x-model="line.unit_price">
                        </div>
                        <div>
                            <label class="muted">Line total</label>
                            <input type="text" :value="money(lineTotal(index)) + ' ETB'" readonly>
                        </div>
                    </div>
                    <div class="toolbar" style="margin-top:.25rem">
                        <button type="button" class="btn ghost" @click="removeLine(index)" x-show="doc.lines.length > 1">Remove line</button>
                    </div>
                </div>
            </template>
            <div class="toolbar" style="margin-bottom:1rem">
                <button type="button" class="btn" style="background:#ea580c;color:#fff;border-color:#ea580c" @click="addLine()">Add line</button>
            </div>

            <h3 style="margin:0 0 .75rem;font-size:.95rem;color:var(--muted)">Totals &amp; terms</h3>
            <div class="grid-2">
                <div>
                    <label>Discount type</label>
                    <select x-model="doc.discount.type">
                        <option value="PERCENT">Percent (%)</option>
                        <option value="AMOUNT">Fixed amount (ETB)</option>
                    </select>
                </div>
                <div>
                    <label>Discount value</label>
                    <input type="number" step="any" min="0" x-model="doc.discount.value">
                </div>
                <div>
                    <label>VAT rate (%)</label>
                    <input type="number" step="any" min="0" x-model="doc.tax.rate">
                </div>
                <div>
                    <label class="muted">Grand total</label>
                    <input type="text" :value="money(totals.grandTotal) + ' ETB'" readonly>
                </div>
                <div style="grid-column:1 / -1">
                    <label>Notes</label>
                    <textarea x-model="notesText" rows="3" style="margin-bottom:0.85rem" placeholder="One note per line"></textarea>
                </div>
                <div>
                    <label>Payment terms</label>
                    <input type="text" x-model="doc.terms.payment">
                </div>
                <div>
                    <label>Delivery place</label>
                    <input type="text" x-model="doc.terms.delivery_place">
                </div>
                <div>
                    <label>Delivery time in days</label>
                    <input type="number" min="0" step="1" inputmode="numeric" x-model="doc.terms.delivery_days">
                </div>
                <div>
                    <label>Validity in days</label>
                    <input type="number" min="0" step="1" inputmode="numeric" x-model="doc.terms.validity_days">
                </div>
                <div>
                    <label>Warranty in years</label>
                    <input type="number" min="0" step="1" inputmode="numeric" x-model="doc.terms.warranty">
                </div>
                <div></div>
                <div>
                    <label>Prepared by — name</label>
                    <input type="text" :value="personName(doc.prepared_by.name)" readonly disabled>
                </div>
                <div>
                    <label>Prepared by — phone</label>
                    <input type="text" :value="doc.prepared_by.phone" readonly disabled>
                </div>
                <div>
                    <label>Approved by — name</label>
                    <input type="text" :value="personName(doc.approved_by.name) || 'Filled when admin approves'" readonly disabled>
                </div>
                <div>
                    <label>Approved by — phone</label>
                    <input type="text" :value="doc.approved_by.phone || ''" readonly disabled placeholder="Filled when admin approves">
                </div>
            </div>
            <p class="muted" style="margin:.75rem 0 0;font-size:.85rem">Prepared by is locked to your account. Approved by is set only when an admin approves the invoice.</p>
        </div>
        @endunless
    @endif

        @if ($readOnly)
        <div class="invoice-desk">
            <div class="invoice-a4" id="invoice-a4-sheet">
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
                            <span class="lbl">Invoice Id:</span>
                            <span class="val" x-text="doc.doc_number || 'Auto-generated'"></span>
                        </div>
                        <div class="inv-meta-row">
                            <span class="lbl">Date:</span>
                            <span class="val" style="font-weight:600" x-text="formatDate(doc.doc_date)"></span>
                        </div>
                        <template x-if="doc.doc_type === 'CREDIT_NOTE'">
                            <div class="inv-meta-row">
                                <span class="lbl">Against Invoice:</span>
                                <span class="val" x-text="doc.parent_doc_number || ''"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="inv-customer">
                    <div class="cap">Customer</div>
                    <div class="cname" x-text="doc.customer.name"></div>
                    <div class="caddr" x-text="doc.customer.address_line"></div>
                </div>

                <table class="inv-lines">
                    <thead>
                    <tr>
                        <th style="width:2rem">#</th>
                        <th>Name</th>
                        <th style="width:3.5rem">Unit</th>
                        <th class="r" style="width:4rem">Qty</th>
                        <th class="r" style="width:4rem">Pieces</th>
                        <th class="r" style="width:6rem">Unit Price</th>
                        <th class="r" style="width:7rem">Total Price</th>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="(line, index) in doc.lines" :key="index">
                        <tr>
                            <td class="muted" x-text="index + 1"></td>
                            <td>
                                <div class="litem">
                                    <span class="lname" x-text="line.name"></span><span class="ldesc" x-text="String(line.description || '').trim() ? (': ' + line.description) : ''"></span>
                                </div>
                            </td>
                            <td><span x-text="line.uom_code"></span></td>
                            <td class="r"><span x-text="line.quantity"></span></td>
                            <td class="r"><span x-text="line.unit_count ?? ''"></span></td>
                            <td class="r"><span x-text="line.unit_price"></span></td>
                            <td class="r" style="font-variant-numeric:tabular-nums" x-text="money(lineTotal(index))"></td>
                        </tr>
                    </template>
                    </tbody>
                </table>

                <div class="inv-bottom">
                    <div class="inv-notes">
                        <div class="block">
                            <div class="cap">Notes:</div>
                            <div style="min-height:3rem;white-space:pre-wrap;display:block" x-text="(doc.notes || []).join('\n')"></div>
                        </div>
                        <div>
                            <div class="cap">Terms & Conditions:</div>
                            <div class="inv-term-row">
                                <span class="lbl">Payment:</span>
                                <span class="val" x-text="doc.terms.payment"></span>
                            </div>
                            <div class="term-title" style="margin-bottom:.25rem">Delivery:</div>
                            <div class="inv-term-row inv-term-indent">
                                <span class="lbl">Place:</span>
                                <span class="val" x-text="doc.terms.delivery_place"></span>
                            </div>
                            <div class="inv-term-row inv-term-indent">
                                <span class="lbl">Time:</span>
                                <span class="val" x-text="doc.terms.delivery_days !== '' && doc.terms.delivery_days != null ? (doc.terms.delivery_days + ' days') : ''"></span>
                            </div>
                            <div class="inv-term-row inv-term-indent">
                                <span class="lbl">Validity:</span>
                                <span class="val" x-text="doc.terms.validity_days !== '' && doc.terms.validity_days != null ? (doc.terms.validity_days + ' days') : ''"></span>
                            </div>
                            <div class="inv-term-row">
                                <span class="lbl">Warranty:</span>
                                <span class="val" x-text="doc.terms.warranty !== '' && doc.terms.warranty != null ? (doc.terms.warranty + ' years') : ''"></span>
                            </div>
                        </div>
                    </div>
                    <div class="inv-totals">
                        <div class="inv-tot-row">
                            <span class="lbl">Total</span>
                            <span class="amt" x-text="money(totals.subtotal) + ' ' + (doc.currency || 'ETB')"></span>
                        </div>
                        <div class="inv-tot-row">
                            <span class="lbl" x-text="discountLabel"></span>
                            <span class="amt" x-text="money(totals.discountAmount) + ' ' + (doc.currency || 'ETB')"></span>
                        </div>
                        <div class="inv-tot-row">
                            <span class="lbl">S. Total</span>
                            <span class="amt" x-text="money(totals.afterDiscount) + ' ' + (doc.currency || 'ETB')"></span>
                        </div>
                        <div class="inv-tot-row">
                            <span class="lbl">VAT <span x-text="(doc.tax.rate || '0') + '%'"></span></span>
                            <span class="amt" x-text="money(totals.taxAmount) + ' ' + (doc.currency || 'ETB')"></span>
                        </div>
                        <div class="inv-tot-row inv-grand">
                            <span class="lbl">G. Total</span>
                            <span class="amt" x-text="money(totals.grandTotal) + ' ' + (doc.currency || 'ETB')"></span>
                        </div>
                    </div>
                </div>

                <template x-if="doc.doc_type === 'TAX_INVOICE' || doc.doc_type === 'CREDIT_NOTE'">
                    <div class="inv-words no-print">Amount in words: — will appear when printed</div>
                </template>

                <div class="inv-sigs">
                    <div class="inv-sig">
                        <div class="cap">Prepared By</div>
                        <div class="inv-sig-line"></div>
                        <div class="inv-sig-row">
                            <span class="lbl">Name:</span>
                            <span class="val" x-text="personName(doc.prepared_by.name)"></span>
                        </div>
                        <div class="inv-sig-row">
                            <span class="lbl">Tel:</span>
                            <span class="val" x-text="doc.prepared_by.phone"></span>
                        </div>
                    </div>
                    <div class="inv-sig">
                        <div class="cap">Approved By</div>
                        <div class="inv-sig-line"></div>
                        <div class="inv-sig-row">
                            <span class="lbl">Name:</span>
                            <span class="val" x-text="personName(doc.approved_by.name)"></span>
                        </div>
                        <div class="inv-sig-row">
                            <span class="lbl">Tel:</span>
                            <span class="val" x-text="doc.approved_by.phone"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

    @if ($formAction)
    </form>
    @endif
</div>

@push('scripts')
<script>
function invoiceEditor(initialDoc, readOnly, invoiceId, invoiceStatus) {
    const authUser = @js([
        'full_name' => auth()->user()?->full_name ?? '',
        'phone' => auth()->user()?->phone ?? '',
    ]);
    const snsSupplier = @js(\App\Services\DocumentService::SNS_SUPPLIER);
    const today = @js(date('Y-m-d'));
    const autosaveCreateUrl = @json(route('invoices.autosave'));
    const salesOrderId = @json($salesOrderId ?? '');
    const invoicesBaseUrl = @json(url('/finance/invoices'));

    const doc = initialDoc || {};
    if (!doc.supplier) doc.supplier = { name: '', address_line: '' };
    if (!doc.customer) doc.customer = { name: '', address_line: '' };
    if (!doc.terms) doc.terms = { payment: '', delivery_place: '', delivery_days: '', validity_days: '', warranty: '' };
    if (!doc.discount) doc.discount = { type: 'PERCENT', value: '0' };
    if (!doc.tax) doc.tax = { rate: '15' };
    if (!doc.prepared_by) doc.prepared_by = { name: '', phone: '' };
    if (!doc.approved_by) doc.approved_by = { name: '', phone: '' };
    if (!Array.isArray(doc.lines) || !doc.lines.length) {
        doc.lines = [{ line_no: 1, name: '', description: '', uom_code: 'pcs', quantity: '1.000', unit_count: null, unit_price: '0.00' }];
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
        if (! String(doc.prepared_by?.name || '').trim()) {
            doc.prepared_by = {
                name: authUser.full_name || '',
                phone: authUser.phone || '',
            };
        }
        if (! String(doc.approved_by?.name || '').trim()) {
            doc.approved_by = { name: '', phone: '' };
        }
    }
    return {
        doc,
        readOnly: readOnly,
        invoiceId: invoiceId || null,
        invoiceStatus: invoiceStatus || null,
        autosaveUrl: invoiceId ? (invoicesBaseUrl + '/' + invoiceId + '/autosave') : autosaveCreateUrl,
        dirty: false,
        saving: false,
        submitting: false,
        autosaveTimer: null,
        baselineJson: '',
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
        get docTitle() {
            return this.doc.doc_type === 'CREDIT_NOTE' ? 'Credit Note' : 'Proforma Invoice';
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
        init() {
            if (!this.canAutosave) return;
            this.baselineJson = JSON.stringify(this.doc);
            this.$watch('doc', () => {
                this.dirty = JSON.stringify(this.doc) !== this.baselineJson;
                this.scheduleAutosave();
            }, { deep: true });
            const flush = () => this.flushAutosave(true);
            window.addEventListener('pagehide', flush);
            window.addEventListener('beforeunload', flush);
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') flush();
            });
        },
        scheduleAutosave() {
            if (!this.canAutosave) return;
            clearTimeout(this.autosaveTimer);
            this.autosaveTimer = setTimeout(() => this.flushAutosave(false), 1200);
        },
        buildPayload() {
            this.prepareSubmit({ preventDefault(){} });
            const t = this.totals;
            return {
                document_json: JSON.stringify(this.doc),
                amount: String(t.grandTotal),
                status: 'draft',
                sales_order_id: salesOrderId || '',
            };
        },
        applyAutosaveResponse(data) {
            if (!data || !data.ok) return;
            this.invoiceId = data.id;
            this.invoiceStatus = 'draft';
            this.autosaveUrl = data.autosave_url || this.autosaveUrl;
            this.doc.doc_number = data.invoice_number || this.doc.doc_number;
            this.baselineJson = JSON.stringify(this.doc);
            this.dirty = false;
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
        async flushAutosave(keepalive) {
            if (!this.canAutosave || this.saving || !this.dirty) return;
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!token || !this.autosaveUrl) return;
            const payload = this.buildPayload();
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
                    keepalive: !!keepalive,
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const data = await res.json();
                this.applyAutosaveResponse(data);
            } catch (_) {
                // Best-effort; user can still click Save.
            } finally {
                this.saving = false;
            }
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
        addLine() {
            const next = (this.doc.lines.reduce((m,l)=>Math.max(m, Number(l.line_no)||0), 0)) + 1;
            this.doc.lines.push({ line_no: next, name:'', description:'', uom_code:'pcs', quantity:'1.000', unit_count:null, unit_price:'0.00' });
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
        prepareSubmit(e, statusOverride) {
            // Drafts save with any partial data — no required-field blocking.
            const t = this.totals;
            this.doc.currency = 'ETB';
            this.doc.doc_type = 'PROFORMA';
            this.doc.doc_date = this.doc.doc_date || today;
            this.doc.valid_until = this.doc.valid_until || today;
            this.doc.customer = this.doc.customer || { name: '', address_line: '' };
            this.doc.customer.name = String(this.doc.customer.name || '');
            this.doc.customer.address_line = String(this.doc.customer.address_line || '');
            this.doc.supplier = {
                name: snsSupplier.name,
                address_line: snsSupplier.address_line,
                tin: snsSupplier.tin || '',
                vat_reg_no: snsSupplier.vat_reg_no || '',
                phone: snsSupplier.phone || '',
            };
            this.doc.prepared_by = {
                name: String(this.doc.prepared_by?.name || authUser.full_name || '').trim(),
                phone: String(this.doc.prepared_by?.phone || authUser.phone || '').trim(),
            };
            if (!String(this.doc.approved_by?.name || '').trim()) {
                this.doc.approved_by = { name: '', phone: '' };
            }
            if (!Array.isArray(this.doc.lines) || !this.doc.lines.length) {
                this.doc.lines = [{ line_no: 1, name: '', description: '', uom_code: 'pcs', quantity: '1.000', unit_count: null, unit_price: '0.00' }];
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
            return true;
        },
        printDoc() {
            window.print();
        },
    }
}
</script>
@endpush
