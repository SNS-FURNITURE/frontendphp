<?php

try {
    $user = App\Models\User::find(3);
    Auth::login($user);
    
    $document = [
        'doc_type' => 'PROFORMA',
        'customer' => ['name' => 'Test Customer', 'address_line' => 'Test Address'],
        'lines' => [
            ['name' => 'Test Item', 'quantity' => '1.0', 'unit_price' => '100.00']
        ]
    ];
    
    $request = Illuminate\Http\Request::create('/finance/invoices', 'POST', [
        'document_json' => json_encode($document),
        'status' => 'draft',
    ]);
    
    $controller = app(App\Http\Controllers\Web\InvoiceWebController::class);
    $response = $controller->store($request);
    
    echo "Store Response Status: " . $response->getStatusCode() . "\n";
    if ($response->isRedirect()) {
        $errors = session()->get('errors');
        if ($errors) {
            echo "Errors: " . json_encode($errors->toArray()) . "\n";
        } else {
            echo "Redirect Target: " . $response->getTargetUrl() . "\n";
        }
    }
    
    $invoice = App\Models\Invoice::latest('id')->first();
    if ($invoice) {
        echo "Created Invoice ID: {$invoice->id}, created_by: {$invoice->created_by}\n";
        
        echo "Testing view...\n";
        $controller->show($invoice);
        echo "View OK\n";
        
        echo "Testing edit...\n";
        $controller->edit($invoice);
        echo "Edit OK\n";
        
        echo "Testing update...\n";
        $updateReq = Illuminate\Http\Request::create('/finance/invoices/'.$invoice->id, 'PUT', [
            'document_json' => json_encode($document),
            'status' => 'draft',
        ]);
        $controller->update($updateReq, $invoice);
        echo "Update OK\n";
        
        echo "Testing approve...\n";
        $admin = App\Models\User::find(1); // switch to admin for approval
        Auth::login($admin);
        $approveReq = Illuminate\Http\Request::create('/finance/invoices/'.$invoice->id.'/approve', 'POST');
        $controller->approve($approveReq, $invoice);
        echo "Approve OK\n";
        
        echo "Testing pdf...\n";
        $pdfReq = Illuminate\Http\Request::create('/finance/invoices/'.$invoice->id.'/document', 'GET');
        $controller->document($pdfReq, $invoice);
        echo "PDF OK\n";
    }
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
