@extends('layouts.app')

@section('title', $invoice->invoice_number)
@section('content_class', 'content-wide')

@section('content')
@php($canViewPrices = auth()->user()?->canViewInvoicePrices() ?? false)
<div class="toolbar no-print" style="margin-bottom:1rem">
    @if ($canEdit)
        <a class="btn" href="{{ route('invoices.edit', $invoice) }}">Edit document</a>
    @endif
    @if ($canViewPrices)
        <button type="button" class="btn ghost" id="invoice-print-btn">Print</button>
        <button type="button" class="btn ghost" id="invoice-pdf-btn">Download PDF</button>
    @endif
    <a class="btn ghost" href="{{ route('invoices.index') }}">Back to log</a>
    @if ($canApprove ?? false)
        <button type="submit" form="invoice-approve-form" class="btn"
                data-erp-confirm="Set prices and approver details, then approve this order?"
                data-erp-confirm-title="Approve order"
                data-erp-confirm-ok="Approve">Approve order</button>
    @endif
    <span class="badge" @if($invoice->status === 'approved') style="background:#166534;color:#bbf7d0" @endif>
        {{ $invoice->status }}
    </span>
</div>

@include('invoices._editor', [
    'document' => $document,
    'readOnly' => true,
    'formAction' => null,
    'pageTitle' => $invoice->invoice_number,
    'pageDescription' => $invoice->status === 'approved'
        ? 'Approved order — signed by '.($document['approved_by']['name'] ?? 'admin').'.'
        : (($canApprove ?? false)
            ? 'Review prices and fill in Approved By details, then click Approve order.'
            : 'Saved order document.'),
    'invoiceStatus' => $invoice->status,
    'invoiceId' => $invoice->id,
    'approvalMode' => $canApprove ?? false,
    'approveAction' => ($canApprove ?? false) ? route('invoices.approve', $invoice) : null,
])
@endsection

@if ($canViewPrices ?? auth()->user()?->canViewInvoicePrices())
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(function () {
    var sheetId = 'invoice-a4-sheet';
    var fileName = @json(($invoice->invoice_number ?: 'invoice') . '.pdf');
    // A4 at 96dpi
    var A4_W_PX = 794;
    var A4_H_PX = 1123;

    function sheet() {
        return document.getElementById(sheetId);
    }

    function waitImages(root) {
        var imgs = Array.prototype.slice.call(root.querySelectorAll('img'));
        return Promise.all(imgs.map(function (img) {
            if (img.complete && img.naturalWidth) return Promise.resolve();
            return new Promise(function (resolve) {
                img.onload = resolve;
                img.onerror = resolve;
                setTimeout(resolve, 1500);
            });
        }));
    }

    function buildCaptureClone(source) {
        var host = document.createElement('div');
        host.id = 'invoice-pdf-capture-host';
        host.setAttribute('aria-hidden', 'true');
        host.style.cssText = [
            'position:fixed',
            'left:-10000px',
            'top:0',
            'width:' + A4_W_PX + 'px',
            'background:#ffffff',
            'color:#171717',
            'z-index:-1',
            'pointer-events:none',
            'opacity:1',
        ].join(';');

        var clone = source.cloneNode(true);
        clone.id = 'invoice-a4-sheet-clone';
        clone.classList.add('invoice-a4');
        clone.style.cssText = [
            'position:relative',
            'box-sizing:border-box',
            'width:' + A4_W_PX + 'px',
            'min-width:' + A4_W_PX + 'px',
            'max-width:' + A4_W_PX + 'px',
            'height:' + A4_H_PX + 'px',
            'min-height:' + A4_H_PX + 'px',
            'max-height:' + A4_H_PX + 'px',
            'margin:0',
            'padding:0 45px 45px 53px',
            'background:#ffffff',
            'color:#171717',
            'overflow:hidden',
            'box-shadow:none',
            'border:0',
            'transform:none',
            "font-family:'Neris','NotoSans','NotoEthiopic',DejaVu Sans,sans-serif",
            'font-size:13px',
            'line-height:1.375',
        ].join(';');

        // Strip Alpine control attrs; keep rendered text/nodes.
        clone.querySelectorAll('*').forEach(function (node) {
            Array.prototype.slice.call(node.attributes).forEach(function (attr) {
                if (attr.name.indexOf('x-') === 0 || attr.name.indexOf('@') === 0 || attr.name.indexOf(':') === 0) {
                    node.removeAttribute(attr.name);
                }
            });
            node.removeAttribute('x-cloak');
        });

        host.appendChild(clone);
        document.body.appendChild(host);
        return { host: host, clone: clone };
    }

    function printInvoice() {
        var source = sheet();
        if (!source) return;

        var iframe = document.createElement('iframe');
        iframe.setAttribute('aria-hidden', 'true');
        iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
        document.body.appendChild(iframe);

        var doc = iframe.contentDocument || iframe.contentWindow.document;
        var styles = Array.prototype.slice.call(document.querySelectorAll('style, link[rel="stylesheet"]'))
            .map(function (n) { return n.outerHTML; })
            .join('\n');

        var paper = source.cloneNode(true);
        paper.id = 'invoice-a4-sheet';
        paper.querySelectorAll('*').forEach(function (node) {
            Array.prototype.slice.call(node.attributes).forEach(function (attr) {
                if (attr.name.indexOf('x-') === 0 || attr.name.indexOf('@') === 0 || attr.name.indexOf(':') === 0) {
                    node.removeAttribute(attr.name);
                }
            });
        });

        doc.open();
        doc.write('<!DOCTYPE html><html><head><meta charset="utf-8">' +
            '<link href="https://fonts.cdnfonts.com/css/neris" rel="stylesheet">' + styles +
            '<style>' +
            'html,body{margin:0;padding:0;background:#fff;color:#171717;font-family:\'Neris\',\'NotoSans\',\'NotoEthiopic\',DejaVu Sans,sans-serif;}' +
            '.invoice-desk{background:#fff!important;padding:0!important;display:block!important;}' +
            '.invoice-a4,.invoice-a4 *{font-family:\'Neris\',\'NotoSans\',\'NotoEthiopic\',DejaVu Sans,sans-serif!important;}' +
            '.invoice-a4{box-shadow:none!important;border:0!important;margin:0 auto!important;' +
            'width:210mm!important;min-width:210mm!important;max-width:210mm!important;' +
            'height:297mm!important;min-height:297mm!important;max-height:297mm!important;' +
            'padding:0 12mm 12mm 14mm!important;overflow:hidden!important;background:#fff!important;}' +
            '@page{size:A4 portrait;margin:0;}' +
            '</style></head><body><div class="invoice-desk">' + paper.outerHTML + '</div></body></html>');
        doc.close();

        var win = iframe.contentWindow;
        setTimeout(function () {
            win.focus();
            win.print();
            setTimeout(function () { iframe.remove(); }, 800);
        }, 400);
    }

    async function downloadPdf() {
        var source = sheet();
        if (!source || typeof html2canvas === 'undefined' || !(window.jspdf && window.jspdf.jsPDF)) {
            window.erpToast('PDF download is not available right now.', 'warn');
            return;
        }

        var btn = document.getElementById('invoice-pdf-btn');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Preparing PDF…';
        }

        var capture = null;
        try {
            await waitImages(source);
            if (document.fonts && document.fonts.ready) {
                await document.fonts.ready;
            }

            capture = buildCaptureClone(source);
            await waitImages(capture.clone);
            await new Promise(function (r) { requestAnimationFrame(function () { requestAnimationFrame(r); }); });

            var canvas = await html2canvas(capture.clone, {
                scale: 2,
                useCORS: true,
                allowTaint: false,
                backgroundColor: '#ffffff',
                logging: false,
                width: A4_W_PX,
                height: A4_H_PX,
                windowWidth: A4_W_PX,
                windowHeight: A4_H_PX,
                scrollX: 0,
                scrollY: 0,
            });

            if (!canvas || canvas.width < 10 || canvas.height < 10) {
                throw new Error('Empty canvas');
            }

            var img = canvas.toDataURL('image/jpeg', 0.98);
            var JsPDF = window.jspdf.jsPDF;
            var pdf = new JsPDF({ unit: 'mm', format: 'a4', orientation: 'portrait', compress: true });
            pdf.addImage(img, 'JPEG', 0, 0, 210, 297, undefined, 'FAST');
            
            // Fix for Chrome 'File wasn't available on the site' bug
            var blob = pdf.output('blob');
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = fileName;
            document.body.appendChild(a);
            a.click();
            
            setTimeout(function() {
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }, 5000);
            
        } catch (err) {
            console.error(err);
            window.erpToast('Could not create PDF from the paper. Please try again.', 'error');
        } finally {
            if (capture && capture.host && capture.host.parentNode) {
                capture.host.parentNode.removeChild(capture.host);
            }
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Download PDF';
            }
        }
    }

    var printBtn = document.getElementById('invoice-print-btn');
    var pdfBtn = document.getElementById('invoice-pdf-btn');
    if (printBtn) printBtn.addEventListener('click', printInvoice);
    if (pdfBtn) pdfBtn.addEventListener('click', downloadPdf);
})();
</script>
@endpush
@endif
