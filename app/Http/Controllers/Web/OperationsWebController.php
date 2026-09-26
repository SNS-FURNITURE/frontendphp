<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\OrderCheckpoint;
use App\Models\OrderIntake;
use App\Models\OrderMaterialLine;
use App\Models\OrderPhase;
use App\Models\OrderProcurementRequest;
use App\Models\OrderSupplierQuote;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\OrderIntakeService;
use App\Services\OrderMessageService;
use App\Services\OrderProcurementService;
use App\Services\OrderScheduleService;
use App\Services\OrderWorkflowService;
use App\Support\OrderOperations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use InvalidArgumentException;

class OperationsWebController extends Controller
{
    public function __construct(
        private OrderIntakeService $intakeService,
        private OrderScheduleService $scheduleService,
        private OrderWorkflowService $workflowService,
        private OrderProcurementService $procurementService,
        private OrderMessageService $messageService,
        private DocumentService $documents,
    ) {}

    public function omsDashboard(): View
    {
        return $this->omsCheckInvoice();
    }

    public function omsCheckInvoice(): View
    {
        abort_unless(auth()->user()?->canReviewOrderIntake() || auth()->user()?->canViewOrderOperations(), 403);

        $intakes = OrderIntake::query()
            ->with(['invoice', 'sourceUser', 'reviewer'])
            ->whereIn('status', OrderOperations::intakeOpenStatuses())
            ->latest('id')
            ->get();

        return view('operations.oms-dashboard', [
            'intakes' => $intakes,
            'tab' => 'check',
        ]);
    }

    public function omsSchedule(): View
    {
        abort_unless(auth()->user()?->canManageOrderSchedule() || auth()->user()?->canViewOrderOperations(), 403);

        $intakes = OrderIntake::query()
            ->with(['phases', 'assignments.user', 'sourceUser'])
            ->where('status', OrderOperations::INTAKE_ACCEPTED)
            ->latest('id')
            ->get();

        return view('operations.oms-schedule', [
            'intakes' => $intakes,
            'tab' => 'schedule',
        ]);
    }

    public function omsPipeline(): View
    {
        abort_unless(auth()->user()?->canReviewOrderIntake() || auth()->user()?->canViewOrderOperations(), 403);

        return view('operations.oms-pipeline', [
            'tab' => 'pipeline',
            'pipelineUrl' => route('operations.oms.pipeline.json'),
        ]);
    }

    public function omsPipelineJson(): JsonResponse
    {
        abort_unless(auth()->user()?->canReviewOrderIntake() || auth()->user()?->canViewOrderOperations(), 403);

        $intakes = OrderIntake::query()
            ->with([
                'phases.checkpoints',
                'assignments.user',
                'materialLines',
                'procurementRequests',
                'deliveries',
            ])
            ->whereIn('status', OrderOperations::intakePipelineStatuses())
            ->latest('id')
            ->get();

        $rows = $intakes->map(function (OrderIntake $intake): array {
            $phases = $intake->phases->sortBy('sort_order')->values()->map(function (OrderPhase $phase): array {
                return [
                    'key' => $phase->phase_key,
                    'label' => $phase->displayLabel(),
                    'status' => $phase->status ?? '—',
                    'due_at' => optional($phase->due_at)->toIso8601String(),
                    'checkpoints_done' => $phase->checkpoints->where('is_completed', true)->count(),
                    'checkpoints_total' => $phase->checkpoints->count(),
                ];
            })->all();

            $designer = $intake->assignments->firstWhere('role_key', OrderOperations::ROLE_DESIGNER);
            $productManager = $intake->assignments->firstWhere('role_key', OrderOperations::ROLE_PRODUCT_MANAGER);

            return [
                'id' => (int) $intake->id,
                'invoice_number' => $intake->invoice_number,
                'status' => $intake->status,
                'designer' => $designer?->user?->full_name,
                'product_manager' => $productManager?->user?->full_name,
                'materials_pending' => $intake->materialLines->where('stock_status', OrderOperations::STOCK_PENDING)->count(),
                'procurement_open' => $intake->procurementRequests->whereIn('status', [
                    OrderOperations::PROCUREMENT_OPEN,
                    OrderOperations::PROCUREMENT_QUOTED,
                    OrderOperations::PROCUREMENT_PENDING_APPROVAL,
                ])->count(),
                'delivery_status' => optional($intake->deliveries->sortByDesc('id')->first())->status,
                'phases' => $phases,
                'url' => route('operations.orders.show', $intake),
            ];
        })->values();

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'rows' => $rows,
        ]);
    }

    public function omfDashboard(): View
    {
        abort_unless(auth()->user()?->isOmf() || auth()->user()?->isAssembler() || auth()->user()?->canMonitorProductionDelivery() || auth()->user()?->canViewOrderOperations(), 403);

        $intakes = OrderIntake::query()
            ->with(['phases', 'assignments.user', 'deliveries'])
            ->where('status', OrderOperations::INTAKE_ACCEPTED)
            ->where(function ($q) {
                $q->whereHas('assignments', fn ($a) => $a->where('role_key', OrderOperations::ROLE_PRODUCT_MANAGER))
                    ->orWhereHas('phases', function ($p) {
                        $p->whereIn('phase_key', [
                            OrderOperations::PHASE_FACTORY_COLORING,
                            OrderOperations::PHASE_ASSEMBLY,
                            OrderOperations::PHASE_DELIVERY,
                        ]);
                    });
            })
            ->latest('id')
            ->get();

        return view('operations.omf-dashboard', [
            'intakes' => $intakes,
        ]);
    }

    public function managerDashboard(): View
    {
        abort_unless(auth()->user()?->canApproveOrderProcurement() || auth()->user()?->hasRole('company_manager') || auth()->user()?->canViewOrderOperations(), 403);

        $awaitingCm = OrderIntake::query()
            ->with(['invoice', 'sourceUser', 'reviewer'])
            ->where('status', OrderOperations::INTAKE_AWAITING_CM)
            ->latest('id')
            ->get();

        $intakes = OrderIntake::query()
            ->with(['materialLines', 'procurementRequests.proposedQuote', 'phases'])
            ->where('status', OrderOperations::INTAKE_ACCEPTED)
            ->where(function ($q) {
                $q->whereHas('materialLines', fn ($m) => $m->where('stock_status', OrderOperations::STOCK_PENDING))
                    ->orWhereHas('procurementRequests', fn ($p) => $p->whereIn('status', [
                        OrderOperations::PROCUREMENT_PENDING_APPROVAL,
                        OrderOperations::PROCUREMENT_OPEN,
                        OrderOperations::PROCUREMENT_QUOTED,
                    ]));
            })
            ->latest('id')
            ->get();

        return view('operations.manager-dashboard', [
            'awaitingCm' => $awaitingCm,
            'intakes' => $intakes,
        ]);
    }

    public function designerDashboard(): View
    {
        $user = auth()->user();
        abort_unless($user?->isDesigner() || $user?->isOms() || $user?->canViewOrderOperations(), 403);

        $query = OrderIntake::query()
            ->with(['phases.checkpoints', 'assignments.user'])
            ->where('status', OrderOperations::INTAKE_ACCEPTED);

        if ($user->isDesigner() && ! $user->isOms() && ! $user->isAdmin()) {
            $query->whereHas('assignments', function ($q) use ($user) {
                $q->where('role_key', OrderOperations::ROLE_DESIGNER)
                    ->where('user_id', $user->id);
            });
        } else {
            $query->whereHas('assignments', fn ($q) => $q->where('role_key', OrderOperations::ROLE_DESIGNER));
        }

        return view('operations.designer-dashboard', [
            'intakes' => $query->latest('id')->get(),
        ]);
    }

    public function productManagerDashboard(): View
    {
        $user = auth()->user();
        abort_unless($user?->isProductManager() || $user?->isOmf() || $user?->isOms() || $user?->canViewOrderOperations(), 403);

        $query = OrderIntake::query()
            ->with(['phases', 'assignments.user'])
            ->where('status', OrderOperations::INTAKE_ACCEPTED);

        if ($user->isProductManager() && ! $user->isOmf() && ! $user->isOms() && ! $user->isAdmin()) {
            $query->whereHas('assignments', function ($q) use ($user) {
                $q->where('role_key', OrderOperations::ROLE_PRODUCT_MANAGER)
                    ->where('user_id', $user->id);
            });
        } else {
            $query->whereHas('assignments', fn ($q) => $q->where('role_key', OrderOperations::ROLE_PRODUCT_MANAGER));
        }

        return view('operations.product-manager-dashboard', [
            'intakes' => $query->latest('id')->get(),
        ]);
    }

    public function procurementDashboard(): View
    {
        abort_unless(auth()->user()?->isProcurement() || auth()->user()?->canApproveOrderProcurement() || auth()->user()?->canViewOrderOperations(), 403);

        $requests = OrderProcurementRequest::query()
            ->with(['intake', 'materialLine', 'quotes', 'proposedQuote'])
            ->whereIn('status', [
                OrderOperations::PROCUREMENT_OPEN,
                OrderOperations::PROCUREMENT_QUOTED,
                OrderOperations::PROCUREMENT_PENDING_APPROVAL,
            ])
            ->latest('id')
            ->get();

        return view('operations.procurement-dashboard', [
            'requests' => $requests,
        ]);
    }

    public function show(OrderIntake $intake): View
    {
        abort_unless(auth()->user()?->canViewOrderOperations(), 403);

        $intake->load([
            'invoice',
            'sourceUser',
            'reviewer',
            'phases.checkpoints.completedByUser',
            'assignments.user',
            'assignments.assignedByUser',
            'materialLines.submitter',
            'materialLines.reviewer',
            'materialLines.item',
            'procurementRequests.materialLine',
            'procurementRequests.quotes.creator',
            'procurementRequests.proposedQuote',
            'procurementRequests.approver',
            'messages.sender',
            'reviews.actor',
            'deliveries',
        ]);

        $intake->setRelation('phases', $intake->phases->sortBy('sort_order')->values());

        if (auth()->user()->canMessageOpsPeer()) {
            $this->messageService->markRead($intake, auth()->user());
        }

        $user = auth()->user();
        $hidePrices = $user->isDesigner() || $user->isProductManager();
        $design = $intake->phase(OrderOperations::PHASE_DESIGN);
        $daysLeft = null;
        if ($design?->due_at) {
            $daysLeft = (int) now()->startOfDay()->diffInDays($design->due_at->copy()->startOfDay(), false);
        }

        $issuedSnapshot = $intake->issued_snapshot_json;
        if (blank($issuedSnapshot) && is_array($intake->approved_snapshot_json) && $intake->approved_snapshot_json !== []) {
            $issuedSnapshot = $intake->approved_snapshot_json;
            unset($issuedSnapshot['approval']);
            if ($user->canReviewOrderIntake()) {
                $intake->issued_snapshot_json = $issuedSnapshot;
                $intake->save();
            }
        }

        $cmSnapshot = $intake->approved_snapshot_json
            ?? $intake->snapshot_json
            ?? $issuedSnapshot
            ?? $intake->invoice?->snapshot_json;

        return view('operations.show', [
            'intake' => $intake,
            'designers' => $this->usersWithRole(OrderOperations::ROLE_DESIGNER),
            'productManagers' => $this->usersWithRole(OrderOperations::ROLE_PRODUCT_MANAGER),
            'assemblers' => $this->usersWithRole(OrderOperations::ROLE_ASSEMBLER),
            'destinations' => Delivery::DESTINATIONS,
            'hidePrices' => $hidePrices,
            'designDaysLeft' => $daysLeft,
            'issuedHtml' => $this->snapshotHtml($issuedSnapshot, ! $hidePrices),
            'approvedHtml' => $this->snapshotHtml($intake->approved_snapshot_json ?? null, ! $hidePrices),
            'cmInvoiceHtml' => $this->snapshotHtml($cmSnapshot, true),
        ]);
    }

    public function invoiceDocument(OrderIntake $intake): Response
    {
        abort_unless(auth()->user()?->canViewOrderOperations(), 403);

        $user = auth()->user();
        $showPrices = ! ($user->isDesigner() || $user->isProductManager());

        $snapshot = $intake->snapshot_json
            ?? $intake->approved_snapshot_json
            ?? $intake->issued_snapshot_json
            ?? $intake->invoice?->snapshot_json;

        if (! is_array($snapshot) || $snapshot === []) {
            abort(404, 'Invoice document not available');
        }

        return response($this->documents->renderHtml($snapshot, false, $showPrices), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    public function startReview(Request $request, OrderIntake $intake): RedirectResponse
    {
        abort_unless(auth()->user()?->canReviewOrderIntake(), 403);

        try {
            $this->intakeService->startReview($intake, auth()->user(), $request);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Review started');
    }

    public function accept(Request $request, OrderIntake $intake): RedirectResponse
    {
        return $this->sendToCompanyManager($request, $intake);
    }

    public function sendToCompanyManager(Request $request, OrderIntake $intake): RedirectResponse
    {
        abort_unless(auth()->user()?->canReviewOrderIntake(), 403);

        $validated = $request->validate([
            'cm_due_at' => ['required', 'date', 'after:now'],
            'cm_reminder_hours_before' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $this->intakeService->sendToCompanyManager(
                $intake,
                auth()->user(),
                $request,
                Carbon::parse($validated['cm_due_at']),
                (int) ($validated['cm_reminder_hours_before'] ?? 24),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('operations.orders.show', $intake)
            ->with('status', 'Order sent to company manager with approval deadline');
    }

    public function cmApprove(Request $request, OrderIntake $intake): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole(OrderOperations::ROLE_COMPANY_MANAGER) || auth()->user()?->isAdmin(), 403);

        try {
            $this->intakeService->approveByCompanyManager($intake, auth()->user(), $request);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()
            ->route('operations.orders.show', $intake)
            ->with('status', 'Order approved — OMS can schedule production');
    }

    public function cmReject(Request $request, OrderIntake $intake): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole(OrderOperations::ROLE_COMPANY_MANAGER) || auth()->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3'],
        ]);

        try {
            $this->intakeService->rejectByCompanyManager($intake, auth()->user(), $validated['rejection_reason'], $request);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Order returned to OMS');
    }

    public function reject(Request $request, OrderIntake $intake): RedirectResponse
    {
        abort_unless(auth()->user()?->canReviewOrderIntake(), 403);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3'],
        ]);

        try {
            $this->intakeService->reject($intake, auth()->user(), $validated['rejection_reason'], $request);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Order rejected');
    }

    public function resubmit(Request $request, OrderIntake $intake): RedirectResponse
    {
        abort_unless(auth()->user()?->canViewOrderOperations(), 403);

        $validated = $request->validate([
            'resubmit_comment' => ['nullable', 'string'],
        ]);

        try {
            $this->intakeService->resubmit($intake, auth()->user(), $validated['resubmit_comment'] ?? null, $request);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Order resubmitted for OMS review');
    }

    public function updateDeadline(Request $request, OrderIntake $intake, OrderPhase $phase): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageOrderSchedule(), 403);
        abort_unless((int) $phase->order_intake_id === (int) $intake->id, 404);

        $validated = $request->validate([
            'due_at' => ['required', 'date'],
            'reminder_hours_before' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $this->scheduleService->setPhaseDeadline(
                $phase,
                auth()->user(),
                Carbon::parse($validated['due_at']),
                (int) ($validated['reminder_hours_before'] ?? 24),
                $request,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Deadline updated');
    }

    public function updatePhaseLabel(Request $request, OrderIntake $intake, OrderPhase $phase): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageOrderSchedule(), 403);
        abort_unless((int) $phase->order_intake_id === (int) $intake->id, 404);

        $validated = $request->validate([
            'label' => ['required', 'string', 'min:1', 'max:120'],
        ]);

        try {
            $this->scheduleService->updatePhaseLabel($phase, auth()->user(), $validated['label'], $request);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Phase renamed');
    }

    public function addPhase(Request $request, OrderIntake $intake): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageOrderSchedule(), 403);

        $validated = $request->validate([
            'label' => ['required', 'string', 'min:1', 'max:120'],
            'due_at' => ['nullable', 'date'],
            'reminder_hours_before' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $this->scheduleService->addCustomPhase(
                $intake,
                auth()->user(),
                $validated['label'],
                isset($validated['due_at']) ? Carbon::parse($validated['due_at']) : null,
                (int) ($validated['reminder_hours_before'] ?? 24),
                $request,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()])->withInput();
        }

        return back()->with('status', 'Phase added');
    }

    public function saveSchedule(Request $request, OrderIntake $intake): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageOrderSchedule(), 403);

        $validated = $request->validate([
            'phases' => ['nullable', 'array'],
            'phases.*.id' => ['required', 'integer'],
            'phases.*.label' => ['required', 'string', 'min:1', 'max:120'],
            'phases.*.due_at' => ['required', 'date'],
            'phases.*.reminder_hours_before' => ['nullable', 'integer', 'min:1'],
            'new_phases' => ['nullable', 'array'],
            'new_phases.*.label' => ['nullable', 'string', 'max:120'],
            'new_phases.*.due_at' => ['nullable', 'date'],
            'new_phases.*.reminder_hours_before' => ['nullable', 'integer', 'min:1'],
            'designer_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'product_manager_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        try {
            $this->scheduleService->saveSchedule(
                $intake,
                auth()->user(),
                array_values($validated['phases'] ?? []),
                array_values($validated['new_phases'] ?? []),
                isset($validated['designer_user_id']) ? (int) $validated['designer_user_id'] : null,
                isset($validated['product_manager_user_id']) ? (int) $validated['product_manager_user_id'] : null,
                $request,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()])->withInput();
        }

        return back()->with('status', 'Schedule saved');
    }

    public function completePhase(Request $request, OrderIntake $intake, OrderPhase $phase): RedirectResponse
    {
        abort_unless(auth()->user()?->canViewOrderOperations(), 403);
        abort_unless((int) $phase->order_intake_id === (int) $intake->id, 404);

        try {
            $this->workflowService->completeProductionPhase($intake, $phase, auth()->user(), $request);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Phase completed');
    }

    public function assignDesigner(Request $request, OrderIntake $intake): RedirectResponse
    {
        abort_unless(auth()->user()?->canAssignDesigner(), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'due_at' => ['required', 'date', 'after:now'],
            'reminder_hours_before' => ['nullable', 'integer', 'min:1'],
        ]);

        $assignee = User::query()->findOrFail($validated['user_id']);

        try {
            $design = $intake->phase(OrderOperations::PHASE_DESIGN)
                ?? $intake->phases()->where('phase_key', OrderOperations::PHASE_DESIGN)->first();
            if (! $design) {
                $this->scheduleService->initializePhases($intake);
                $intake->load('phases');
                $design = $intake->phase(OrderOperations::PHASE_DESIGN);
            }

            $this->scheduleService->setPhaseDeadline(
                $design,
                auth()->user(),
                Carbon::parse($validated['due_at']),
                (int) ($validated['reminder_hours_before'] ?? 24),
                $request,
            );

            $this->scheduleService->assignUser(
                $intake->fresh(['phases']),
                auth()->user(),
                $assignee,
                OrderOperations::ROLE_DESIGNER,
                $request,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()])->withInput();
        }

        return back()->with('status', 'Designer assigned with deadline');
    }

    public function assignProductManager(Request $request, OrderIntake $intake): RedirectResponse
    {
        abort_unless(auth()->user()?->canAssignProductManager(), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'due_at' => ['required', 'date', 'after:now'],
            'reminder_hours_before' => ['nullable', 'integer', 'min:1'],
        ]);

        $assignee = User::query()->findOrFail($validated['user_id']);

        try {
            $factory = $intake->phase(OrderOperations::PHASE_FACTORY_COLORING)
                ?? $intake->phases()->where('phase_key', OrderOperations::PHASE_FACTORY_COLORING)->first();
            if (! $factory) {
                $this->scheduleService->initializePhases($intake);
                $intake->load('phases');
                $factory = $intake->phase(OrderOperations::PHASE_FACTORY_COLORING);
            }

            $this->scheduleService->setPhaseDeadline(
                $factory,
                auth()->user(),
                Carbon::parse($validated['due_at']),
                (int) ($validated['reminder_hours_before'] ?? 24),
                $request,
            );

            $this->scheduleService->assignUser(
                $intake->fresh(['phases']),
                auth()->user(),
                $assignee,
                OrderOperations::ROLE_PRODUCT_MANAGER,
                $request,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()])->withInput();
        }

        return back()->with('status', 'Product manager assigned with deadline');
    }

    public function assignAssembler(Request $request, OrderIntake $intake): RedirectResponse
    {
        abort_unless(auth()->user()?->canAssignAssembler(), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $assignee = User::query()->findOrFail($validated['user_id']);

        try {
            $this->scheduleService->assignUser(
                $intake,
                auth()->user(),
                $assignee,
                OrderOperations::ROLE_ASSEMBLER,
                $request,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Assembler assigned');
    }

    public function toggleCheckpoint(Request $request, OrderIntake $intake, OrderCheckpoint $checkpoint): RedirectResponse
    {
        abort_unless(auth()->user()?->canViewOrderOperations(), 403);

        $checkpoint->loadMissing('phase');
        abort_unless((int) $checkpoint->phase?->order_intake_id === (int) $intake->id, 404);

        $request->validate([
            'completed' => ['required', 'boolean'],
        ]);

        try {
            $this->workflowService->toggleCheckpoint(
                $checkpoint,
                auth()->user(),
                $request->boolean('completed'),
                $request,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Checkpoint updated');
    }

    public function submitMaterials(Request $request, OrderIntake $intake): RedirectResponse
    {
        abort_unless(auth()->user()?->canViewOrderOperations(), 403);

        $validated = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_name' => ['required', 'string', 'min:1'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit' => ['nullable', 'string'],
            'lines.*.item_id' => ['nullable', 'integer'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        try {
            $this->procurementService->submitMaterials($intake, auth()->user(), $validated['lines'], $request);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Materials submitted');
    }

    public function verifyStock(Request $request, OrderIntake $intake, OrderMaterialLine $line): RedirectResponse
    {
        abort_unless(auth()->user()?->canApproveOrderProcurement() || auth()->user()?->hasRole('company_manager'), 403);
        abort_unless((int) $line->order_intake_id === (int) $intake->id, 404);

        try {
            $this->procurementService->verifyStockAvailable($line, auth()->user(), $request);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Stock marked available');
    }

    public function procureMaterial(Request $request, OrderIntake $intake, OrderMaterialLine $line): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('company_manager'), 403);
        abort_unless((int) $line->order_intake_id === (int) $intake->id, 404);

        try {
            $this->procurementService->markUnavailableAndProcure($line, auth()->user(), $request);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Material routed to procurement');
    }

    public function addQuote(Request $request, OrderIntake $intake, OrderProcurementRequest $procurement): RedirectResponse
    {
        abort_unless(auth()->user()?->isProcurement() || auth()->user()?->isAdmin(), 403);
        abort_unless((int) $procurement->order_intake_id === (int) $intake->id, 404);

        $validated = $request->validate([
            'supplier_name' => ['required', 'string', 'min:2'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'quality_grade' => ['nullable', 'string'],
            'availability' => ['nullable', 'string'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'delivery_terms' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $this->procurementService->addQuote($procurement, auth()->user(), $validated, $request);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Supplier quote added');
    }

    public function recommendQuote(Request $request, OrderIntake $intake, OrderProcurementRequest $procurement, OrderSupplierQuote $quote): RedirectResponse
    {
        abort_unless(auth()->user()?->isProcurement() || auth()->user()?->isAdmin(), 403);
        abort_unless((int) $procurement->order_intake_id === (int) $intake->id, 404);

        $validated = $request->validate([
            'justification' => ['required', 'string', 'min:3'],
        ]);

        try {
            $this->procurementService->recommendQuote(
                $procurement,
                $quote,
                auth()->user(),
                $validated['justification'],
                $request,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Quote recommended for approval');
    }

    public function decideProcurement(Request $request, OrderIntake $intake, OrderProcurementRequest $procurement): RedirectResponse
    {
        abort_unless(auth()->user()?->canApproveOrderProcurement(), 403);
        abort_unless((int) $procurement->order_intake_id === (int) $intake->id, 404);

        $validated = $request->validate([
            'approve' => ['required', 'boolean'],
            'comment' => ['nullable', 'string'],
        ]);

        try {
            $this->procurementService->decideRecommendation(
                $procurement,
                auth()->user(),
                $request->boolean('approve'),
                $validated['comment'] ?? null,
                $request,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', $request->boolean('approve') ? 'Procurement approved' : 'Procurement rejected');
    }

    public function releaseMaterials(Request $request, OrderIntake $intake): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('company_manager') || auth()->user()?->isOms(), 403);

        try {
            $this->procurementService->releaseMaterials($intake, auth()->user(), $request);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Materials released to production');
    }

    public function completeAssembly(Request $request, OrderIntake $intake): RedirectResponse
    {
        abort_unless(auth()->user()?->canMonitorProductionDelivery(), 403);

        try {
            $this->workflowService->completeAssembly($intake, auth()->user(), $request);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Assembly completed');
    }

    public function completeDelivery(Request $request, OrderIntake $intake): RedirectResponse
    {
        abort_unless(auth()->user()?->canMonitorProductionDelivery(), 403);

        try {
            $this->workflowService->completeDelivery($intake, auth()->user(), $request);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Delivery completed');
    }

    public function scheduleDelivery(Request $request, OrderIntake $intake): RedirectResponse
    {
        abort_unless(auth()->user()?->canMonitorProductionDelivery() || auth()->user()?->canCreateDeliveries(), 403);

        $validated = $request->validate([
            'product_name' => ['required', 'string', 'min:2'],
            'quantity' => ['required', 'integer', 'min:1'],
            'destination' => ['required', 'in:customer,showroom'],
            'recipient_name' => ['required', 'string', 'min:2'],
            'notes' => ['nullable', 'string'],
        ]);

        Delivery::query()->create([
            ...$validated,
            'order_intake_id' => $intake->id,
            'status' => 'planned',
            'created_by' => auth()->id(),
        ]);

        return back()->with('status', 'Delivery scheduled for this order');
    }

    public function sendMessage(Request $request, OrderIntake $intake): RedirectResponse
    {
        abort_unless(auth()->user()?->canMessageOpsPeer(), 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1'],
        ]);

        try {
            $this->messageService->send($intake, auth()->user(), $validated['body'], $request);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Message sent');
    }

    /**
     * @return Collection<int, User>
     */
    private function usersWithRole(string $role): Collection
    {
        $names = OrderOperations::roleAliases($role);

        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', $names))
            ->orderBy('full_name')
            ->get();
    }

    private function snapshotHtml(mixed $snapshot, bool $showPrices): ?string
    {
        if (is_string($snapshot)) {
            $decoded = json_decode($snapshot, true);
            $snapshot = is_array($decoded) ? $decoded : null;
        }

        if (! is_array($snapshot) || $snapshot === []) {
            return null;
        }

        try {
            return $this->documents->renderHtml($snapshot, false, $showPrices);
        } catch (\Throwable) {
            return null;
        }
    }
}
