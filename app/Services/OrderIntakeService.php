<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\OrderIntake;
use App\Models\OrderIntakeReview;
use App\Models\User;
use App\Support\OrderOperations;
use Illuminate\Http\Request;
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
            return $existing;
        }

        if (OrderIntake::query()
            ->where('invoice_id', $invoice->id)
            ->where('status', OrderOperations::INTAKE_ACCEPTED)
            ->exists()) {
            return null;
        }

        $intake = OrderIntake::query()->create([
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'source_type' => $sourceType,
            'source_user_id' => $invoice->created_by ?? $actor->id,
            'status' => OrderOperations::INTAKE_PENDING,
            'snapshot_json' => $invoice->snapshot_json,
        ]);

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

    public function accept(OrderIntake $intake, User $actor, ?Request $request = null): OrderIntake
    {
        $this->assertOms($actor);

        if (! in_array($intake->status, [OrderOperations::INTAKE_PENDING, OrderOperations::INTAKE_UNDER_REVIEW, OrderOperations::INTAKE_RESUBMITTED], true)) {
            throw new InvalidArgumentException('Intake cannot be accepted in its current status.');
        }

        return DB::transaction(function () use ($intake, $actor, $request) {
            $from = $intake->status;
            $intake->status = OrderOperations::INTAKE_ACCEPTED;
            $intake->reviewed_by = $actor->id;
            $intake->reviewed_at = now();
            $intake->accepted_at = now();
            $intake->rejection_reason = null;
            $intake->save();

            $this->schedule->initializePhases($intake);
            $this->recordReview($intake, $actor, 'accepted', $from, OrderOperations::INTAKE_ACCEPTED, null);

            foreach ($this->notify->activeUserIdsWithRoles([OrderOperations::ROLE_COMPANY_MANAGER]) as $userId) {
                $this->notify->notifyUser($userId, [
                    'type' => 'order_intake_accepted',
                    'title' => 'Order accepted by OMS',
                    'message' => "Invoice {$intake->invoice_number} passed OMS review (visibility only).",
                    'entityType' => 'order_intake',
                    'entityId' => (int) $intake->id,
                ]);
            }

            $intake->company_manager_notified_at = now();
            $intake->save();

            $this->audit->log($actor, 'order_intake', (int) $intake->id, 'ACCEPT_ORDER_INTAKE', [
                'invoice_number' => $intake->invoice_number,
            ], $request);

            return $intake->fresh(['phases.checkpoints']);
        });
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
