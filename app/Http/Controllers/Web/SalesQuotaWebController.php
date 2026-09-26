<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SalesQuota;
use App\Models\User;
use App\Services\SalesContactService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesQuotaWebController extends Controller
{
    public function __construct(private SalesContactService $contacts) {}

    public function show(): View|RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user?->isSalesRep() || $user?->canManageContactQuotas(), 403);

        if ($user->canManageContactQuotas()) {
            return redirect()->route('sales.quota.manage');
        }

        $periodType = request('period_type', 'monthly');
        $stats = $this->contacts->dashboardStats($user, $periodType);

        return view('sales.quota', [
            'stats' => $stats,
            'periodType' => $periodType,
        ]);
    }

    public function manage(Request $request): View
    {
        abort_unless(auth()->user()?->canManageContactQuotas(), 403);

        $periodType = $request->query('period_type', 'monthly');
        $period = $this->contacts->currentPeriod($periodType);
        $salesUsers = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', 'sales'))
            ->orderBy('full_name')
            ->get();

        $quotas = [];
        foreach ($salesUsers as $salesUser) {
            $row = $this->contacts->quotaForUser($salesUser, $periodType);
            $counts = $this->contacts->contactCountsForUser(
                (int) $salesUser->id,
                $period['start'],
                $period['end'],
            );
            $quotas[] = [
                'user' => $salesUser,
                'row' => $row,
                'counts' => $counts,
            ];
        }

        return view('sales.quota-manage', [
            'quotas' => $quotas,
            'period' => $period,
            'periodType' => $periodType,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageContactQuotas(), 403);

        $validated = $request->validate([
            'period_type' => ['required', 'in:daily,weekly,monthly,yearly'],
            'quotas' => ['required', 'array'],
            'quotas.*.user_id' => ['required', 'integer'],
            'quotas.*.quota' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);

        $periodType = $validated['period_type'];
        $period = $this->contacts->currentPeriod($periodType);

        foreach ($validated['quotas'] as $entry) {
            $user = User::query()
                ->where('id', $entry['user_id'])
                ->whereHas('roles', fn ($q) => $q->where('name', 'sales'))
                ->first();

            if (! $user) {
                continue;
            }

            SalesQuota::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'period' => $period['period'],
                    'period_type' => $periodType,
                ],
                [
                    'quota' => $entry['quota'],
                    'metric' => 'contacts',
                    'actual' => 0,
                ]
            );
        }

        return redirect()
            ->route('sales.quota.manage', ['period_type' => $periodType])
            ->with('status', 'Sales quotas updated for '.$period['label'].'.');
    }
}
