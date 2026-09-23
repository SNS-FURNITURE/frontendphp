<?php

try {
    $user = App\Models\User::find(3);
    Auth::login($user);
    $invoice = App\Models\Invoice::latest('id')->first();
    echo "ID: {$invoice->id}, Status: {$invoice->status}, Created By: {$invoice->created_by}\n";
    echo "Owned by user 3: " . ($invoice->isOwnedBy($user) ? 'YES' : 'NO') . "\n";
} catch (\Exception $e) {}
