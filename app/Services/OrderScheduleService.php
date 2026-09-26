<?php

namespace App\Services;

use App\Models\OrderAssignment;
use App\Models\OrderCheckpoint;
use App\Models\OrderIntake;
use App\Models\OrderPhase;
use App\Models\User;
use App\Support\OrderOperations;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class OrderScheduleService
{
    public function __construct(
        private AuditService $audit,
        private NotifyService $notify,
    ) {}

    public function initializePhases(OrderIntake $intake): void
    {
        foreach (OrderOperations::phaseKeys() as $phaseKey) {
            $phase = OrderPhase::query()->firstOrCreate(
                [
                    'order_intake_id' => $intake->id,
                    'phase_key' => $phaseKey,
                ],
                [
                    'status' => $phaseKey === OrderOperations::PHASE_DESIGN
                        ? OrderOperations::PHASE_PENDING
                        : OrderOperations::PHASE_PENDING,
                ]
            );

            if ($phaseKey === OrderOperations::PHASE_DESIGN) {
                OrderCheckpoint::query()->firstOrCreate(
                    [
                        'order_phase_id' => $phase->id,
                        'checkpoint_key' => OrderOperations::CHECKPOINT_3D,
                    ],
                    ['label' => '3D Design Completed']
                );
                OrderCheckpoint::query()->firstOrCreate(
                    [
                        'order_phase_id' => $phase->id,
                        'checkpoint_key' => OrderOperations::CHECKPOINT_2D,
                    ],
                    ['label' => '2D Converted Design Completed']
                );
            }
        }
    }

    public function setPhaseDeadline(
        OrderPhase $phase,
        User $actor,
        Carbon $dueAt,
        int $reminderHoursBefore = 24,
        ?Request $request = null,
    ): OrderPhase {
        if (! $actor->canManageOrderSchedule()) {
            throw new InvalidArgumentException('Only OMS can update deadlines.');
        }

        $previous = $phase->due_at?->toIso8601String();
        $phase->due_at = $dueAt;
        $phase->reminder_hours_before = max(1, $reminderHoursBefore);
        $phase->reminder_sent_at = null;
        $phase->overdue_sent_at = null;
        $phase->save();

        $this->audit->log($actor, 'order_phase', (int) $phase->id, 'UPDATE_PHASE_DEADLINE', [
            'previous' => $previous,
            'due_at' => $dueAt->toIso8601String(),
            'reminder_hours_before' => $phase->reminder_hours_before,
        ], $request);

        return $phase->fresh();
    }

    public function assignUser(
        OrderIntake $intake,
        User $actor,
        User $assignee,
        string $roleKey,
        ?Request $request = null,
    ): OrderAssignment {
        if ($roleKey === OrderOperations::ROLE_DESIGNER && ! $actor->canAssignDesigner()) {
            throw new InvalidArgumentException('Only OMS can assign designers.');
        }
        if ($roleKey === OrderOperations::ROLE_ASSEMBLER && ! $actor->canAssignAssembler()) {
            throw new InvalidArgumentException('Only OMF can assign assemblers.');
        }
        if (! in_array($roleKey, [OrderOperations::ROLE_DESIGNER, OrderOperations::ROLE_ASSEMBLER], true)) {
            throw new InvalidArgumentException('Unsupported assignment role.');
        }
        if (! $assignee->hasRole($roleKey)) {
            throw new InvalidArgumentException('Assignee does not have the required role.');
        }
        if ($roleKey === OrderOperations::ROLE_DESIGNER && $intake->status !== OrderOperations::INTAKE_ACCEPTED) {
            throw new InvalidArgumentException('Company manager must approve before a designer can be assigned.');
        }

        if ($roleKey === OrderOperations::ROLE_DESIGNER) {
            OrderAssignment::query()
                ->where('order_intake_id', $intake->id)
                ->where('role_key', $roleKey)
                ->delete();
        }

        $assignment = OrderAssignment::query()->create([
            'order_intake_id' => $intake->id,
            'role_key' => $roleKey,
            'user_id' => $assignee->id,
            'assigned_by' => $actor->id,
            'assigned_at' => now(),
        ]);

        if ($roleKey === OrderOperations::ROLE_DESIGNER) {
            $design = $intake->phase(OrderOperations::PHASE_DESIGN);
            if ($design && $design->status === OrderOperations::PHASE_PENDING) {
                $design->status = OrderOperations::PHASE_IN_PROGRESS;
                $design->started_at = now();
                $design->save();
            }
        }

        if ($roleKey === OrderOperations::ROLE_ASSEMBLER) {
            $assembly = $intake->phases()->where('phase_key', OrderOperations::PHASE_ASSEMBLY)->first();
            if ($assembly && $assembly->status === OrderOperations::PHASE_PENDING) {
                $assembly->status = OrderOperations::PHASE_IN_PROGRESS;
                $assembly->started_at = now();
                $assembly->save();
            }
        }

        $this->notify->notifyUser((int) $assignee->id, [
            'type' => 'order_assignment',
            'title' => 'New order assignment',
            'message' => "You were assigned as {$roleKey} on invoice {$intake->invoice_number}.",
            'entityType' => 'order_intake',
            'entityId' => (int) $intake->id,
        ]);

        $this->audit->log($actor, 'order_assignment', (int) $assignment->id, 'ASSIGN_ORDER_USER', [
            'role_key' => $roleKey,
            'user_id' => $assignee->id,
            'order_intake_id' => $intake->id,
        ], $request);

        return $assignment;
    }
}
