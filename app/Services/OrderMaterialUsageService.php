<?php

namespace App\Services;

use App\Models\OrderIntake;
use App\Models\OrderMaterialLine;
use App\Models\OrderMaterialUsageLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class OrderMaterialUsageService
{
    public function __construct(
        private SalesContactService $contacts,
        private NotifyService $notify,
    ) {}

    /**
     * Persist usage rows when materials are released to production for an order.
     *
     * @return list<OrderMaterialUsageLog>
     */
    public function logRelease(OrderIntake $intake, User $actor, Collection $lines): array
    {
        $created = [];
        $usedAt = now();

        foreach ($lines as $line) {
            if (! $line instanceof OrderMaterialLine) {
                continue;
            }

            if (OrderMaterialUsageLog::query()
                ->where('order_material_line_id', $line->id)
                ->exists()) {
                continue;
            }

            $created[] = OrderMaterialUsageLog::query()->create([
                'order_intake_id' => $intake->id,
                'order_material_line_id' => $line->id,
                'invoice_id' => $intake->invoice_id,
                'invoice_number' => $intake->invoice_number,
                'item_id' => $line->item_id,
                'item_name' => $line->item_name,
                'quantity' => $line->quantity,
                'unit' => $line->unit,
                'source_user_id' => $intake->source_user_id,
                'released_by' => $actor->id,
                'used_at' => $usedAt,
                'notes' => $line->notes,
            ]);
        }

        if ($created !== []) {
            foreach ($this->notify->activeUserIdsWithRoles(['admin']) as $adminId) {
                $this->notify->notifyUser($adminId, [
                    'type' => 'material_usage_logged',
                    'title' => 'Materials used on order',
                    'message' => count($created).' material line(s) released for '.$intake->invoice_number.'.',
                    'entityType' => 'order_intake',
                    'entityId' => (int) $intake->id,
                ]);
            }
        }

        return $created;
    }

    /**
     * @return array{
     *     period: array{start: Carbon, end: Carbon, label: string, period: string, period_type: string},
     *     period_type: string,
     *     totals: array{lines: int, quantity: float, orders: int, items: int},
     *     by_item: list<array{item_name: string, quantity: float, lines: int, unit: string|null}>,
     *     by_sales: list<array{user_id: int|null, full_name: string, quantity: float, lines: int, orders: int}>,
     *     recent: Collection<int, OrderMaterialUsageLog>
     * }
     */
    public function usageReport(string $periodType = 'monthly'): array
    {
        $periodType = $this->contacts->normalizePeriodType($periodType);
        $period = $this->contacts->currentPeriod($periodType);

        $empty = [
            'period' => $period,
            'period_type' => $periodType,
            'totals' => [
                'lines' => 0,
                'quantity' => 0.0,
                'orders' => 0,
                'items' => 0,
            ],
            'by_item' => [],
            'by_sales' => [],
            'recent' => collect(),
        ];

        if (! Schema::hasTable('order_material_usage_logs')) {
            return $empty;
        }

        $base = OrderMaterialUsageLog::query()
            ->whereBetween('used_at', [$period['start'], $period['end']]);

        $lines = (clone $base)->count();
        $quantity = (float) (clone $base)->sum('quantity');
        $orders = (int) (clone $base)->selectRaw('COUNT(DISTINCT order_intake_id) as aggregate')->value('aggregate');
        $items = (int) (clone $base)->selectRaw('COUNT(DISTINCT item_name) as aggregate')->value('aggregate');

        $byItem = (clone $base)
            ->selectRaw('item_name, COALESCE(unit, ?) as unit, SUM(quantity) as quantity, COUNT(*) as lines', ['pcs'])
            ->groupBy('item_name', 'unit')
            ->orderByDesc('quantity')
            ->limit(25)
            ->get()
            ->map(fn ($row) => [
                'item_name' => (string) $row->item_name,
                'quantity' => (float) $row->quantity,
                'lines' => (int) $row->lines,
                'unit' => $row->unit ? (string) $row->unit : null,
            ])
            ->all();

        $bySales = (clone $base)
            ->selectRaw('source_user_id, SUM(quantity) as quantity, COUNT(*) as lines, COUNT(DISTINCT order_intake_id) as orders')
            ->groupBy('source_user_id')
            ->orderByDesc('quantity')
            ->get();

        $users = User::query()
            ->whereIn('id', $bySales->pluck('source_user_id')->filter()->all())
            ->get()
            ->keyBy('id');

        $bySalesRows = $bySales->map(function ($row) use ($users) {
            $userId = $row->source_user_id ? (int) $row->source_user_id : null;

            return [
                'user_id' => $userId,
                'full_name' => $userId ? ($users->get($userId)?->full_name ?? 'Unknown') : 'Unassigned',
                'quantity' => (float) $row->quantity,
                'lines' => (int) $row->lines,
                'orders' => (int) $row->orders,
            ];
        })->values()->all();

        $recent = OrderMaterialUsageLog::query()
            ->with(['sourceUser', 'releasedByUser'])
            ->whereBetween('used_at', [$period['start'], $period['end']])
            ->latest('used_at')
            ->limit(40)
            ->get();

        return [
            'period' => $period,
            'period_type' => $periodType,
            'totals' => [
                'lines' => $lines,
                'quantity' => round($quantity, 2),
                'orders' => $orders,
                'items' => $items,
            ],
            'by_item' => $byItem,
            'by_sales' => $bySalesRows,
            'recent' => $recent,
        ];
    }

    /**
     * Build a text body for directed weekly/monthly admin reports.
     */
    public function summaryBody(string $periodType): string
    {
        $report = $this->usageReport($periodType);
        $lines = [
            'Cadence: '.$periodType,
            'Period: '.$report['period']['label'],
            'Material lines used: '.$report['totals']['lines'],
            'Total quantity: '.$report['totals']['quantity'],
            'Orders with usage: '.$report['totals']['orders'],
            'Distinct items: '.$report['totals']['items'],
            '',
            'Top items:',
        ];

        foreach (array_slice($report['by_item'], 0, 10) as $item) {
            $lines[] = '- '.$item['item_name'].': '.$item['quantity'].' '.($item['unit'] ?? 'pcs').' ('.$item['lines'].' lines)';
        }

        $lines[] = '';
        $lines[] = 'By sales source:';
        foreach (array_slice($report['by_sales'], 0, 10) as $row) {
            $lines[] = '- '.$row['full_name'].': '.$row['quantity'].' qty across '.$row['orders'].' order(s)';
        }

        return implode("\n", $lines);
    }
}
