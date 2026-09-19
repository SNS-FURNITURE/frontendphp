<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\User;
use App\Services\DocumentService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    public function __construct(private DocumentService $documents) {}

    public function render(Request $request): Response
    {
        $format = strtolower((string) $request->query('format', 'pdf'));
        $snapshot = is_array($request->all()) ? $request->all() : [];
        $docNumber = (string) ($snapshot['doc_number'] ?? 'document');
        $filename = preg_replace('/[^\w.-]+/', '_', $docNumber) ?: 'document';

        return $this->sendRendered($snapshot, $format, $filename);
    }

    public function compose(Request $request): Response
    {
        $format = strtolower((string) $request->query('format', 'pdf'));
        $snapshot = $this->documents->assemble($request->all() ?? []);
        $filename = preg_replace('/[^\w.-]+/', '_', (string) $snapshot['doc_number']) ?: 'document';

        return $this->sendRendered($snapshot, $format, $filename);
    }

    public function invoiceDocument(Request $request, int $id): Response
    {
        $format = strtolower((string) $request->query('format', 'pdf'));
        $invoice = Invoice::query()->find($id);
        if (! $invoice) {
            return ApiResponse::error('Invoice not found', 'NOT_FOUND', 404);
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        try {
            $snapshot = $this->documents->snapshotForInvoice($invoice, $user);
        } catch (\RuntimeException $e) {
            $msg = $e->getMessage();
            $code = str_contains($msg, 'not found') ? 'NOT_FOUND' : 'INVALID_INPUT';
            $status = $code === 'NOT_FOUND' ? 404 : 400;

            return ApiResponse::error($msg, $code, $status);
        }

        $filename = preg_replace('/[^\w.-]+/', '_', $invoice->invoice_number) ?: 'invoice';

        return $this->sendRendered($snapshot, $format, $filename);
    }

    private function sendRendered(array $snapshot, string $format, string $filename): Response
    {
        if ($format === 'html') {
            $html = $this->documents->renderHtml($snapshot);

            return response($html, 200, [
                'Content-Type' => 'text/html; charset=utf-8',
                'Content-Disposition' => 'inline; filename="'.$filename.'.html"',
            ]);
        }

        $pdf = $this->documents->renderPdf($snapshot);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.pdf"',
        ]);
    }
}
