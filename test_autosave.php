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
    
    $request = Illuminate\Http\Request::create('/finance/invoices/autosave', 'POST', [
        'document_json' => json_encode($document),
        'status' => 'draft',
    ]);
    
    $controller = app(App\Http\Controllers\Web\InvoiceWebController::class);
    if (!method_exists($controller, 'autosave')) {
        echo "Method autosave does not exist!\n";
    } else {
        echo "Method exists!\n";
    }
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
