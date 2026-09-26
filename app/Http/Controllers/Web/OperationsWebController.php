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
use App\Services\OrderIntakeService;
use App\Services\OrderMessageService;
use App\Services\OrderProcurementService;
use App\Services\OrderScheduleService;
use App\Services\OrderWorkflowService;
use App\Support\OrderOperations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    ) {}

    public function omsDashboard(): View
    {
        abort_unless(auth()->user()?->canReviewOrderIntake() || auth()->user()?->canViewOrderOperations(), 403);

        $intakes = OrderIntake::query()
            ->with(['invoice', 'sourceUser', 'reviewer'])
            ->whereIn('status', OrderOperations::intakeOpenStatuses())
            ->latest('id')
            ->get();

        return view('operations.oms-dashboard', [
            'intakes' => $intakes,
        ]);
    }

    public function omfDashboard(): View
    {
        abort_unless(auth()->user()?->isOmf() || auth()->user()?->isAssembler() || auth()->user()?->canMonitorProductionDelivery() || auth()->user()?->canViewOrderOperations(), 403);

        $intakes = OrderIntake::query()
            ->with(['phases', 'assignments.user', 'deliveries'])
            ->where('status', OrderOperations::INTAKE_ACCEPTED)
            ->whereHas('phases', function ($q) {
                $q->whereIn('phase_key', [OrderOperations::PHASE_ASSEMBLY, OrderOperations::PHASE_DELIVERY]);
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

        if (auth()->user()->canMessageOpsPeer()) {
            $this->messageService->markRead($intake, auth()->user());
        }

        return view('operations.show', [
            'intake' => $intake,
            'designers' => $this->usersWithRole(OrderOperations::ROLE_DESIGNER),
            'assemblers' => $this->usersWithRole(OrderOperations::ROLE_ASSEMBLER),
            'destinations' => Delivery::DESTINATIONS,
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
        abort_unless(auth()->user()?->canReviewOrderIntake(), 403);

        try {
            $this->intakeService->accept($intake, auth()->user(), $request);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()
            ->route('operations.orders.show', $intake)
            ->with('status', 'Order accepted and phases initialized');
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

    public function assignDesigner(Request $request, OrderIntake $intake): RedirectResponse
    {
        abort_unless(auth()->user()?->canAssignDesigner(), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $assignee = User::query()->findOrFail($validated['user_id']);

        try {
            $this->scheduleService->assignUser(
                $intake,
                auth()->user(),
                $assignee,
                OrderOperations::ROLE_DESIGNER,
                $request,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Designer assigned');
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
}
