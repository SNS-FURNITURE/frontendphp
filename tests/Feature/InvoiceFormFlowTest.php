<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Services\DocumentService;
use Tests\TestCase;

class InvoiceFormFlowTest extends TestCase
{
    public function test_create_invoice_view_uses_form_not_word_editor(): void
    {
        $document = app(DocumentService::class)->blankDraft(null, 'INV-PREVIEW');

        $view = $this->view('invoices.create', [
            'document' => $document,
            'salesOrderId' => null,
        ]);

        $view->assertSee('Fill in the form', false);
        $view->assertSee('Line items', false);
        $view->assertSee('Customer name', false);
        $view->assertDontSee('invoice-a4', false);
    }

    public function test_show_invoice_view_uses_word_document(): void
    {
        $document = app(DocumentService::class)->blankDraft(null, 'INV-SHOW');
        $invoice = new Invoice([
            'invoice_number' => 'INV-SHOW',
            'status' => 'draft',
            'amount' => 0,
        ]);
        $invoice->id = 1;

        $view = $this->view('invoices.show', [
            'invoice' => $invoice,
            'document' => $document,
            'canEdit' => true,
        ]);

        $view->assertSee('invoice-a4', false);
        $view->assertSee('Edit details', false);
        $view->assertSee('Proforma Invoice', false);
    }

    public function test_guests_are_redirected_from_invoice_create(): void
    {
        $this->get('/finance/invoices/create')->assertRedirect('/login');
    }
}
