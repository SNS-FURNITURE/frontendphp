<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ExternalLaborer;
use App\Models\ProcurementMarketResearch;
use App\Models\SiteInstallationJob;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ProcurementWebController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->canViewProcurement(), 403);

        return view('procurement.index', [
            'research' => ProcurementMarketResearch::query()->with(['conductedBy', 'approvedBy'])->orderByDesc('created_at')->get(),
            'laborers' => ExternalLaborer::query()->orderBy('full_name')->get(),
            'installations' => SiteInstallationJob::query()->with('laborer')->orderByDesc('created_at')->get(),
            'researchStatuses' => ProcurementMarketResearch::STATUSES,
            'laborerStatuses' => ExternalLaborer::STATUSES,
            'installationStatuses' => SiteInstallationJob::STATUSES,
            'canCreate' => auth()->user()->canCreateProcurement(),
            'canApprove' => auth()->user()->canApproveProcurement(),
            'canEditLaborer' => auth()->user()->canEditProcurement(),
            'canViewInstallation' => auth()->user()->canViewInstallation(),
            'canEditInstallation' => auth()->user()->canEditInstallation(),
        ]);
    }

    public function storeResearch(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateProcurement(), 403);

        $validated = $request->validate([
            'item_name' => ['required', 'string', 'min:2'],
            'category' => ['required', 'string', 'min:1'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_of_measure' => ['required', 'string', 'min:1'],
            'selected_supplier_name' => ['required', 'string', 'min:2'],
            'selected_unit_price' => ['required', 'numeric', 'min:0.01'],
            'specifications' => ['nullable', 'string'],
            'quality_grade' => ['nullable', 'string'],
            'research_notes' => ['nullable', 'string'],
        ], [
            'item_name.required' => 'Please provide an item name and quantity',
            'quantity.required' => 'Please provide an item name and quantity',
        ]);

        $total = (float) $validated['selected_unit_price'] * (float) $validated['quantity'];

        $row = ProcurementMarketResearch::query()->create([
            ...$validated,
            'selected_total_price' => $total,
            'status' => 'submitted',
            'conducted_by_user_id' => auth()->id() ?: 1,
        ]);

        $this->audit->log(auth()->user(), 'procurement_market_research', (int) $row->id, 'CREATE_MARKET_RESEARCH', [
            'item_name' => $validated['item_name'],
        ], $request);

        return redirect()
            ->route('procurement.index')
            ->with('status', 'Market research for "'.$validated['item_name'].'" submitted for Manager approval');
    }

    public function updateResearch(Request $request, int $id): RedirectResponse
    {
        $row = ProcurementMarketResearch::query()->findOrFail($id);
        $status = (string) $request->input('status');

        if (! in_array($status, ProcurementMarketResearch::STATUSES, true)) {
            return back()->withErrors(['status' => 'Invalid status']);
        }

        if (in_array($status, ['manager_approved', 'manager_rejected'], true) && ! auth()->user()?->canApproveProcurement()) {
            return back()->withErrors(['status' => 'Company Manager approval required']);
        }

        $updates = ['status' => $status];
        if ($status === 'manager_approved') {
            $updates['approved_by_user_id'] = auth()->id();
        }
        $row->fill($updates)->save();

        $this->audit->log(auth()->user(), 'procurement_market_research', $id, 'UPDATE_MARKET_RESEARCH', $updates, $request);

        return back()->with('status', 'Market research updated');
    }

    public function storeLaborer(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canViewInstallation() || auth()->user()?->canCreateProcurement(), 403);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'min:2'],
            'phone' => ['required', 'string', 'min:6'],
            'specialty_skills' => ['required', 'string', 'min:2'],
            'experience_years' => ['nullable', 'integer', 'min:0'],
            'daily_rate' => ['nullable', 'numeric', 'min:0'],
            'national_id' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ], [
            'full_name.required' => 'Please enter laborer name and phone number',
            'phone.required' => 'Please enter laborer name and phone number',
        ]);

        $laborer = ExternalLaborer::query()->create([
            ...$validated,
            'experience_years' => $validated['experience_years'] ?? 0,
            'daily_rate' => $validated['daily_rate'] ?? 0,
            'status' => 'free',
            'created_by_user_id' => auth()->id(),
        ]);

        $this->audit->log(auth()->user(), 'external_laborers', (int) $laborer->id, 'CREATE_LABORER', [
            'full_name' => $validated['full_name'],
        ], $request);

        return redirect()->route('procurement.index')->with('status', 'Laborer registered successfully');
    }

    public function updateLaborer(Request $request, int $id): RedirectResponse
    {
        if (! auth()->user()?->canEditProcurement()) {
            return back()->withErrors(['status' => 'Permission required to edit laborer status']);
        }

        $laborer = ExternalLaborer::query()->findOrFail($id);
        $validated = $request->validate([
            'status' => ['required', 'in:free,assigned,unavailable'],
        ]);
        $laborer->fill($validated)->save();

        $this->audit->log(auth()->user(), 'external_laborers', $id, 'UPDATE_LABORER', $validated, $request);

        return back()->with('status', 'Laborer status updated');
    }

    public function storeInstallation(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canViewInstallation(), 403);

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'min:2'],
            'site_address' => ['required', 'string', 'min:3'],
            'customer_phone' => ['nullable', 'string'],
            'assigned_laborer_id' => ['nullable', 'integer'],
            'scheduled_start_date' => ['nullable', 'date'],
            'estimated_duration_days' => ['nullable', 'integer', 'min:1'],
            'agreed_total_payment' => ['nullable', 'numeric', 'min:0'],
            'advance_payment_amount' => ['nullable', 'numeric', 'min:0'],
            'setup_notes' => ['nullable', 'string'],
        ], [
            'customer_name.required' => 'Please enter customer name and site address',
            'site_address.required' => 'Please enter customer name and site address',
        ]);

        $laborerId = $validated['assigned_laborer_id'] ?? null;
        $total = (float) ($validated['agreed_total_payment'] ?? 0);
        $advance = (float) ($validated['advance_payment_amount'] ?? 0);

        $job = SiteInstallationJob::query()->create([
            'customer_name' => $validated['customer_name'],
            'site_address' => $validated['site_address'],
            'customer_phone' => $validated['customer_phone'] ?? null,
            'assigned_laborer_id' => $laborerId,
            'status' => $laborerId ? 'assigned' : 'not_assigned',
            'scheduled_start_date' => $validated['scheduled_start_date'] ?? null,
            'estimated_duration_days' => $validated['estimated_duration_days'] ?? 1,
            'agreed_total_payment' => $total,
            'advance_payment_amount' => $advance,
            'balance_payment_amount' => max(0, $total - $advance),
            'setup_notes' => $validated['setup_notes'] ?? null,
        ]);

        if ($laborerId) {
            ExternalLaborer::query()->where('id', $laborerId)->update(['status' => 'assigned']);
        }

        $this->audit->log(auth()->user(), 'site_installation_jobs', (int) $job->id, 'CREATE_INSTALLATION_JOB', [
            'customer_name' => $validated['customer_name'],
        ], $request);

        return redirect()->route('procurement.index')->with('status', 'Site installation job created');
    }

    public function updateInstallation(Request $request, int $id): RedirectResponse
    {
        if (! auth()->user()?->canEditInstallation()) {
            return back()->withErrors(['status' => 'Permission required to update installation job']);
        }

        $job = SiteInstallationJob::query()->findOrFail($id);
        $validated = $request->validate([
            'status' => ['required', 'in:not_assigned,assigned,in_progress,finished,cancelled'],
            'assigned_laborer_id' => ['nullable', 'integer'],
            'agreed_total_payment' => ['nullable', 'numeric', 'min:0'],
            'advance_payment_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $updates = ['status' => $validated['status']];
        if (array_key_exists('assigned_laborer_id', $validated)) {
            $updates['assigned_laborer_id'] = $validated['assigned_laborer_id'];
        }
        if (isset($validated['agreed_total_payment']) || isset($validated['advance_payment_amount'])) {
            $total = (float) ($validated['agreed_total_payment'] ?? $job->agreed_total_payment);
            $advance = (float) ($validated['advance_payment_amount'] ?? $job->advance_payment_amount);
            $updates['agreed_total_payment'] = $total;
            $updates['advance_payment_amount'] = $advance;
            $updates['balance_payment_amount'] = max(0, $total - $advance);
        }
        if ($validated['status'] === 'finished' && ! $job->actual_completion_date) {
            $updates['actual_completion_date'] = Carbon::today()->toDateString();
        }

        $oldLaborer = $job->assigned_laborer_id;
        $job->fill($updates)->save();

        $currentLaborer = $updates['assigned_laborer_id'] ?? $oldLaborer;
        if ($currentLaborer) {
            if (in_array($validated['status'], ['finished', 'cancelled'], true)) {
                $active = SiteInstallationJob::query()
                    ->where('assigned_laborer_id', $currentLaborer)
                    ->where('id', '!=', $id)
                    ->whereIn('status', ['assigned', 'in_progress'])
                    ->count();
                if ($active === 0) {
                    ExternalLaborer::query()->where('id', $currentLaborer)->update(['status' => 'free']);
                }
            } elseif (in_array($validated['status'], ['assigned', 'in_progress'], true)) {
                ExternalLaborer::query()->where('id', $currentLaborer)->update(['status' => 'assigned']);
            }
        }

        $this->audit->log(auth()->user(), 'site_installation_jobs', $id, 'UPDATE_INSTALLATION_JOB', $updates, $request);

        return back()->with('status', 'Installation job updated');
    }
}
