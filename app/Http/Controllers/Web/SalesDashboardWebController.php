<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\OrderMaterialUsageLog;
use App\Models\Party;
use App\Models\User;
use App\Services\SalesContactService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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

        $materialUsage = [
            'lines' => 0,
            'quantity' => 0.0,
            'orders' => 0,
            'rows' => collect(),
        ];

        if (Schema::hasTable('order_material_usage_logs')) {
            $base = OrderMaterialUsageLog::query()
                ->where('source_user_id', $user->id)
                ->whereBetween('used_at', [$report['period']['start'], $report['period']['end']]);

            $materialUsage = [
                'lines' => (clone $base)->count(),
                'quantity' => round((float) (clone $base)->sum('quantity'), 2),
                'orders' => (int) (clone $base)->selectRaw('COUNT(DISTINCT order_intake_id) as aggregate')->value('aggregate'),
                'rows' => OrderMaterialUsageLog::query()
                    ->where('source_user_id', $user->id)
                    ->whereBetween('used_at', [$report['period']['start'], $report['period']['end']])
                    ->latest('used_at')
                    ->limit(15)
                    ->get(),
            ];
        }

        return view('sales.dashboard', [
            'subject' => $user,
            'viewingAsManager' => $viewingAsManager,
            'report' => $report,
            'stats' => $stats,
            'recent' => $recent,
            'periodType' => $periodType,
            'materialUsage' => $materialUsage,
            'reportRoute' => $viewingAsManager
                ? route('sales.reps.report', ['user' => $user->id])
                : route('sales.dashboard'),
        ]);
    }
}
