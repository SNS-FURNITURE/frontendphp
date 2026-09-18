<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceNumberService
{
    public function allocate(): string
    {
        $year = (int) date('Y');
        $prefix = "INV-{$year}-";

        $latest = DB::table('invoices')
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByRaw("CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED) DESC")
            ->value('invoice_number');

        $nextSeq = 1;
        if (is_string($latest) && preg_match('/INV-\d{4}-(\d+)$/i', $latest, $m)) {
            $nextSeq = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);
    }

    public function allocateUnique(int $maxAttempts = 5): string
    {
        $last = '';
        for ($i = 0; $i < $maxAttempts; $i++) {
            $last = $this->allocate();
            $exists = Invoice::query()->where('invoice_number', $last)->exists();
            if (! $exists) {
                return $last;
            }
        }

        return $last.'-'.substr((string) time(), -4);
    }
}
