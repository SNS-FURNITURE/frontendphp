<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\CommercialDealsReportService;
use App\Services\CommercialOrdersReportService;
use App\Services\CommercialReportService;
use App\Services\SalesContactService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommercialReportWebController extends Controller
{
    public function __construct(
        private SalesContactService $contacts,
        private CommercialReportService $commercialReports,
        private CommercialOrdersReportService $ordersReport,
        private CommercialDealsReportService $dealsReport,
    ) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->canViewCommercialReports(), 403);

        $periodType = $this->contacts->normalizePeriodType($request->query('period'));

        $reports = Report::query()
            ->where(function ($q) {
                $q->where('report_type', 'like', 'commercial_%');
            })
            ->latestFirst('posted_at')
            ->limit(200)
            ->get();

        return view('commercial.reports.index', [
            'reports' => $reports,
            'orderReport' => $this->ordersReport->orderReport($periodType),
            'dealReport' => $this->dealsReport->dealReport($periodType),
            'periodType' => $periodType,
        ]);
    }

    public function generate(string $cadence): RedirectResponse
    {
        abort_unless(auth()->user()?->canViewCommercialReports(), 403);
        if (! in_array($cadence, ['weekly', 'monthly', 'yearly'], true)) {
            return redirect()
                ->route('commercial.reports.index')
                ->withErrors(['cadence' => 'Invalid report cadence.']);
        }

        $count = $this->commercialReports->generatePeriodicReports($cadence);

        return redirect()
            ->route('commercial.reports.index', ['period' => $cadence])
            ->with('status', "Generated {$count} {$cadence} commercial report(s).");
    }
}
