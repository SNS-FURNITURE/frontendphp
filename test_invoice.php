<?php

try {
    $user = App\Models\User::find(3); // Supervisor
    Auth::login($user);
    
    echo "Creating invoice...\n";
    $request = Illuminate\Http\Request::create('/finance/invoices', 'POST', [
        'customer_name' => 'Test Customer',
        'customer_address' => 'Test Address',
        'status' => 'draft',
        'lines' => [
            ['name' => 'Test Item', 'quantity' => 1, 'unit_price' => 100]
        ]
    ]);
    $controller = app(App\Http\Controllers\Web\InvoiceWebController::class);
    $response = $controller->store($request);
    echo "Store Response Status: " . $response->getStatusCode() . "\n";
    
    $invoice = App\Models\Invoice::latest('id')->first();
    if (!$invoice) {
        throw new \Exception("Invoice was not created in the database.");
    }
    echo "Created invoice ID: " . $invoice->id . "\n";
    
    echo "Showing invoice...\n";
    $showResponse = $controller->show($invoice);
    echo "Show complete.\n";
    
    echo "Editing invoice...\n";
    $editResponse = $controller->edit($invoice);
    echo "Edit complete.\n";
    
    echo "Updating invoice...\n";
    $updateRequest = Illuminate\Http\Request::create('/finance/invoices/' . $invoice->id, 'PUT', [
        'customer_name' => 'Test Customer Updated',
        'status' => 'draft',
        'lines' => [
            ['name' => 'Test Item Updated', 'quantity' => 2, 'unit_price' => 150]
        ]
    ]);
    $updateResponse = $controller->update($updateRequest, $invoice);
    echo "Update Response Status: " . $updateResponse->getStatusCode() . "\n";
    
    echo "Approving invoice...\n";
    $approveRequest = Illuminate\Http\Request::create('/finance/invoices/' . $invoice->id . '/approve', 'POST');
    $approveResponse = $controller->approve($approveRequest, $invoice);
    echo "Approve Response Status: " . $approveResponse->getStatusCode() . "\n";
    
    echo "Generating document...\n";
    $docRequest = Illuminate\Http\Request::create('/finance/invoices/' . $invoice->id . '/document', 'GET');
    $docResponse = $controller->document($docRequest, $invoice);
    echo "Document complete. Status: " . $docResponse->getStatusCode() . "\n";
    
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
