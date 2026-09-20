<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasInvoiceLaunchRole() && $user->hasPermission('finance', 'view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return false;
        }

        return $user->hasInvoiceLaunchRole() && $user->hasPermission('finance', 'create');
    }

    public function update(User $user, ?Invoice $invoice = null): bool
    {
        if ($user->isAdmin()) {
            return false;
        }

        return $user->hasInvoiceLaunchRole() && $user->hasPermission('finance', 'edit');
    }

    public function recordPayment(User $user): bool
    {
        return $this->create($user);
    }
}
