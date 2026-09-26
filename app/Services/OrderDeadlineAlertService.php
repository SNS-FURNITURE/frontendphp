<?php

namespace App\Services;

use App\Models\OrderIntake;
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

        $sent += $this->processPhaseAlerts($now);
        $sent += $this->processCompanyManagerAlerts($now);

        return $sent;
    }

    private function processPhaseAlerts(Carbon $now): int
    {
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
            $reminderAt = $dueAt->copy()->subHours((int) ($phase->reminder_hours_before ?? 24));

            if ($phase->reminder_sent_at === null && $now->greaterThanOrEqualTo($reminderAt) && $now->lessThan($dueAt)) {
                $this->notifyRoles(
                    (int) $intake->id,
                    $intake->invoice_number,
                    $phase->phase_key,
                    'deadline_reminder',
                    'Deadline reminder',
                    "Phase {$phase->phase_key} for {$intake->invoice_number} is due soon."
                );
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

    private function processCompanyManagerAlerts(Carbon $now): int
    {
        $sent = 0;

        $intakes = OrderIntake::query()
            ->where('status', OrderOperations::INTAKE_AWAITING_CM)
            ->whereNotNull('cm_due_at')
            ->get();

        foreach ($intakes as $intake) {
            $dueAt = $intake->cm_due_at;
            $reminderHours = (int) ($intake->cm_reminder_hours_before ?: 24);
            $reminderAt = $dueAt->copy()->subHours($reminderHours);

            if ($intake->cm_reminder_sent_at === null && $now->greaterThanOrEqualTo($reminderAt) && $now->lessThan($dueAt)) {
                $this->notifyCompanyManagerDeadline(
                    $intake,
                    'cm_deadline_reminder',
                    'CM approval due soon',
                    "Approve {$intake->invoice_number} by {$dueAt->format('Y-m-d H:i')} (OMS deadline)."
                );
                $intake->cm_reminder_sent_at = $now;
                $intake->save();
                $sent++;
            }

            if ($intake->cm_overdue_sent_at === null && $now->greaterThan($dueAt)) {
                $this->notifyCompanyManagerDeadline(
                    $intake,
                    'cm_deadline_overdue',
                    'CM approval overdue',
                    "Company manager approval for {$intake->invoice_number} is overdue (OMS deadline {$dueAt->format('Y-m-d H:i')})."
                );
                $intake->cm_overdue_sent_at = $now;
                $intake->save();
                $sent++;
            }
        }

        return $sent;
    }

    private function notifyCompanyManagerDeadline(OrderIntake $intake, string $type, string $title, string $message): void
    {
        $roles = [OrderOperations::ROLE_OMS, OrderOperations::ROLE_COMPANY_MANAGER];
        foreach ($this->notify->activeUserIdsWithRoles($roles) as $userId) {
            $this->notify->notifyUser($userId, [
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'entityType' => 'order_intake',
                'entityId' => (int) $intake->id,
            ]);
        }
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
        if ($phaseKey === OrderOperations::PHASE_MATERIALS) {
            $roles[] = OrderOperations::ROLE_COMPANY_MANAGER;
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
