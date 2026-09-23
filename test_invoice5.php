<?php

try {
    $user = App\Models\User::find(3);
    Auth::login($user);
    $invoice = App\Models\Invoice::latest('id')->first();
    
    echo "Updating invoice to issued...\n";
    $invoice->status = 'issued';
    $invoice->save();
    
    echo "Testing approve...\n";
    $admin = App\Models\User::find(1); // switch to admin for approval
    Auth::login($admin);
    
    $controller = app(App\Http\Controllers\Web\InvoiceWebController::class);
    $approveReq = Illuminate\Http\Request::create('/finance/invoices/'.$invoice->id.'/approve', 'POST');
    $controller->approve($approveReq, $invoice);
    echo "Approve OK\n";
    
    echo "Testing pdf...\n";
    $pdfReq = Illuminate\Http\Request::create('/finance/invoices/'.$invoice->id.'/document', 'GET');
    $controller->document($pdfReq, $invoice);
    echo "PDF OK\n";
    
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
