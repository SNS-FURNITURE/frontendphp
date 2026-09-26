<?php

namespace App\Services;

use App\Models\Party;
use App\Models\SalesQuota;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SalesContactService
{
    /**
     * @return array{start: Carbon, end: Carbon, label: string, period: string, period_type: string}
     */
    public function currentPeriod(string $periodType = 'monthly'): array
    {
        $now = now();

        return match ($periodType) {
            'daily' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => $now->format('M j, Y'),
                'period' => $now->format('Y-m-d'),
                'period_type' => 'daily',
            ],
            'weekly' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
                'label' => 'Week of '.$now->copy()->startOfWeek()->format('M j, Y'),
                'period' => $now->format('o-\\WW'),
                'period_type' => 'weekly',
            ],
            'yearly' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
                'label' => 'Year '.$now->format('Y'),
                'period' => $now->format('Y'),
                'period_type' => 'yearly',
            ],
            default => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'label' => $now->format('F Y'),
                'period' => $now->format('Y-m'),
                'period_type' => 'monthly',
            ],
        };
    }

    public function quotaForUser(User $user, ?string $periodType = null): SalesQuota
    {
        $periodType = $periodType ?: 'monthly';
        $period = $this->currentPeriod($periodType);

        return SalesQuota::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'period' => $period['period'],
                'period_type' => $periodType,
            ],
            [
                'quota' => 20,
                'actual' => 0,
                'metric' => 'contacts',
            ]
        );
    }

    /**
     * @return array{total: int, approved: int, pending: int, rejected: int}
     */
    public function contactCountsForUser(int $userId, Carbon $start, Carbon $end): array
    {
        $rows = Party::query()
            ->where('party_type', 'customer')
            ->where('created_by', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('approval_status, COUNT(*) as total')
            ->groupBy('approval_status')
            ->pluck('total', 'approval_status');

        return [
            'total' => (int) $rows->sum(),
            'approved' => (int) ($rows['approved'] ?? 0),
            'pending' => (int) ($rows['pending'] ?? 0),
            'rejected' => (int) ($rows['rejected'] ?? 0),
        ];
    }

    public function normalizePeriodType(?string $periodType): string
    {
        return in_array($periodType, ['daily', 'weekly', 'monthly', 'yearly'], true) ? $periodType : 'monthly';
    }

    /**
     * @return array{
     *     period: array{start: Carbon, end: Carbon, label: string, period: string, period_type: string},
     *     period_type: string,
     *     counts: array{total: int, approved: int, pending: int, rejected: int},
     *     percentages: array{approved: float, pending: float, rejected: float},
     *     total: int
     * }
     */
    public function contactReportForUser(User $user, string $periodType = 'monthly'): array
    {
        $periodType = $this->normalizePeriodType($periodType);
        $period = $this->currentPeriod($periodType);
        $counts = $this->contactCountsForUser((int) $user->id, $period['start'], $period['end']);
        $total = $counts['total'];
        $percent = static fn (int $value): float => $total > 0
            ? round(($value / $total) * 100, 1)
            : 0.0;

        return [
            'period' => $period,
            'period_type' => $periodType,
            'counts' => $counts,
            'percentages' => [
                'approved' => $percent($counts['approved']),
                'pending' => $percent($counts['pending']),
                'rejected' => $percent($counts['rejected']),
            ],
            'total' => $total,
        ];
    }

    /**
     * @return array{quota: float, approved: int, pending: int, rejected: int, total: int, pct: float, period_label: string, period: string}
     */
    public function dashboardStats(User $user, string $periodType = 'monthly'): array
    {
        $periodType = $this->normalizePeriodType($periodType);
        $period = $this->currentPeriod($periodType);
        $quotaRow = $this->quotaForUser($user, $periodType);
        $counts = $this->contactCountsForUser((int) $user->id, $period['start'], $period['end']);
        $target = (float) $quotaRow->quota;
        $approved = $counts['approved'];
        $pct = $target > 0 ? round(($approved / $target) * 100, 1) : 0;

        return [
            'quota' => $target,
            'approved' => $approved,
            'pending' => $counts['pending'],
            'rejected' => $counts['rejected'],
            'total' => $counts['total'],
            'pct' => $pct,
            'period_label' => $period['label'],
            'period' => $period['period'],
        ];
    }

    /**
     * @return Collection<int, array{user: User, stats: array<string, mixed>}>
     */
    public function teamStatsForRole(string $roleName, string $periodType = 'monthly'): Collection
    {
        $period = $this->currentPeriod($periodType);

        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', $roleName))
            ->orderBy('full_name')
            ->get()
            ->map(function (User $user) use ($period, $periodType) {
                $quotaRow = $this->quotaForUser($user, $periodType);
                $counts = $this->contactCountsForUser((int) $user->id, $period['start'], $period['end']);
                $target = (float) $quotaRow->quota;

                return [
                    'user' => $user,
                    'stats' => [
                        'quota' => $target,
                        'approved' => $counts['approved'],
                        'pending' => $counts['pending'],
                        'rejected' => $counts['rejected'],
                        'total' => $counts['total'],
                        'pct' => $target > 0 ? round(($counts['approved'] / $target) * 100, 1) : 0,
                    ],
                ];
            });
    }

    /**
     * Sales supervisor review counts (contacts they approved/rejected in period).
     *
     * @return array{approved: int, rejected: int, pending_queue: int}
     */
    public function supervisorReviewStats(User $supervisor, Carbon $start, Carbon $end): array
    {
        $approved = Party::query()
            ->where('party_type', 'customer')
            ->where('approved_by', $supervisor->id)
            ->where('approval_status', 'approved')
            ->whereBetween('updated_at', [$start, $end])
            ->count();

        $rejected = Party::query()
            ->where('party_type', 'customer')
            ->where('approved_by', $supervisor->id)
            ->where('approval_status', 'rejected')
            ->whereBetween('updated_at', [$start, $end])
            ->count();

        $pendingQueue = Party::query()
            ->where('party_type', 'customer')
            ->where('approval_status', 'pending')
            ->count();

        return [
            'approved' => $approved,
            'rejected' => $rejected,
            'pending_queue' => $pendingQueue,
        ];
    }
}
