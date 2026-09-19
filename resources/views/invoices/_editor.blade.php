@php
    $editorId = $editorId ?? 'invoice-editor';
    $readOnly = $readOnly ?? false;
    $formAction = $formAction ?? null;
    $formMethod = $formMethod ?? 'POST';
    $submitStatus = $submitStatus ?? null;
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
    padding: 0 12mm 12mm 2mm;
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
    height: 5rem;
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
    border-bottom: 2px solid #262626;
    text-align: left;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    padding: 0.375rem 0.25rem 0.375rem 0;
}
.inv-lines th.r, .inv-lines td.r { text-align: right; }
.inv-lines td {
    border-bottom: 1px solid #e5e5e5;
    vertical-align: top;
    padding: 0.5rem 0.25rem 0.5rem 0;
}
.inv-lines .muted { color: #737373; }
.inv-lines .lname { font-weight: 600; display: block; }
.inv-lines .ldesc { color: #525252; font-size: 12px; display: block; margin-top: 0.125rem; white-space: pre-wrap; }
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
    color: #737373;
    margin-bottom: 0.25rem;
}
.inv-notes .block { margin-bottom: 1rem; }
.inv-term-row {
    display: flex;
    gap: 0.25rem;
    align-items: baseline;
    margin-bottom: 0.25rem;
}
.inv-term-row .lbl { color: #737373; flex-shrink: 0; }
.inv-term-row .val { flex: 1; min-width: 0; }
.inv-term-indent { padding-left: 0.5rem; }
.inv-totals { width: 42%; flex-shrink: 0; font-size: 0.875rem; }
.inv-tot-row {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.375rem;
    align-items: baseline;
}
.inv-tot-row .lbl { color: #737373; }
.inv-tot-row .amt { font-variant-numeric: tabular-nums; }
.inv-grand {
    border-top: 1px solid #262626;
    padding-top: 0.5rem;
    margin-top: 0.5rem;
    font-weight: 700;
    font-size: 15px;
}
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
    font-size: 11px;
    text-decoration: underline;
    text-underline-offset: 2px;
    padding: 0;
}
@media print {
    .invoice-desk { background: #fff; padding: 0; }
    .invoice-a4 { box-shadow: none; border: 0; }
    .no-print { display: none !important; }
    @page { size: A4 portrait; margin: 0; }
}
</style>
@endpush

<div
    id="{{ $editorId }}"
    x-data="invoiceEditor(@js($document), {{ $readOnly ? 'true' : 'false' }})"
    x-cloak
>
    @if ($formAction)
    <form method="POST" action="{{ $formAction }}" @submit="prepareSubmit($event)">
        @csrf
        @if (strtoupper($formMethod) !== 'POST')
            @method($formMethod)
        @endif
        <input type="hidden" name="document_json" :value="JSON.stringify(doc)">
        <input type="hidden" name="amount" :value="totals.grandTotal">
        <input type="hidden" name="sales_order_id" value="{{ $salesOrderId ?? '' }}">
        <input type="hidden" name="status" id="invoice-status-field" value="{{ $status ?? 'draft' }}">

        <div class="page-head no-print">
            <div>
                <h1>{{ $pageTitle ?? 'Invoice' }}</h1>
                <p class="muted" style="margin:.35rem 0 0">{{ $pageDescription ?? 'Edit like Word, then save, print, or download PDF.' }}</p>
            </div>
            <div class="toolbar">
                <a class="btn ghost" href="{{ route('invoices.index') }}">Back to log</a>
                <button type="button" class="btn ghost" @click="downloadPdf()">Download PDF</button>
                <button type="button" class="btn ghost" @click="printDoc()">Print</button>
                @unless ($readOnly)
                    <button type="submit" class="btn ghost" @click="setStatus('draft')">Save draft</button>
                    <button type="submit" class="btn" @click="setStatus('issued')">Issue</button>
                @endunless
            </div>
        </div>

        @unless ($readOnly)
        <div class="card no-print" style="margin-bottom:1rem">
            <div class="grid-2">
                <div>
                    <label>Document type</label>
                    <select x-model="doc.doc_type">
                        <option value="PROFORMA">Proforma Invoice</option>
                        <option value="SALES_ORDER">Sales Order</option>
                        <option value="CREDIT_NOTE">Credit Note</option>
                    </select>
                </div>
                <div>
                    <label class="muted">Invoice Id (auto)</label>
                    <input type="text" :value="doc.doc_number || 'Auto-generated'" readonly>
                </div>
            </div>
        </div>
        @endunless
    @endif

        <div class="invoice-desk">
            <div class="invoice-a4" id="invoice-a4-sheet">
                <div class="inv-head">
                    <img class="inv-logo" src="{{ asset('sns-logo.png') }}" alt="SNS">
                    <h1 class="inv-title" x-text="docTitle"></h1>
                </div>

                <div class="inv-supplier-meta">
                    <div class="inv-supplier">
                        <div contenteditable="{{ $readOnly ? 'false' : 'true' }}" class="name"
                             @blur="doc.supplier.name = $event.target.innerText.trim()" x-text="doc.supplier.name"></div>
                        <div contenteditable="{{ $readOnly ? 'false' : 'true' }}" class="addr"
                             @blur="doc.supplier.address_line = $event.target.innerText" x-text="doc.supplier.address_line"></div>
                    </div>
                    <div class="inv-meta">
                        <div class="inv-meta-row">
                            <span class="lbl">Invoice Id:</span>
                            <span class="val" x-text="doc.doc_number || 'Auto-generated'"></span>
                        </div>
                        <div class="inv-meta-row">
                            <span class="lbl">Date:</span>
                            @if ($readOnly)
                                <span class="val" style="font-weight:600" x-text="formatDate(doc.doc_date)"></span>
                            @else
                                <input class="ghost" type="date" x-model="doc.doc_date" style="width:8rem;text-align:left;min-width:8rem">
                            @endif
                        </div>
                        <template x-if="doc.doc_type === 'PROFORMA'">
                            <div class="inv-meta-row">
                                <span class="lbl">Valid Until:</span>
                                <span class="val" contenteditable="{{ $readOnly ? 'false' : 'true' }}"
                                      @blur="doc.valid_until = $event.target.innerText.trim() || null" x-text="doc.valid_until || ''"></span>
                            </div>
                        </template>
                        <template x-if="doc.doc_type === 'CREDIT_NOTE'">
                            <div class="inv-meta-row">
                                <span class="lbl">Against Invoice:</span>
                                <span class="val" contenteditable="{{ $readOnly ? 'false' : 'true' }}"
                                      @blur="doc.parent_doc_number = $event.target.innerText.trim() || null" x-text="doc.parent_doc_number || ''"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="inv-customer">
                    <div class="cap">Customer</div>
                    <div contenteditable="{{ $readOnly ? 'false' : 'true' }}" class="cname"
                         @blur="doc.customer.name = $event.target.innerText.trim()" x-text="doc.customer.name"></div>
                    <div contenteditable="{{ $readOnly ? 'false' : 'true' }}" class="caddr"
                         @blur="doc.customer.address_line = $event.target.innerText" x-text="doc.customer.address_line"></div>
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
                        @unless ($readOnly)<th class="no-print" style="width:4rem"></th>@endunless
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="(line, index) in doc.lines" :key="index">
                        <tr>
                            <td class="muted" x-text="index + 1"></td>
                            <td>
                                <div contenteditable="{{ $readOnly ? 'false' : 'true' }}" class="lname"
                                     @blur="line.name = $event.target.innerText.trim()" x-text="line.name"></div>
                                <div contenteditable="{{ $readOnly ? 'false' : 'true' }}" class="ldesc"
                                     @blur="line.description = $event.target.innerText" x-text="line.description"></div>
                            </td>
                            <td>
                                <span contenteditable="{{ $readOnly ? 'false' : 'true' }}"
                                      @blur="line.uom_code = $event.target.innerText.trim()" x-text="line.uom_code"></span>
                            </td>
                            <td class="r">
                                @if ($readOnly)
                                    <span x-text="line.quantity"></span>
                                @else
                                    <input class="ghost" style="text-align:right" x-model="line.quantity">
                                @endif
                            </td>
                            <td class="r">
                                @if ($readOnly)
                                    <span x-text="line.unit_count ?? ''"></span>
                                @else
                                    <input class="ghost" style="text-align:right"
                                           :value="line.unit_count ?? ''"
                                           @input="line.unit_count = $event.target.value === '' ? null : Number($event.target.value)">
                                @endif
                            </td>
                            <td class="r">
                                @if ($readOnly)
                                    <span x-text="line.unit_price"></span>
                                @else
                                    <input class="ghost" style="text-align:right" x-model="line.unit_price">
                                @endif
                            </td>
                            <td class="r" style="font-variant-numeric:tabular-nums" x-text="money(lineTotal(index))"></td>
                            @unless ($readOnly)
                            <td class="no-print">
                                <button type="button" class="inv-remove" @click="removeLine(index)">Remove</button>
                            </td>
                            @endunless
                        </tr>
                    </template>
                    </tbody>
                </table>

                @unless ($readOnly)
                <button type="button" class="inv-add-line no-print" @click="addLine()">Add line</button>
                @endunless

                <div class="inv-bottom">
                    <div class="inv-notes">
                        <div class="block">
                            <div class="cap">Notes:</div>
                            <div contenteditable="{{ $readOnly ? 'false' : 'true' }}" style="min-height:3rem;white-space:pre-wrap;display:block"
                                 @blur="doc.notes = $event.target.innerText.split(/\r?\n/)" x-text="(doc.notes || []).join('\n')"></div>
                        </div>
                        <div>
                            <div class="cap">Terms & Conditions:</div>
                            <div class="inv-term-row">
                                <span class="lbl">Payment:</span>
                                <span class="val" contenteditable="{{ $readOnly ? 'false' : 'true' }}"
                                      @blur="doc.terms.payment = $event.target.innerText.trim()" x-text="doc.terms.payment"></span>
                            </div>
                            <div class="lbl" style="color:#737373;margin-bottom:.25rem">Delivery:</div>
                            <div class="inv-term-row inv-term-indent">
                                <span class="lbl">Place:</span>
                                <span class="val" contenteditable="{{ $readOnly ? 'false' : 'true' }}"
                                      @blur="doc.terms.delivery_place = $event.target.innerText.trim()" x-text="doc.terms.delivery_place"></span>
                            </div>
                            <div class="inv-term-row inv-term-indent">
                                <span class="lbl">Time:</span>
                                <span class="val" contenteditable="{{ $readOnly ? 'false' : 'true' }}"
                                      @blur="doc.terms.delivery_days = $event.target.innerText.trim()" x-text="doc.terms.delivery_days"></span>
                            </div>
                            <div class="inv-term-row inv-term-indent">
                                <span class="lbl">Validity:</span>
                                <span class="val" contenteditable="{{ $readOnly ? 'false' : 'true' }}"
                                      @blur="doc.terms.validity_days = $event.target.innerText.trim()" x-text="doc.terms.validity_days"></span>
                            </div>
                            <div class="inv-term-row">
                                <span class="lbl">Warrenty:</span>
                                <span class="val" contenteditable="{{ $readOnly ? 'false' : 'true' }}"
                                      @blur="doc.terms.warranty = $event.target.innerText.trim()" x-text="doc.terms.warranty"></span>
                            </div>
                        </div>
                    </div>
                    <div class="inv-totals">
                        <div class="inv-tot-row">
                            <span class="lbl">Total</span>
                            <span class="amt" x-text="money(totals.subtotal) + ' ' + (doc.currency || 'ETB')"></span>
                        </div>
                        <div class="inv-tot-row">
                            <span class="lbl" style="display:flex;align-items:center;gap:.25rem;flex-wrap:wrap">
                                @unless ($readOnly)
                                <button type="button" style="background:none;border:0;color:#737373;text-decoration:underline;text-decoration-style:dashed;cursor:pointer;padding:0;font:inherit"
                                        @click="toggleDiscount()" x-text="discountLabel" title="Toggle percent vs amount"></button>
                                <input class="ghost" style="width:3.5rem;text-align:right;border-bottom:1px dashed #d4d4d4" x-model="doc.discount.value">
                                @else
                                <span x-text="discountLabel"></span>
                                @endunless
                            </span>
                            <span class="amt" x-text="money(totals.discountAmount) + ' ' + (doc.currency || 'ETB')"></span>
                        </div>
                        <div class="inv-tot-row">
                            <span class="lbl">S. Total</span>
                            <span class="amt" x-text="money(totals.afterDiscount) + ' ' + (doc.currency || 'ETB')"></span>
                        </div>
                        <div class="inv-tot-row">
                            <span class="lbl" style="display:flex;align-items:center;gap:.25rem">
                                VAT
                                @unless ($readOnly)
                                    <input class="ghost" style="width:3rem;text-align:right;border-bottom:1px dashed #d4d4d4" x-model="doc.tax.rate"><span>%</span>
                                @else
                                    <span x-text="(doc.tax.rate || '0') + '%'"></span>
                                @endunless
                            </span>
                            <span class="amt" x-text="money(totals.taxAmount) + ' ' + (doc.currency || 'ETB')"></span>
                        </div>
                        <div class="inv-tot-row inv-grand">
                            <span>G. Total</span>
                            <span class="amt" x-text="money(totals.grandTotal) + ' ' + (doc.currency || 'ETB')"></span>
                        </div>
                    </div>
                </div>

                <template x-if="doc.doc_type === 'TAX_INVOICE' || doc.doc_type === 'CREDIT_NOTE'">
                    <div class="inv-words no-print">Amount in words: — will appear on PDF</div>
                </template>

                <div class="inv-sigs">
                    <div class="inv-sig">
                        <div class="cap">Prepared By Phone Number</div>
                        <div class="inv-sig-line"></div>
                        <div class="inv-sig-row">
                            <span class="lbl">Name:</span>
                            <span class="val" contenteditable="{{ $readOnly ? 'false' : 'true' }}"
                                  @blur="doc.prepared_by.name = $event.target.innerText.trim()" x-text="doc.prepared_by.name"></span>
                        </div>
                        <div class="inv-sig-row">
                            <span class="lbl">Tel:</span>
                            <span class="val" contenteditable="{{ $readOnly ? 'false' : 'true' }}"
                                  @blur="doc.prepared_by.phone = $event.target.innerText.trim()" x-text="doc.prepared_by.phone"></span>
                        </div>
                    </div>
                    <div class="inv-sig">
                        <div class="cap">Approved By Contact</div>
                        <div class="inv-sig-line"></div>
                        <div class="inv-sig-row">
                            <span class="lbl">Name:</span>
                            <span class="val" contenteditable="{{ $readOnly ? 'false' : 'true' }}"
                                  @blur="doc.approved_by.name = $event.target.innerText.trim()" x-text="doc.approved_by.name"></span>
                        </div>
                        <div class="inv-sig-row">
                            <span class="lbl">Tel:</span>
                            <span class="val" contenteditable="{{ $readOnly ? 'false' : 'true' }}"
                                  @blur="doc.approved_by.phone = $event.target.innerText.trim()" x-text="doc.approved_by.phone"></span>
                        </div>
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
function invoiceEditor(initialDoc, readOnly) {
    return {
        doc: initialDoc,
        readOnly: readOnly,
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
        round2(n) { return Math.round((n + Number.EPSILON) * 100) / 100; },
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
        toggleDiscount() {
            this.doc.discount.type = this.doc.discount.type === 'PERCENT' ? 'AMOUNT' : 'PERCENT';
        },
        addLine() {
            const next = (this.doc.lines.reduce((m,l)=>Math.max(m, Number(l.line_no)||0), 0)) + 1;
            this.doc.lines.push({ line_no: next, name:'', description:'', uom_code:'pcs', quantity:'1.000', unit_count:null, unit_price:'0.00' });
        },
        removeLine(i) {
            this.doc.lines.splice(i,1);
            this.doc.lines.forEach((l,idx)=> l.line_no = idx+1);
        },
        setStatus(status) {
            const el = document.getElementById('invoice-status-field');
            if (el) el.value = status;
        },
        prepareSubmit(e) {
            const err = this.validate();
            if (err) {
                e.preventDefault();
                alert(err);
                return;
            }
            const t = this.totals;
            this.doc.currency = 'ETB';
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
        },
        validate() {
            if (!String(this.doc.customer?.name||'').trim()) return 'Customer name is required';
            if (!(this.doc.lines||[]).some(l => String(l.name||'').trim())) return 'Add at least one line item';
            if (this.doc.doc_type === 'PROFORMA' && !String(this.doc.valid_until||'').trim()) return 'Valid until date is required for proforma invoices';
            if (this.doc.doc_type === 'CREDIT_NOTE' && !String(this.doc.parent_doc_number||'').trim()) return 'Parent invoice number is required for credit notes';
            return null;
        },
        printDoc() {
            window.print();
        },
        async downloadPdf() {
            const err = this.validate();
            if (err) { alert(err); return; }
            this.prepareSubmit({ preventDefault(){} });
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const res = await fetch(@json(route('invoices.compose-pdf')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/pdf',
                },
                body: JSON.stringify(this.doc),
            });
            if (!res.ok) {
                alert('Document download failed');
                return;
            }
            const blob = await res.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = (this.doc.doc_number || 'invoice') + '.pdf';
            a.click();
            URL.revokeObjectURL(url);
        },
    }
}
</script>
@endpush
