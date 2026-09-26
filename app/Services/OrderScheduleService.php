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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OrderScheduleService
{
    public function __construct(
        private AuditService $audit,
        private NotifyService $notify,
    ) {}

    /**
     * Save the full OMS schedule in one shot: phase names/deadlines, new phases, designer + PM.
     *
     * @param  list<array{id?:int|string|null, label:string, due_at?:string|null, reminder_hours_before?:int|string|null}>  $phases
     * @param  list<array{label:string, due_at?:string|null, reminder_hours_before?:int|string|null}>  $newPhases
     */
    public function saveSchedule(
        OrderIntake $intake,
        User $actor,
        array $phases,
        array $newPhases = [],
        ?int $designerUserId = null,
        ?int $productManagerUserId = null,
        ?Request $request = null,
    ): OrderIntake {
        if (! $actor->canManageOrderSchedule()) {
            throw new InvalidArgumentException('Only OMS can save the schedule.');
        }

        if ($intake->status !== OrderOperations::INTAKE_ACCEPTED) {
            throw new InvalidArgumentException('Schedule can only be saved after company manager approval.');
        }

        if ($intake->phases()->count() === 0) {
            $this->initializePhases($intake);
            $intake->load('phases');
        }

        return DB::transaction(function () use ($intake, $actor, $phases, $newPhases, $designerUserId, $productManagerUserId, $request) {
            foreach ($phases as $row) {
                $phaseId = (int) ($row['id'] ?? 0);
                if ($phaseId < 1) {
                    continue;
                }

                $phase = OrderPhase::query()
                    ->where('order_intake_id', $intake->id)
                    ->where('id', $phaseId)
                    ->first();

                if (! $phase) {
                    continue;
                }

                $label = trim((string) ($row['label'] ?? ''));
                if ($label === '') {
                    throw new InvalidArgumentException('Every phase needs a name.');
                }

                $dueRaw = $row['due_at'] ?? null;
                if (blank($dueRaw)) {
                    throw new InvalidArgumentException("Set a deadline for \"{$label}\".");
                }

                $dueAt = Carbon::parse((string) $dueRaw);
                $reminder = max(1, (int) ($row['reminder_hours_before'] ?? 24));

                $phase->label = $label;
                $phase->due_at = $dueAt;
                $phase->reminder_hours_before = $reminder;
                $phase->reminder_sent_at = null;
                $phase->overdue_sent_at = null;
                $phase->save();
            }

            foreach ($newPhases as $row) {
                $label = trim((string) ($row['label'] ?? ''));
                if ($label === '') {
                    continue;
                }

                $dueRaw = $row['due_at'] ?? null;
                $dueAt = blank($dueRaw) ? null : Carbon::parse((string) $dueRaw);
                if ($dueAt === null) {
                    throw new InvalidArgumentException("Set a deadline for new phase \"{$label}\".");
                }

                $this->addCustomPhase(
                    $intake,
                    $actor,
                    $label,
                    $dueAt,
                    max(1, (int) ($row['reminder_hours_before'] ?? 24)),
                    $request,
                );
            }

            $intake = $intake->fresh(['phases', 'assignments']);

            if ($designerUserId) {
                $current = $intake->assignments->firstWhere('role_key', OrderOperations::ROLE_DESIGNER);
                if (! $current || (int) $current->user_id !== $designerUserId) {
                    $designer = User::query()->findOrFail($designerUserId);
                    $this->assignUser($intake, $actor, $designer, OrderOperations::ROLE_DESIGNER, $request);
                    $intake = $intake->fresh(['phases', 'assignments']);
                }
            }

            $pmUserId = $productManagerUserId;
            if (! $pmUserId) {
                $pmUserId = User::soleProductManager()?->id;
            }

            if ($pmUserId) {
                $current = $intake->assignments->firstWhere('role_key', OrderOperations::ROLE_PRODUCT_MANAGER);
                if (! $current || (int) $current->user_id !== $pmUserId) {
                    $pm = User::query()->findOrFail($pmUserId);
                    $this->assignUser($intake, $actor, $pm, OrderOperations::ROLE_PRODUCT_MANAGER, $request);
                }
            }

            $this->audit->log($actor, 'order_intake', (int) $intake->id, 'SAVE_ORDER_SCHEDULE', [
                'phase_count' => $intake->phases()->count(),
                'designer_user_id' => $designerUserId,
                'product_manager_user_id' => $pmUserId,
            ], $request);

            return $intake->fresh(['phases', 'assignments.user']);
        });
    }

    public function initializePhases(OrderIntake $intake): void
    {
        $sort = 0;
        foreach (OrderOperations::defaultPhaseLabels() as $phaseKey => $label) {
            $phase = OrderPhase::query()->firstOrCreate(
                [
                    'order_intake_id' => $intake->id,
                    'phase_key' => $phaseKey,
                ],
                [
                    'label' => $label,
                    'sort_order' => $sort,
                    'status' => OrderOperations::PHASE_PENDING,
                ]
            );

            if ($phase->label === null) {
                $phase->label = $label;
                $phase->sort_order = $sort;
                $phase->save();
            }

            if ($phaseKey === OrderOperations::PHASE_DESIGN) {
                $this->ensureDesignCheckpoints($phase);
            }

            $sort++;
        }
    }

    public function addCustomPhase(
        OrderIntake $intake,
        User $actor,
        string $label,
        ?Carbon $dueAt = null,
        int $reminderHoursBefore = 24,
        ?Request $request = null,
    ): OrderPhase {
        if (! $actor->canManageOrderSchedule()) {
            throw new InvalidArgumentException('Only OMS can add schedule phases.');
        }

        if ($intake->status !== OrderOperations::INTAKE_ACCEPTED) {
            throw new InvalidArgumentException('Phases can only be added after company manager approval.');
        }

        $label = trim($label);
        if ($label === '') {
            throw new InvalidArgumentException('Phase name is required.');
        }

        $baseKey = Str::slug($label, '_');
        if ($baseKey === '') {
            $baseKey = 'custom';
        }

        $phaseKey = $baseKey;
        $suffix = 2;
        while (OrderPhase::query()
            ->where('order_intake_id', $intake->id)
            ->where('phase_key', $phaseKey)
            ->exists()) {
            $phaseKey = $baseKey.'_'.$suffix;
            $suffix++;
        }

        $sortOrder = (int) OrderPhase::query()
            ->where('order_intake_id', $intake->id)
            ->max('sort_order') + 1;

        $phase = OrderPhase::query()->create([
            'order_intake_id' => $intake->id,
            'phase_key' => $phaseKey,
            'label' => $label,
            'sort_order' => $sortOrder,
            'status' => OrderOperations::PHASE_PENDING,
            'due_at' => $dueAt,
            'reminder_hours_before' => max(1, $reminderHoursBefore),
        ]);

        $this->audit->log($actor, 'order_phase', (int) $phase->id, 'ADD_CUSTOM_PHASE', [
            'label' => $label,
            'phase_key' => $phaseKey,
        ], $request);

        return $phase;
    }

    public function updatePhaseLabel(
        OrderPhase $phase,
        User $actor,
        string $label,
        ?Request $request = null,
    ): OrderPhase {
        if (! $actor->canManageOrderSchedule()) {
            throw new InvalidArgumentException('Only OMS can rename phases.');
        }

        $label = trim($label);
        if ($label === '') {
            throw new InvalidArgumentException('Phase name is required.');
        }

        $phase->label = $label;
        $phase->save();

        $this->audit->log($actor, 'order_phase', (int) $phase->id, 'RENAME_PHASE', [
            'label' => $label,
        ], $request);

        return $phase->fresh();
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
        if ($roleKey === OrderOperations::ROLE_PRODUCT_MANAGER && ! $actor->canAssignProductManager()) {
            throw new InvalidArgumentException('Only OMS can assign product managers.');
        }
        if ($roleKey === OrderOperations::ROLE_ASSEMBLER && ! $actor->canAssignAssembler()) {
            throw new InvalidArgumentException('Only OMF can assign assemblers.');
        }
        if (! in_array($roleKey, [
            OrderOperations::ROLE_DESIGNER,
            OrderOperations::ROLE_PRODUCT_MANAGER,
            OrderOperations::ROLE_ASSEMBLER,
        ], true)) {
            throw new InvalidArgumentException('Unsupported assignment role.');
        }
        if (! $assignee->hasRole($roleKey)) {
            throw new InvalidArgumentException('Assignee does not have the required role.');
        }
        if (in_array($roleKey, [OrderOperations::ROLE_DESIGNER, OrderOperations::ROLE_PRODUCT_MANAGER], true)
            && $intake->status !== OrderOperations::INTAKE_ACCEPTED) {
            throw new InvalidArgumentException('Company manager must approve before scheduling assignments.');
        }
        if ($roleKey === OrderOperations::ROLE_DESIGNER) {
            $design = $intake->phase(OrderOperations::PHASE_DESIGN)
                ?? $intake->phases()->where('phase_key', OrderOperations::PHASE_DESIGN)->first();
            if (! $design || $design->due_at === null) {
                throw new InvalidArgumentException('OMS must set the designer deadline before assigning.');
            }
        }
        if ($roleKey === OrderOperations::ROLE_PRODUCT_MANAGER) {
            $factory = $intake->phase(OrderOperations::PHASE_FACTORY_COLORING)
                ?? $intake->phases()->where('phase_key', OrderOperations::PHASE_FACTORY_COLORING)->first();
            if (! $factory || $factory->due_at === null) {
                throw new InvalidArgumentException('OMS must set the factory coloring deadline before assigning a product manager.');
            }
        }

        if (in_array($roleKey, [OrderOperations::ROLE_DESIGNER, OrderOperations::ROLE_PRODUCT_MANAGER], true)) {
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

        if ($roleKey === OrderOperations::ROLE_PRODUCT_MANAGER) {
            $factory = $intake->phases()->where('phase_key', OrderOperations::PHASE_FACTORY_COLORING)->first();
            if ($factory && $factory->status === OrderOperations::PHASE_PENDING) {
                $factory->status = OrderOperations::PHASE_IN_PROGRESS;
                $factory->started_at = now();
                $factory->save();
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

    private function ensureDesignCheckpoints(OrderPhase $phase): void
    {
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
        OrderCheckpoint::query()->firstOrCreate(
            [
                'order_phase_id' => $phase->id,
                'checkpoint_key' => OrderOperations::CHECKPOINT_MEASUREMENT,
            ],
            ['label' => 'Measurement Done']
        );
    }
}
