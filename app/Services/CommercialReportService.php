<?php

namespace App\Services;

use App\Models\Party;
use App\Models\User;
use Illuminate\Support\Collection;

class CommercialReportService
{
    public function __construct(
        private DirectedReportService $directedReports,
        private NotifyService $notify,
        private SalesContactService $contacts,
    ) {}

    public function reportContactSubmitted(Party $party): void
    {
        $creator = $party->creator;
        if (! $creator) {
            return;
        }

        $stats = $this->contacts->dashboardStats($creator);
        $title = 'Sales contact submitted — '.$party->name;
        $body = implode("\n", [
            'Action: contact_submitted',
            'Sales rep: '.$creator->full_name,
            'Contact: '.$party->name,
            'Phone: '.($party->phone ?: '—'),
            'Status: pending supervisor review',
            'Period: '.$stats['period_label'],
            'Approved this period: '.$stats['approved'].' / '.$stats['quota'],
        ]);

        $this->sendToMarketingManagers($title, $body, 'commercial_contact_submitted', 'party', (int) $party->id);
    }

    public function reportContactReviewed(Party $party): void
    {
        $creator = $party->creator;
        $reviewer = $party->approver;
        $stats = $creator ? $this->contacts->dashboardStats($creator) : null;

        $title = 'Contact '.strtoupper((string) $party->approval_status).' — '.$party->name;
        $body = implode("\n", [
            'Action: contact_'.strtolower((string) $party->approval_status),
            'Contact: '.$party->name,
            'Submitted by: '.($creator?->full_name ?: 'Unknown'),
            'Reviewed by: '.($reviewer?->full_name ?: 'Unknown'),
            $stats ? 'Sales quota progress: '.$stats['approved'].' / '.$stats['quota'].' ('.$stats['period_label'].')' : '',
        ]);

        $this->sendToMarketingManagers($title, $body, 'commercial_contact_reviewed', 'party', (int) $party->id);

        if ($creator) {
            $this->notify->notifyUser((int) $creator->id, [
                'type' => 'contact_reviewed',
                'title' => $title,
                'message' => 'Your contact '.$party->name.' was '.$party->approval_status.'.',
                'entityType' => 'party',
                'entityId' => (int) $party->id,
            ]);
        }
    }

    public function generatePeriodicReports(string $cadence): int
    {
        $count = 0;
        $period = $this->contacts->currentPeriod($cadence);

        foreach ($this->contacts->teamStatsForRole('sales', $cadence) as $row) {
            $user = $row['user'];
            $stats = $row['stats'];
            $title = ucfirst($cadence).' sales contacts — '.$user->full_name;
            $body = implode("\n", [
                'Cadence: '.$cadence,
                'Period: '.$period['label'],
                'Sales rep: '.$user->full_name,
                'Contact quota: '.$stats['quota'],
                'Submitted: '.$stats['total'],
                'Approved: '.$stats['approved'],
                'Pending: '.$stats['pending'],
                'Rejected: '.$stats['rejected'],
                'Achievement: '.$stats['pct'].'%',
            ]);

            if ($this->sendToMarketingManagers($title, $body, 'commercial_'.$cadence.'_sales', 'user', (int) $user->id)) {
                $count++;
            }
        }

        $supervisors = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', 'sales_supervisor'))
            ->orderBy('full_name')
            ->get();

        foreach ($supervisors as $supervisor) {
            $review = $this->contacts->supervisorReviewStats($supervisor, $period['start'], $period['end']);
            $title = ucfirst($cadence).' supervisor reviews — '.$supervisor->full_name;
            $body = implode("\n", [
                'Cadence: '.$cadence,
                'Period: '.$period['label'],
                'Supervisor: '.$supervisor->full_name,
                'Contacts approved: '.$review['approved'],
                'Contacts rejected: '.$review['rejected'],
                'Pending in queue now: '.$review['pending_queue'],
            ]);

            if ($this->sendToMarketingManagers($title, $body, 'commercial_'.$cadence.'_supervisor', 'user', (int) $supervisor->id)) {
                $count++;
            }
        }

        return $count;
    }

    private function sendToMarketingManagers(
        string $title,
        string $body,
        string $reportType,
        ?string $entityType = null,
        ?int $entityId = null,
    ): bool {
        $managers = $this->marketingManagers();
        if ($managers->isEmpty()) {
            return false;
        }

        foreach ($managers as $manager) {
            $this->directedReports->insertDirectedReport(
                $title,
                $body,
                $reportType,
                null,
                (int) $manager->id,
                (string) $manager->full_name,
                $entityType,
                $entityId,
            );
        }

        return true;
    }

    /**
     * @return Collection<int, User>
     */
    private function marketingManagers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', 'marketing_manager'))
            ->get();
    }
}
