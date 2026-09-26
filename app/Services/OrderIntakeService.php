<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\OrderIntake;
use App\Models\OrderIntakeReview;
use App\Models\User;
use App\Support\OrderOperations;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderIntakeService
{
    public function __construct(
        private AuditService $audit,
        private NotifyService $notify,
        private OrderScheduleService $schedule,
    ) {}

    public function enqueueFromApproval(Invoice $invoice, User $actor, ?Request $request = null): ?OrderIntake
    {
        return $this->enqueue($invoice, $actor, 'approved_invoice', $request);
    }

    public function enqueueFromSalesSupervisorIssue(Invoice $invoice, User $actor, ?Request $request = null): ?OrderIntake
    {
        if (! $actor->isSalesSupervisor()) {
            return null;
        }

        if ($invoice->status !== 'issued') {
            return null;
        }

        return $this->enqueue($invoice, $actor, 'sales_supervisor_issue', $request);
    }

    public function enqueue(Invoice $invoice, User $actor, string $sourceType, ?Request $request = null): ?OrderIntake
    {
        $existing = OrderIntake::query()
            ->where('invoice_id', $invoice->id)
            ->whereIn('status', OrderOperations::intakeOpenStatuses())
            ->first();

        if ($existing) {
            if ($sourceType === 'approved_invoice') {
                $existing->source_type = 'approved_invoice';
                $existing->snapshot_json = $invoice->snapshot_json;
                $existing->approved_snapshot_json = $invoice->snapshot_json;
                $existing->invoice_number = $invoice->invoice_number;
                $existing->save();
            } elseif ($sourceType === 'sales_supervisor_issue' && blank($existing->issued_snapshot_json)) {
                $existing->issued_snapshot_json = $invoice->snapshot_json;
                $existing->snapshot_json = $invoice->snapshot_json;
                $existing->save();
            }

            return $existing->fresh();
        }

        if (OrderIntake::query()
            ->where('invoice_id', $invoice->id)
            ->whereIn('status', OrderOperations::intakePostReviewStatuses())
            ->exists()) {
            return null;
        }

        $payload = [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'source_type' => $sourceType,
            'source_user_id' => $invoice->created_by ?? $actor->id,
            'status' => OrderOperations::INTAKE_PENDING,
            'snapshot_json' => $invoice->snapshot_json,
        ];

        if ($sourceType === 'sales_supervisor_issue') {
            $payload['issued_snapshot_json'] = $invoice->snapshot_json;
        }

        if ($sourceType === 'approved_invoice') {
            $payload['approved_snapshot_json'] = $invoice->snapshot_json;
        }

        $intake = OrderIntake::query()->create($payload);

        $this->recordReview($intake, $actor, 'enqueued', null, OrderOperations::INTAKE_PENDING, 'Invoice entered OMS queue');

        $this->audit->log($actor, 'order_intake', (int) $intake->id, 'ENQUEUE_ORDER_INTAKE', [
            'invoice_id' => $invoice->id,
            'source_type' => $sourceType,
        ], $request);

        foreach ($this->notify->activeUserIdsWithRoles([OrderOperations::ROLE_OMS]) as $omsUserId) {
            $this->notify->notifyUser($omsUserId, [
                'type' => 'order_intake_incoming',
                'title' => 'New incoming order',
                'message' => "Invoice {$invoice->invoice_number} is waiting for OMS review.",
                'entityType' => 'order_intake',
                'entityId' => (int) $intake->id,
            ]);
        }

        return $intake;
    }

    public function startReview(OrderIntake $intake, User $actor, ?Request $request = null): OrderIntake
    {
        $this->assertOms($actor);

        if (! in_array($intake->status, [OrderOperations::INTAKE_PENDING, OrderOperations::INTAKE_RESUBMITTED], true)) {
            throw new InvalidArgumentException('Intake is not awaiting review.');
        }

        $from = $intake->status;
        $intake->status = OrderOperations::INTAKE_UNDER_REVIEW;
        $intake->reviewed_by = $actor->id;
        $intake->reviewed_at = now();
        $intake->save();

        $this->recordReview($intake, $actor, 'under_review', $from, OrderOperations::INTAKE_UNDER_REVIEW, null);
        $this->audit->log($actor, 'order_intake', (int) $intake->id, 'START_ORDER_INTAKE_REVIEW', null, $request);

        return $intake->fresh();
    }

    /**
     * OMS evaluates and forwards to Company Manager (does not unlock designer assignment yet).
     */
    public function sendToCompanyManager(
        OrderIntake $intake,
        User $actor,
        ?Request $request = null,
        ?Carbon $cmDueAt = null,
        int $reminderHoursBefore = 24,
    ): OrderIntake {
        $this->assertOms($actor);

        if (! in_array($intake->status, [OrderOperations::INTAKE_PENDING, OrderOperations::INTAKE_UNDER_REVIEW, OrderOperations::INTAKE_RESUBMITTED], true)) {
            throw new InvalidArgumentException('Intake cannot be sent to company manager in its current status.');
        }

        if ($cmDueAt === null) {
            throw new InvalidArgumentException('OMS must set a company manager approval deadline.');
        }

        if ($cmDueAt->lessThanOrEqualTo(now())) {
            throw new InvalidArgumentException('Company manager deadline must be in the future.');
        }

        return DB::transaction(function () use ($intake, $actor, $request, $cmDueAt, $reminderHoursBefore) {
            if ($intake->invoice) {
                $intake->snapshot_json = $intake->invoice->snapshot_json;
                $intake->invoice_number = $intake->invoice->invoice_number;
            }

            $from = $intake->status;
            $intake->status = OrderOperations::INTAKE_AWAITING_CM;
            $intake->reviewed_by = $actor->id;
            $intake->reviewed_at = now();
            $intake->rejection_reason = null;
            $intake->company_manager_notified_at = now();
            $intake->cm_due_at = $cmDueAt;
            $intake->cm_reminder_hours_before = max(1, $reminderHoursBefore);
            $intake->cm_reminder_sent_at = null;
            $intake->cm_overdue_sent_at = null;
            $intake->save();

            $this->recordReview($intake, $actor, 'sent_to_cm', $from, OrderOperations::INTAKE_AWAITING_CM, 'CM deadline '.$cmDueAt->toDateTimeString());

            $dueLabel = $cmDueAt->timezone(config('app.timezone'))->format('Y-m-d H:i');
            foreach ($this->notify->activeUserIdsWithRoles([OrderOperations::ROLE_COMPANY_MANAGER]) as $userId) {
                $this->notify->notifyUser($userId, [
                    'type' => 'order_intake_awaiting_cm',
                    'title' => 'Order needs your approval',
                    'message' => "Invoice {$intake->invoice_number} needs approval by {$dueLabel} (deadline set by OMS).",
                    'entityType' => 'order_intake',
                    'entityId' => (int) $intake->id,
                ]);
            }

            $this->audit->log($actor, 'order_intake', (int) $intake->id, 'SEND_ORDER_INTAKE_TO_CM', [
                'invoice_number' => $intake->invoice_number,
                'cm_due_at' => $cmDueAt->toIso8601String(),
            ], $request);

            return $intake->fresh();
        });
    }

    /**
     * @deprecated Use sendToCompanyManager(); kept as alias for older callers/tests.
     */
    public function accept(OrderIntake $intake, User $actor, ?Request $request = null): OrderIntake
    {
        return $this->sendToCompanyManager(
            $intake,
            $actor,
            $request,
            now()->addDays(2),
            24,
        );
    }

    public function approveByCompanyManager(OrderIntake $intake, User $actor, ?Request $request = null): OrderIntake
    {
        $this->assertCompanyManager($actor);

        if ($intake->status !== OrderOperations::INTAKE_AWAITING_CM) {
            throw new InvalidArgumentException('Intake is not awaiting company manager approval.');
        }

        return DB::transaction(function () use ($intake, $actor, $request) {
            $from = $intake->status;
            $intake->status = OrderOperations::INTAKE_ACCEPTED;
            $intake->accepted_at = now();
            $intake->rejection_reason = null;
            $intake->save();

            $this->schedule->initializePhases($intake);
            $this->recordReview($intake, $actor, 'cm_approved', $from, OrderOperations::INTAKE_ACCEPTED, null);

            foreach ($this->notify->activeUserIdsWithRoles([OrderOperations::ROLE_OMS]) as $omsUserId) {
                $this->notify->notifyUser($omsUserId, [
                    'type' => 'order_intake_cm_approved',
                    'title' => 'Ready to assign designer',
                    'message' => "Company manager approved {$intake->invoice_number}. You can assign a designer.",
                    'entityType' => 'order_intake',
                    'entityId' => (int) $intake->id,
                ]);
            }

            $this->audit->log($actor, 'order_intake', (int) $intake->id, 'CM_APPROVE_ORDER_INTAKE', [
                'invoice_number' => $intake->invoice_number,
            ], $request);

            return $intake->fresh(['phases.checkpoints']);
        });
    }

    public function rejectByCompanyManager(OrderIntake $intake, User $actor, string $reason, ?Request $request = null): OrderIntake
    {
        $this->assertCompanyManager($actor);

        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Rejection reason is required.');
        }

        if ($intake->status !== OrderOperations::INTAKE_AWAITING_CM) {
            throw new InvalidArgumentException('Intake is not awaiting company manager approval.');
        }

        $from = $intake->status;
        $intake->status = OrderOperations::INTAKE_UNDER_REVIEW;
        $intake->rejection_reason = $reason;
        $intake->save();

        $this->recordReview($intake, $actor, 'cm_rejected', $from, OrderOperations::INTAKE_UNDER_REVIEW, $reason);

        foreach ($this->notify->activeUserIdsWithRoles([OrderOperations::ROLE_OMS]) as $omsUserId) {
            $this->notify->notifyUser($omsUserId, [
                'type' => 'order_intake_cm_rejected',
                'title' => 'Company manager returned order',
                'message' => "Invoice {$intake->invoice_number} was returned: {$reason}",
                'entityType' => 'order_intake',
                'entityId' => (int) $intake->id,
            ]);
        }

        $this->audit->log($actor, 'order_intake', (int) $intake->id, 'CM_REJECT_ORDER_INTAKE', [
            'reason' => $reason,
        ], $request);

        return $intake->fresh();
    }

    public function reject(OrderIntake $intake, User $actor, string $reason, ?Request $request = null): OrderIntake
    {
        $this->assertOms($actor);

        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Rejection reason is required.');
        }

        if (! in_array($intake->status, [OrderOperations::INTAKE_PENDING, OrderOperations::INTAKE_UNDER_REVIEW, OrderOperations::INTAKE_RESUBMITTED], true)) {
            throw new InvalidArgumentException('Intake cannot be rejected in its current status.');
        }

        $from = $intake->status;
        $intake->status = OrderOperations::INTAKE_REJECTED;
        $intake->rejection_reason = $reason;
        $intake->reviewed_by = $actor->id;
        $intake->reviewed_at = now();
        $intake->save();

        $this->recordReview($intake, $actor, 'rejected', $from, OrderOperations::INTAKE_REJECTED, $reason);

        if ($intake->source_user_id) {
            $this->notify->notifyUser((int) $intake->source_user_id, [
                'type' => 'order_intake_rejected',
                'title' => 'Order rejected by OMS',
                'message' => "Invoice {$intake->invoice_number} was rejected: {$reason}",
                'entityType' => 'order_intake',
                'entityId' => (int) $intake->id,
            ]);
        }

        $this->audit->log($actor, 'order_intake', (int) $intake->id, 'REJECT_ORDER_INTAKE', [
            'reason' => $reason,
        ], $request);

        return $intake->fresh();
    }

    public function resubmit(OrderIntake $intake, User $actor, ?string $comment = null, ?Request $request = null): OrderIntake
    {
        if ($intake->status !== OrderOperations::INTAKE_REJECTED) {
            throw new InvalidArgumentException('Only rejected intakes can be resubmitted.');
        }

        $isSource = (int) $intake->source_user_id === (int) $actor->id;
        $isApproverRole = $actor->isAdmin() || $actor->isMarketingManager() || $actor->isSalesSupervisor();
        if (! $isSource && ! $isApproverRole) {
            throw new InvalidArgumentException('You are not allowed to resubmit this intake.');
        }

        if ($intake->invoice) {
            $intake->snapshot_json = $intake->invoice->snapshot_json;
            $intake->invoice_number = $intake->invoice->invoice_number;
        }

        $from = $intake->status;
        $intake->status = OrderOperations::INTAKE_RESUBMITTED;
        $intake->resubmit_comment = $comment;
        $intake->save();

        $this->recordReview($intake, $actor, 'resubmitted', $from, OrderOperations::INTAKE_RESUBMITTED, $comment);

        foreach ($this->notify->activeUserIdsWithRoles([OrderOperations::ROLE_OMS]) as $omsUserId) {
            $this->notify->notifyUser($omsUserId, [
                'type' => 'order_intake_resubmitted',
                'title' => 'Order resubmitted',
                'message' => "Invoice {$intake->invoice_number} was resubmitted for OMS review.",
                'entityType' => 'order_intake',
                'entityId' => (int) $intake->id,
            ]);
        }

        $this->audit->log($actor, 'order_intake', (int) $intake->id, 'RESUBMIT_ORDER_INTAKE', [
            'comment' => $comment,
        ], $request);

        return $intake->fresh();
    }

    private function assertOms(User $actor): void
    {
        if (! $actor->canReviewOrderIntake()) {
            throw new InvalidArgumentException('Only OMS can perform this action.');
        }
    }

    private function assertCompanyManager(User $actor): void
    {
        if (! $actor->hasRole(OrderOperations::ROLE_COMPANY_MANAGER) && ! $actor->isAdmin()) {
            throw new InvalidArgumentException('Only company manager can perform this action.');
        }
    }

    private function recordReview(
        OrderIntake $intake,
        User $actor,
        string $action,
        ?string $from,
        ?string $to,
        ?string $comment,
    ): void {
        OrderIntakeReview::query()->create([
            'order_intake_id' => $intake->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'comment' => $comment,
            'actor_user_id' => $actor->id,
            'actor_role' => $actor->roles->first()?->name,
        ]);
    }
}
