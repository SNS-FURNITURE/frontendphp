@php
    $formAction = $formAction ?? route('invoices.store');
    $formMethod = $formMethod ?? 'POST';
    $pageTitle = $pageTitle ?? 'Create invoice';
    $pageDescription = $pageDescription ?? 'Enter invoice details, then save to view the Word document.';
    $status = $status ?? 'draft';
    $salesOrderId = $salesOrderId ?? '';
@endphp

<div
    x-data="invoiceForm(@js($document))"
    x-cloak
>
    <form method="POST" action="{{ $formAction }}" @submit="prepareSubmit($event)">
        @csrf
        @if (strtoupper($formMethod) !== 'POST')
            @method($formMethod)
        @endif
        <input type="hidden" name="document_json" :value="JSON.stringify(doc)">
        <input type="hidden" name="amount" :value="totals.grandTotal">
        <input type="hidden" name="sales_order_id" value="{{ $salesOrderId }}">
        <input type="hidden" name="status" id="invoice-status-field" value="{{ $status }}">

        <div class="page-head">
            <div>
                <h1>{{ $pageTitle }}</h1>
                <p class="muted" style="margin:.35rem 0 0">{{ $pageDescription }}</p>
            </div>
            <div class="toolbar">
                <a class="btn ghost" href="{{ route('invoices.index') }}">Back to log</a>
                <button type="submit" class="btn ghost" @click="setStatus('draft')">Save draft</button>
                <button type="submit" class="btn" @click="setStatus('issued')">Save &amp; issue</button>
            </div>
        </div>

        @if (isset($errors) && $errors->any())
            <div class="errors">{{ $errors->first() }}</div>
        @endif

        <div class="card" style="margin-bottom:1rem">
            <h3 style="margin-top:0">Document</h3>
            <div class="grid-2">
                <div>
                    <label for="doc_type">Document type</label>
                    <select id="doc_type" x-model="doc.doc_type">
                        <option value="PROFORMA">Proforma Invoice</option>
                        <option value="SALES_ORDER">Sales Order</option>
                        <option value="CREDIT_NOTE">Credit Note</option>
                    </select>
                </div>
                <div>
                    <label>Invoice number</label>
                    <input type="text" :value="doc.doc_number || 'Auto-generated on save'" readonly>
                </div>
                <div>
                    <label for="doc_date">Date</label>
                    <input id="doc_date" type="date" x-model="doc.doc_date" required>
                </div>
                <div x-show="doc.doc_type === 'PROFORMA'">
                    <label for="valid_until">Valid until</label>
                    <input id="valid_until" type="date" x-model="doc.valid_until">
                </div>
                <div x-show="doc.doc_type === 'CREDIT_NOTE'">
                    <label for="parent_doc_number">Parent invoice number</label>
                    <input id="parent_doc_number" type="text" x-model="doc.parent_doc_number">
                </div>
                <div>
                    <label for="due_date">Due date (optional)</label>
                    <input id="due_date" type="date" name="due_date" value="{{ old('due_date', $dueDate ?? '') }}">
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:1rem">
            <h3 style="margin-top:0">Customer</h3>
            <div class="grid-2">
                <div>
                    <label for="customer_name">Customer name</label>
                    <input id="customer_name" type="text" x-model="doc.customer.name" required>
                </div>
                <div>
                    <label for="customer_address">Customer address</label>
                    <input id="customer_address" type="text" x-model="doc.customer.address_line">
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:1rem">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:0.75rem">
                <h3 style="margin:0">Line items</h3>
                <button type="button" class="btn ghost" @click="addLine()">Add line</button>
            </div>
            <div style="overflow-x:auto">
                <table>
                    <thead>
                        <tr>
                            <th style="width:2.5rem">#</th>
                            <th>Item</th>
                            <th>Description</th>
                            <th style="width:5rem">UOM</th>
                            <th style="width:6rem">Qty</th>
                            <th style="width:8rem">Unit price</th>
                            <th style="width:8rem">Line total</th>
                            <th style="width:4rem"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, index) in doc.lines" :key="index">
                            <tr>
                                <td x-text="index + 1"></td>
                                <td><input type="text" x-model="line.name" placeholder="Item name" required></td>
                                <td><input type="text" x-model="line.description" placeholder="Optional"></td>
                                <td><input type="text" x-model="line.uom_code"></td>
                                <td><input type="number" step="0.001" min="0" x-model="line.quantity"></td>
                                <td><input type="number" step="0.01" min="0" x-model="line.unit_price"></td>
                                <td x-text="money(lineTotal(index))"></td>
                                <td>
                                    <button type="button" class="btn ghost" style="padding:0.25rem 0.5rem" @click="removeLine(index)" x-show="doc.lines.length > 1">Remove</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid-2" style="margin-bottom:1rem">
            <div class="card">
                <h3 style="margin-top:0">Discount &amp; tax</h3>
                <div class="grid-2">
                    <div>
                        <label for="discount_type">Discount type</label>
                        <select id="discount_type" x-model="doc.discount.type">
                            <option value="PERCENT">Percent</option>
                            <option value="AMOUNT">Fixed amount</option>
                        </select>
                    </div>
                    <div>
                        <label for="discount_value">Discount value</label>
                        <input id="discount_value" type="number" step="0.01" min="0" x-model="doc.discount.value">
                    </div>
                    <div>
                        <label for="tax_rate">VAT rate (%)</label>
                        <input id="tax_rate" type="number" step="0.01" min="0" x-model="doc.tax.rate">
                    </div>
                </div>
                <div style="margin-top:1rem;display:grid;gap:0.35rem">
                    <div style="display:flex;justify-content:space-between"><span class="muted">Subtotal</span><strong x-text="money(totals.subtotal) + ' ETB'"></strong></div>
                    <div style="display:flex;justify-content:space-between"><span class="muted">Discount</span><strong x-text="money(totals.discountAmount) + ' ETB'"></strong></div>
                    <div style="display:flex;justify-content:space-between"><span class="muted">After discount</span><strong x-text="money(totals.afterDiscount) + ' ETB'"></strong></div>
                    <div style="display:flex;justify-content:space-between"><span class="muted">VAT</span><strong x-text="money(totals.taxAmount) + ' ETB'"></strong></div>
                    <div style="display:flex;justify-content:space-between;border-top:1px solid var(--border);padding-top:0.5rem"><span>Grand total</span><strong x-text="money(totals.grandTotal) + ' ETB'"></strong></div>
                </div>
            </div>

            <div class="card">
                <h3 style="margin-top:0">Terms &amp; notes</h3>
                <label for="notes">Notes (one per line)</label>
                <textarea id="notes" rows="3" x-model="notesText" style="width:100%;margin-bottom:0.75rem"></textarea>
                <div class="grid-2">
                    <div>
                        <label for="term_payment">Payment</label>
                        <input id="term_payment" type="text" x-model="doc.terms.payment">
                    </div>
                    <div>
                        <label for="term_place">Delivery place</label>
                        <input id="term_place" type="text" x-model="doc.terms.delivery_place">
                    </div>
                    <div>
                        <label for="term_days">Delivery time</label>
                        <input id="term_days" type="text" x-model="doc.terms.delivery_days">
                    </div>
                    <div>
                        <label for="term_validity">Validity</label>
                        <input id="term_validity" type="text" x-model="doc.terms.validity_days">
                    </div>
                    <div>
                        <label for="term_warranty">Warranty</label>
                        <input id="term_warranty" type="text" x-model="doc.terms.warranty">
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:1rem">
            <h3 style="margin-top:0">Prepared &amp; approved by</h3>
            <div class="grid-2">
                <div>
                    <label for="prepared_name">Prepared by name</label>
                    <input id="prepared_name" type="text" x-model="doc.prepared_by.name">
                </div>
                <div>
                    <label for="prepared_phone">Prepared by phone</label>
                    <input id="prepared_phone" type="text" x-model="doc.prepared_by.phone">
                </div>
                <div>
                    <label for="approved_name">Approved by name</label>
                    <input id="approved_name" type="text" x-model="doc.approved_by.name">
                </div>
                <div>
                    <label for="approved_phone">Approved by phone</label>
                    <input id="approved_phone" type="text" x-model="doc.approved_by.phone">
                </div>
            </div>
        </div>

        <div class="toolbar">
            <button type="submit" class="btn ghost" @click="setStatus('draft')">Save draft</button>
            <button type="submit" class="btn" @click="setStatus('issued')">Save &amp; issue</button>
            <a class="btn ghost" href="{{ route('invoices.index') }}">Cancel</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
function invoiceForm(initialDoc) {
    const doc = JSON.parse(JSON.stringify(initialDoc || {}));
    doc.customer = doc.customer || { name: '', address_line: '' };
    doc.discount = doc.discount || { type: 'PERCENT', value: '0' };
    doc.tax = doc.tax || { rate: '15', code: 'VAT' };
    doc.terms = doc.terms || {};
    doc.prepared_by = doc.prepared_by || { name: '', phone: '' };
    doc.approved_by = doc.approved_by || { name: '', phone: '' };
    doc.lines = (doc.lines && doc.lines.length) ? doc.lines : [{
        line_no: 1, name: '', description: '', uom_code: 'pcs', quantity: '1.000', unit_price: '0.00'
    }];

    return {
        doc,
        notesText: (doc.notes || []).join('\n'),
        get totals() {
            const lines = this.doc.lines || [];
            const lineTotals = lines.map(l => this.round2((Number(l.quantity) || 0) * (Number(l.unit_price) || 0)));
            const subtotal = this.round2(lineTotals.reduce((a, b) => a + b, 0));
            const type = String(this.doc.discount?.type || 'PERCENT').toUpperCase();
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
            return this.round2(Number(n) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        lineTotal(i) { return this.totals.lineTotals[i] || 0; },
        addLine() {
            const next = (this.doc.lines.reduce((m, l) => Math.max(m, Number(l.line_no) || 0), 0)) + 1;
            this.doc.lines.push({
                line_no: next, name: '', description: '', uom_code: 'pcs', quantity: '1.000', unit_count: null, unit_price: '0.00'
            });
        },
        removeLine(i) {
            if (this.doc.lines.length <= 1) return;
            this.doc.lines.splice(i, 1);
            this.doc.lines.forEach((l, idx) => l.line_no = idx + 1);
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
            this.doc.notes = String(this.notesText || '').split(/\r?\n/);
            const t = this.totals;
            this.doc.currency = 'ETB';
            this.doc.discount.type = String(this.doc.discount.type || 'PERCENT').toUpperCase();
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
        },
        validate() {
            if (!String(this.doc.customer?.name || '').trim()) return 'Customer name is required';
            if (!(this.doc.lines || []).some(l => String(l.name || '').trim())) return 'Add at least one line item';
            if (this.doc.doc_type === 'PROFORMA' && !String(this.doc.valid_until || '').trim()) {
                return 'Valid until date is required for proforma invoices';
            }
            if (this.doc.doc_type === 'CREDIT_NOTE' && !String(this.doc.parent_doc_number || '').trim()) {
                return 'Parent invoice number is required for credit notes';
            }
            return null;
        },
    };
}
</script>
@endpush
