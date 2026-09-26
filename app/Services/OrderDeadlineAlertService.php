<?php

namespace App\Services;

use App\Models\OrderPhase;
use App\Support\OrderOperations;
use Illuminate\Support\Carbon;

class OrderDeadlineAlertService
{
    public function __construct(private NotifyService $notify) {}

    public function processDueAlerts(?Carbon $now = null): int
    {
        $now ??= now();
        $sent = 0;

        $phases = OrderPhase::query()
            ->with('intake')
            ->whereNotNull('due_at')
            ->where('status', '!=', OrderOperations::PHASE_COMPLETED)
            ->get();

        foreach ($phases as $phase) {
            $intake = $phase->intake;
            if (! $intake) {
                continue;
            }

            $dueAt = $phase->due_at;
            $reminderAt = $dueAt->copy()->subHours((int) $phase->reminder_hours_before);

            if ($phase->reminder_sent_at === null && $now->greaterThanOrEqualTo($reminderAt) && $now->lessThan($dueAt)) {
                $this->notifyRoles($intake->id, $intake->invoice_number, $phase->phase_key, 'deadline_reminder', 'Deadline reminder', "Phase {$phase->phase_key} for {$intake->invoice_number} is due soon.");
                $phase->reminder_sent_at = $now;
                $phase->save();
                $sent++;
            }

            if ($phase->overdue_sent_at === null && $now->greaterThan($dueAt)) {
                $this->notifyRoles($intake->id, $intake->invoice_number, $phase->phase_key, 'deadline_overdue', 'Deadline overdue', "Phase {$phase->phase_key} for {$intake->invoice_number} is overdue.");
                $phase->overdue_sent_at = $now;
                $phase->save();
                $sent++;
            }
        }

        return $sent;
    }

    private function notifyRoles(int $intakeId, string $invoiceNumber, string $phaseKey, string $type, string $title, string $message): void
    {
        $roles = [OrderOperations::ROLE_OMS];
        if (in_array($phaseKey, [OrderOperations::PHASE_ASSEMBLY, OrderOperations::PHASE_DELIVERY], true)) {
            $roles[] = OrderOperations::ROLE_OMF;
        }
        if ($phaseKey === OrderOperations::PHASE_DESIGN) {
            $roles[] = OrderOperations::ROLE_DESIGNER;
        }

        foreach ($this->notify->activeUserIdsWithRoles($roles) as $userId) {
            $this->notify->notifyUser($userId, [
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'entityType' => 'order_intake',
                'entityId' => $intakeId,
            ]);
        }
    }
}
