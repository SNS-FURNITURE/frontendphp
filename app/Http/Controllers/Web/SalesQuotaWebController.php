<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SalesQuota;
use Illuminate\View\View;

class SalesQuotaWebController extends Controller
{
    public function show(): View
    {
        abort_unless(auth()->user()?->canViewSales(), 403);

        $row = SalesQuota::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('id')
            ->first();

        $quota = $row ? [
            'quota' => (float) $row->quota,
            'actual' => (float) $row->actual,
            'period' => $row->period,
            'from_db' => true,
        ] : [
            'quota' => 100000,
            'actual' => 0,
            'period' => '2026-Q3',
            'from_db' => false,
        ];

        $pct = $quota['quota'] > 0
            ? min(100, round(($quota['actual'] / $quota['quota']) * 100, 1))
            : 0;

        return view('sales.quota', [
            'quota' => $quota,
            'pct' => $pct,
        ]);
    }
}
