<?php

namespace App\Services;

use App\Models\OrderCheckpoint;
use App\Models\OrderIntake;
use App\Models\OrderPhase;
use App\Models\User;
use App\Support\OrderOperations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderWorkflowService
{
    public function __construct(
        private AuditService $audit,
        private NotifyService $notify,
    ) {}

    public function toggleCheckpoint(
        OrderCheckpoint $checkpoint,
        User $actor,
        bool $completed,
        ?Request $request = null,
    ): OrderCheckpoint {
        $phase = $checkpoint->phase()->with('intake')->firstOrFail();
        $intake = $phase->intake;

        $isDesignerAssignee = $intake->assignments()
            ->where('role_key', OrderOperations::ROLE_DESIGNER)
            ->where('user_id', $actor->id)
            ->exists();

        if (! $actor->isDesigner() && ! $isDesignerAssignee && ! $actor->isOms() && ! $actor->isAdmin()) {
            throw new InvalidArgumentException('Not authorized to update checkpoints.');
        }

        if ($phase->phase_key !== OrderOperations::PHASE_DESIGN) {
            throw new InvalidArgumentException('Checkpoints are only supported on the design phase.');
        }

        $checkpoint->is_completed = $completed;
        $checkpoint->completed_by = $completed ? $actor->id : null;
        $checkpoint->completed_at = $completed ? now() : null;
        $checkpoint->save();

        foreach ($this->notify->activeUserIdsWithRoles([OrderOperations::ROLE_OMS]) as $omsUserId) {
            $this->notify->notifyUser($omsUserId, [
                'type' => 'order_checkpoint',
                'title' => $completed ? 'Checkpoint completed' : 'Checkpoint reopened',
                'message' => "{$checkpoint->label} on {$intake->invoice_number}.",
                'entityType' => 'order_intake',
                'entityId' => (int) $intake->id,
            ]);
        }

        $this->audit->log($actor, 'order_checkpoint', (int) $checkpoint->id, $completed ? 'COMPLETE_CHECKPOINT' : 'REOPEN_CHECKPOINT', [
            'checkpoint_key' => $checkpoint->checkpoint_key,
        ], $request);

        $this->maybeCompleteDesignPhase($phase, $actor, $request);

        return $checkpoint->fresh();
    }

    public function completeAssembly(OrderIntake $intake, User $actor, ?Request $request = null): OrderPhase
    {
        if (! $actor->canMonitorProductionDelivery()) {
            throw new InvalidArgumentException('Not authorized to complete assembly.');
        }

        $phase = $intake->phases()->where('phase_key', OrderOperations::PHASE_ASSEMBLY)->firstOrFail();
        $phase->status = OrderOperations::PHASE_COMPLETED;
        $phase->completed_at = now();
        $phase->save();

        $delivery = $intake->phases()->where('phase_key', OrderOperations::PHASE_DELIVERY)->first();
        if ($delivery && $delivery->status === OrderOperations::PHASE_PENDING) {
            $delivery->status = OrderOperations::PHASE_IN_PROGRESS;
            $delivery->started_at = now();
            $delivery->save();
        }

        $this->notifyOps($intake, 'Assembly completed', "Assembly completed for {$intake->invoice_number}.");
        $this->audit->log($actor, 'order_phase', (int) $phase->id, 'COMPLETE_ASSEMBLY', null, $request);

        return $phase->fresh();
    }

    public function completeDelivery(OrderIntake $intake, User $actor, ?Request $request = null): OrderPhase
    {
        if (! $actor->canMonitorProductionDelivery()) {
            throw new InvalidArgumentException('Not authorized to complete delivery.');
        }

        $materials = $intake->phases()->where('phase_key', OrderOperations::PHASE_MATERIALS)->first();
        $assembly = $intake->phases()->where('phase_key', OrderOperations::PHASE_ASSEMBLY)->first();
        if ($materials?->status !== OrderOperations::PHASE_COMPLETED
            || $assembly?->status !== OrderOperations::PHASE_COMPLETED) {
            throw new InvalidArgumentException('Materials and assembly must be completed before delivery.');
        }

        $phase = $intake->phases()->where('phase_key', OrderOperations::PHASE_DELIVERY)->firstOrFail();
        $phase->status = OrderOperations::PHASE_COMPLETED;
        $phase->completed_at = now();
        $phase->save();

        $this->notifyOps($intake, 'Order delivered', "Delivery completed for {$intake->invoice_number}.");
        if ($intake->source_user_id) {
            $this->notify->notifyUser((int) $intake->source_user_id, [
                'type' => 'order_delivered',
                'title' => 'Order delivered',
                'message' => "Invoice {$intake->invoice_number} was delivered.",
                'entityType' => 'order_intake',
                'entityId' => (int) $intake->id,
            ]);
        }

        $this->audit->log($actor, 'order_phase', (int) $phase->id, 'COMPLETE_DELIVERY', null, $request);

        return $phase->fresh();
    }

    private function maybeCompleteDesignPhase(OrderPhase $phase, User $actor, ?Request $request): void
    {
        $phase->load('checkpoints', 'intake');
        $allDone = $phase->checkpoints->isNotEmpty()
            && $phase->checkpoints->every(fn (OrderCheckpoint $c) => $c->is_completed);

        if (! $allDone) {
            return;
        }

        DB::transaction(function () use ($phase, $actor, $request) {
            $phase->status = OrderOperations::PHASE_COMPLETED;
            $phase->completed_at = now();
            $phase->save();

            $materials = $phase->intake->phases()->where('phase_key', OrderOperations::PHASE_MATERIALS)->first();
            if ($materials && $materials->status === OrderOperations::PHASE_PENDING) {
                $materials->status = OrderOperations::PHASE_IN_PROGRESS;
                $materials->started_at = now();
                $materials->save();
            }

            foreach ($this->notify->activeUserIdsWithRoles([OrderOperations::ROLE_OMS, OrderOperations::ROLE_COMPANY_MANAGER]) as $userId) {
                $this->notify->notifyUser($userId, [
                    'type' => 'design_completed',
                    'title' => 'Design phase completed',
                    'message' => "Design completed for {$phase->intake->invoice_number}.",
                    'entityType' => 'order_intake',
                    'entityId' => (int) $phase->intake->id,
                ]);
            }

            $this->audit->log($actor, 'order_phase', (int) $phase->id, 'COMPLETE_DESIGN_PHASE', null, $request);
        });
    }

    private function notifyOps(OrderIntake $intake, string $title, string $message): void
    {
        foreach ($this->notify->activeUserIdsWithRoles([OrderOperations::ROLE_OMS, OrderOperations::ROLE_OMF]) as $userId) {
            $this->notify->notifyUser($userId, [
                'type' => 'order_progress',
                'title' => $title,
                'message' => $message,
                'entityType' => 'order_intake',
                'entityId' => (int) $intake->id,
            ]);
        }
    }
}
