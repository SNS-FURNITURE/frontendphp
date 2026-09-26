<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Services\AuditService;
use App\Services\CommercialReportService;
use App\Services\CustomerIdentityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerWebController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private CommercialReportService $commercialReports,
        private CustomerIdentityService $customerIdentity,
    ) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->canViewSales() || auth()->user()?->canCreateCustomerContact(), 403);

        $user = auth()->user();
        $query = Party::query()->where('party_type', 'customer');

        if ($user->isSalesRep()) {
            $query->where('created_by', $user->id);
        } elseif ($user->canReviewCustomerContacts()) {
            if ($request->query('filter') === 'pending') {
                $query->where('approval_status', 'pending');
            }
        }

        $parties = $query->with(['creator', 'approver'])->latestFirst()->get();

        return view('sales.customers.index', [
            'customers' => $parties,
            'canCreate' => $user->canCreateCustomerContact() || $user->canReviewCustomerContacts(),
            'canReview' => $user->canReviewCustomerContacts(),
            'filter' => $request->query('filter'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user?->canCreateCustomerContact() || $user?->canReviewCustomerContacts(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2'],
            'phone' => ['required', 'string', 'min:8'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ], [
            'name.required' => 'Customer / Contact name is required',
            'phone.required' => 'Phone number is required',
        ]);

        if ($this->customerIdentity->customerExists($validated['name'], $validated['address'] ?? null)) {
            return back()
                ->withErrors(['name' => 'A customer with this name and address already exists.'])
                ->withInput();
        }

        try {
            $isReviewer = auth()->user()?->canReviewCustomerContacts();
            $status = $isReviewer ? 'approved' : 'pending';

            $party = $this->customerIdentity->createCustomer([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'address' => $validated['address'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'approval_status' => $status,
                'created_by' => auth()->id(),
                'approved_by' => $isReviewer ? auth()->id() : null,
            ]);
        } catch (\InvalidArgumentException $exception) {
            return back()
                ->withErrors(['name' => $exception->getMessage()])
                ->withInput();
        }

        $this->audit->log(auth()->user(), 'party', (int) $party->id, 'CREATE_PARTY', [
            'name' => $party->name,
            'party_type' => 'customer',
            'approval_status' => $status,
        ], $request);

        if (!$isReviewer) {
            $this->commercialReports->reportContactSubmitted($party);
        }

        $msg = $isReviewer ? 'Customer added and approved automatically.' : 'Contact submitted for supervisor review.';

        return redirect()
            ->route('sales.dashboard')
            ->with('status', $msg);
    }

    public function approve(Request $request, Party $party): RedirectResponse
    {
        abort_unless(auth()->user()?->canReviewCustomerContacts(), 403);
        abort_unless($party->party_type === 'customer', 404);

        $validated = $request->validate([
            'approval_status' => ['required', 'in:approved,rejected'],
        ]);

        if ($party->approval_status !== 'pending') {
            return back()->withErrors(['approval_status' => 'This contact was already reviewed.']);
        }

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

        $this->commercialReports->reportContactReviewed($party);

        $label = $validated['approval_status'] === 'approved' ? 'approved' : 'rejected';

        return redirect()
            ->route('sales.customers')
            ->with('status', "Contact {$label}.");
    }
}
