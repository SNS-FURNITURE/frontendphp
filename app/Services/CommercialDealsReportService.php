<?php

namespace App\Services;

use App\Models\Deal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class CommercialDealsReportService
{
    public function __construct(private SalesContactService $contacts) {}

    /**
     * @return array{
     *     period: array{start: Carbon, end: Carbon, label: string, period: string, period_type: string},
     *     period_type: string,
     *     counts: array{total: int, in_progress: int, sales_approved: int, won: int, lost: int},
     *     percentages: array{in_progress: float, sales_approved: float, won: float, lost: float},
     *     total: int
     * }
     */
    public function dealReport(string $periodType = 'monthly'): array
    {
        $periodType = $this->contacts->normalizePeriodType($periodType);
        $period = $this->contacts->currentPeriod($periodType);

        $counts = [
            'total' => 0,
            'in_progress' => 0,
            'sales_approved' => 0,
            'won' => 0,
            'lost' => 0,
        ];

        if (Schema::hasTable('deals')) {
            $rows = Deal::query()
                ->whereBetween('created_at', [$period['start'], $period['end']])
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            foreach ($rows as $status => $total) {
                $value = (int) $total;
                $counts['total'] += $value;

                match ((string) $status) {
                    'in_progress' => $counts['in_progress'] += $value,
                    'sales_approved' => $counts['sales_approved'] += $value,
                    'won' => $counts['won'] += $value,
                    'lost' => $counts['lost'] += $value,
                    default => $counts['in_progress'] += $value,
                };
            }
        }

        $total = $counts['total'];
        $percent = static fn (int $value): float => $total > 0
            ? round(($value / $total) * 100, 1)
            : 0.0;

        return [
            'period' => $period,
            'period_type' => $periodType,
            'counts' => $counts,
            'percentages' => [
                'in_progress' => $percent($counts['in_progress']),
                'sales_approved' => $percent($counts['sales_approved']),
                'won' => $percent($counts['won']),
                'lost' => $percent($counts['lost']),
            ],
            'total' => $total,
        ];
    }
}
