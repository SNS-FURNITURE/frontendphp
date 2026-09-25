<?php

namespace App\Services;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class CommercialOrdersReportService
{
    public function __construct(private SalesContactService $contacts) {}

    /**
     * @return array{
     *     period: array{start: Carbon, end: Carbon, label: string, period: string, period_type: string},
     *     period_type: string,
     *     counts: array{total: int, draft: int, pending: int, approved: int, paid: int, cancelled: int},
     *     percentages: array{draft: float, pending: float, approved: float, paid: float, cancelled: float},
     *     total: int
     * }
     */
    public function orderReport(string $periodType = 'monthly'): array
    {
        $periodType = $this->contacts->normalizePeriodType($periodType);
        $period = $this->contacts->currentPeriod($periodType);

        $counts = [
            'total' => 0,
            'draft' => 0,
            'pending' => 0,
            'approved' => 0,
            'paid' => 0,
            'cancelled' => 0,
        ];

        if (Schema::hasTable('invoices')) {
            $rows = Invoice::query()
                ->whereBetween('created_at', [$period['start'], $period['end']])
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            foreach ($rows as $status => $total) {
                $value = (int) $total;
                $counts['total'] += $value;

                match ((string) $status) {
                    'draft' => $counts['draft'] += $value,
                    'issued', 'overdue' => $counts['pending'] += $value,
                    'approved' => $counts['approved'] += $value,
                    'paid' => $counts['paid'] += $value,
                    'cancelled' => $counts['cancelled'] += $value,
                    default => null,
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
                'draft' => $percent($counts['draft']),
                'pending' => $percent($counts['pending']),
                'approved' => $percent($counts['approved']),
                'paid' => $percent($counts['paid']),
                'cancelled' => $percent($counts['cancelled']),
            ],
            'total' => $total,
        ];
    }
}
