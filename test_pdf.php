<?php
try {
    $user = App\Models\User::find(3);
    Auth::login($user);
    $invoice = App\Models\Invoice::first();
    
    if (!$invoice) {
        echo "No invoice found\n";
        exit;
    }
    
    $request = Illuminate\Http\Request::create('/finance/invoices/'.$invoice->id.'/document', 'GET');
    $controller = app(App\Http\Controllers\Web\InvoiceWebController::class);
    $response = $controller->document($request, $invoice);
    
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Content type: " . $response->headers->get('Content-Type') . "\n";
    echo "Length: " . strlen($response->getContent()) . " bytes\n";
    
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
