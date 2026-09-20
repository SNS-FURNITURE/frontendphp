<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CustomerWebController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->canViewSales(), 403);

        $approval = $request->query('approval_status');

        $parties = Party::query()
            ->where('party_type', 'customer')
            ->when($approval, fn ($q) => $q->where('approval_status', $approval))
            ->orderByDesc('created_at')
            ->get();

        $leadByPhone = [];
        $dealCounts = [];

        if (Schema::hasTable('leads')) {
            $leads = DB::table('leads')->select('id', 'name', 'phone', 'email', 'status', 'created_at')->get();
            foreach ($leads as $lead) {
                $key = $this->normalizePhone((string) ($lead->phone ?? ''));
                if ($key !== '') {
                    $leadByPhone[$key] = $lead;
                }
            }
        }

        if (Schema::hasTable('deals')) {
            $dealCounts = DB::table('deals')
                ->select('customer_name', DB::raw('COUNT(*) as deal_count'))
                ->groupBy('customer_name')
                ->pluck('deal_count', 'customer_name')
                ->all();
        }

        $customers = $parties->map(function (Party $party) use ($leadByPhone, $dealCounts) {
            $phoneKey = $this->normalizePhone((string) ($party->phone ?? ''));
            $lead = $phoneKey !== '' ? ($leadByPhone[$phoneKey] ?? null) : null;
            $dealCount = (int) ($dealCounts[$party->name] ?? 0);

            return [
                'party' => $party,
                'lead' => $lead,
                'deal_count' => $dealCount,
            ];
        });

        $orphanLeads = [];
        if (Schema::hasTable('leads')) {
            $partyPhones = $parties->map(fn (Party $p) => $this->normalizePhone((string) ($p->phone ?? '')))
                ->filter()
                ->all();
            foreach ($leadByPhone as $phone => $lead) {
                if (! in_array($phone, $partyPhones, true)) {
                    $orphanLeads[] = $lead;
                }
            }
        }

        return view('sales.customers.index', [
            'customers' => $customers,
            'orphanLeads' => $orphanLeads,
            'approval' => $approval,
            'canCreate' => auth()->user()->canCreateSales(),
            'canApprove' => auth()->user()->canApproveParty(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateSales(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2'],
            'phone' => ['required', 'string', 'min:8'],
            'email' => ['nullable', 'email'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ], [
            'name.required' => 'Customer / Contact name is required',
            'name.min' => 'Customer / Contact name is required',
            'phone.required' => 'Phone number is required',
            'phone.min' => 'Phone number is required',
            'email.email' => 'Invalid email',
        ]);

        $party = Party::query()->create([
            'party_type' => 'customer',
            'name' => $validated['name'],
            'company_name' => $validated['company_name'] ?? null,
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'approval_status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        $this->audit->log(auth()->user(), 'party', (int) $party->id, 'CREATE_PARTY', [
            'name' => $party->name,
            'party_type' => 'customer',
        ], $request);

        return redirect()
            ->route('sales.customers')
            ->with('status', 'Customer created — pending advisor approval.');
    }

    public function approve(Request $request, Party $party): RedirectResponse
    {
        abort_unless(auth()->user()?->canApproveParty(), 403);

        $validated = $request->validate([
            'approval_status' => ['required', 'in:approved,rejected'],
        ], [
            'approval_status.in' => 'Invalid status. Must be approved or rejected.',
        ]);

        $previous = $party->approval_status;
        $party->approval_status = $validated['approval_status'];
        $party->approved_by = auth()->id();
        $party->save();

        $this->audit->log(
            auth()->user(),
            'party',
            (int) $party->id,
            'APPROVAL_'.strtoupper($validated['approval_status']),
            [
                'previous_status' => $previous,
                'new_status' => $validated['approval_status'],
            ],
            $request,
        );

        $label = $validated['approval_status'] === 'approved' ? 'approved' : 'rejected';

        return redirect()
            ->route('sales.customers')
            ->with('status', "Customer {$label}.");
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}
