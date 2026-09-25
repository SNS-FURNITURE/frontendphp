<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\User;
use App\Services\SalesContactService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesDashboardWebController extends Controller
{
    public function __construct(private SalesContactService $contacts) {}

    public function index(Request $request): View
    {
        $user = auth()->user();
        abort_unless($user?->isSalesRep(), 403);

        return $this->renderDashboard($request, $user, false);
    }

    public function repReport(Request $request, User $user): View
    {
        abort_unless(auth()->user()?->canManageContactQuotas(), 403);
        abort_unless($user->isSalesRep(), 403);

        return $this->renderDashboard($request, $user, true);
    }

    private function renderDashboard(Request $request, User $user, bool $viewingAsManager): View
    {
        $periodType = $this->contacts->normalizePeriodType($request->query('period'));
        $report = $this->contacts->contactReportForUser($user, $periodType);
        $stats = $this->contacts->dashboardStats($user, $periodType);
        $recent = Party::query()
            ->where('party_type', 'customer')
            ->where('created_by', $user->id)
            ->whereBetween('created_at', [$report['period']['start'], $report['period']['end']])
            ->latestFirst()
            ->limit(10)
            ->get();

        return view('sales.dashboard', [
            'subject' => $user,
            'viewingAsManager' => $viewingAsManager,
            'report' => $report,
            'stats' => $stats,
            'recent' => $recent,
            'periodType' => $periodType,
            'reportRoute' => $viewingAsManager
                ? route('sales.reps.report', ['user' => $user->id])
                : route('sales.dashboard'),
        ]);
    }
}
