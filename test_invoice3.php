<?php

try {
    $user = App\Models\User::find(3);
    Auth::login($user);
    
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
    
    echo "Status: " . $response->getStatusCode() . "\n";
    if ($response->isRedirect()) {
        echo "Redirect URL: " . $response->getTargetUrl() . "\n";
    }
    
    $errors = session()->get('errors');
    if ($errors) {
        echo "Errors: " . json_encode($errors->toArray()) . "\n";
    }
    
} catch (\Exception $e) {}
