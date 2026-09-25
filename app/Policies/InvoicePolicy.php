<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        if (! $user->hasInvoiceLaunchRole()) {
            return false;
        }

        return $user->hasPermission('finance', 'view')
            || $user->hasPermission('finance', 'create')
            || $user->hasRole('marketing_manager')
            || $user->isAdmin();
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if (! $this->viewAny($user) && ! $user->hasPermission('finance', 'create')) {
            return false;
        }

        if ($invoice->status === 'draft') {
            return $user->isAdmin() || $invoice->isOwnedBy($user);
        }

        return $user->hasInvoiceLaunchRole()
            && ($user->hasPermission('finance', 'view')
                || $user->hasPermission('finance', 'create')
                || $user->hasRole('marketing_manager'));
    }

    public function create(User $user): bool
    {
        if ($user->isAdmin() || $user->hasRole('finance') || $user->hasRole('marketing_manager')) {
            return false;
        }

        return $user->hasInvoiceLaunchRole() && $user->hasPermission('finance', 'create');
    }

    public function update(User $user, ?Invoice $invoice = null): bool
    {
        if ($user->isAdmin() || $user->hasRole('finance') || $user->hasRole('marketing_manager')) {
            return false;
        }

        if ($invoice && in_array($invoice->status, ['approved', 'paid', 'cancelled'], true)) {
            return false;
        }

        if ($invoice && $invoice->status === 'draft' && $invoice->isOwnedBy($user)) {
            return $user->hasPermission('finance', 'create') || $user->hasPermission('finance', 'edit');
        }

        return $user->hasInvoiceLaunchRole() && $user->hasPermission('finance', 'edit');
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        if ($user->isAdmin() || $user->hasRole('finance')) {
            return false;
        }

        return $invoice->status === 'draft'
            && $invoice->isOwnedBy($user)
            && ($user->hasPermission('finance', 'create') || $user->hasPermission('finance', 'delete'));
    }

    public function approve(User $user, ?Invoice $invoice = null): bool
    {
        if ($invoice && in_array($invoice->status, ['approved', 'paid', 'cancelled', 'draft'], true)) {
            return false;
        }

        return $user->isAdmin() || $user->hasRole('marketing_manager');
    }

    public function recordPayment(User $user): bool
    {
        return $this->create($user);
    }
}
