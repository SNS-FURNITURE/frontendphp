<?php

try {
    $user = App\Models\User::find(3); // A user with invoice creation rights
    Auth::login($user);
    
    $document = [
        'doc_type' => 'PROFORMA',
        'customer' => ['name' => 'Autosave Customer', 'address_line' => 'Autosave Address'],
        'lines' => [
            ['name' => 'Autosave Item', 'quantity' => '2.0', 'unit_price' => '150.00']
        ]
    ];
    
    $request = Illuminate\Http\Request::create('/finance/invoices/autosave', 'POST', [
        'document_json' => json_encode($document),
        'status' => 'draft',
        'sales_order_id' => null, // Just to be explicit
    ]);
    
    $controller = app(App\Http\Controllers\Web\InvoiceWebController::class);
    $response = $controller->autosave($request);
    
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response content: " . $response->getContent() . "\n";
    
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
