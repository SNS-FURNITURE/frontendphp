<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FundingRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FundingWebController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->canViewFunding(), 403);

        $requests = FundingRequest::query()
            ->with(['requester', 'approver'])
            ->orderByDesc('created_at')
            ->get();

        $user = auth()->user();

        return view('funding.index', [
            'requests' => $requests,
            'statuses' => FundingRequest::STATUSES,
            'canCreate' => $user->canCreateFunding(),
            'canApprove' => $user->canApproveFunding(),
            'canEditFunding' => $user->canEditFunding(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateFunding(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:2'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'category' => ['required', 'string', 'min:1'],
            'purpose' => ['required', 'string', 'min:3'],
            'deadline' => ['nullable', 'string'],
        ], [
            'title.min' => 'Request title is required',
            'amount.min' => 'Amount must be greater than 0',
            'purpose.min' => 'Detailed justification is required',
        ]);

        $purpose = '['.$validated['category'].'] '.$validated['purpose'];
        if (! empty($validated['deadline'])) {
            $purpose .= ' (Due: '.$validated['deadline'].')';
        }

        $funding = FundingRequest::query()->create([
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'purpose' => $purpose,
            'status' => 'draft',
            'requested_by' => auth()->id(),
        ]);

        $this->audit->log(
            auth()->user(),
            'funding_request',
            (int) $funding->id,
            'CREATE_FUNDING_REQUEST',
            ['title' => $validated['title'], 'amount' => $validated['amount']],
            $request,
        );

        return redirect()
            ->route('funding.index')
            ->with('status', 'Funding request for '.$validated['amount'].' ETB submitted for Admin review');
    }

    public function updateStatus(Request $request, FundingRequest $funding): RedirectResponse
    {
        $status = (string) $request->input('status');
        $user = auth()->user();

        if (! in_array($status, FundingRequest::STATUSES, true)) {
            return back()->withErrors(['status' => 'Invalid funding status']);
        }

        if (in_array($status, ['approved', 'rejected'], true) && ! $user?->canApproveFunding()) {
            return back()->withErrors(['status' => 'Finance approval required']);
        }

        if (in_array($status, ['received', 'routed'], true) && ! $user?->canEditFunding()) {
            return back()->withErrors(['status' => 'Permission required']);
        }

        if (! $user?->canApproveFunding() && ! $user?->canEditFunding() && ! $user?->canCreateFunding()) {
            abort(403);
        }

        $updates = ['status' => $status];
        if (in_array($status, ['approved', 'routed', 'received'], true) && ! $funding->approved_by) {
            $updates['approved_by'] = $user->id;
        }

        $funding->fill($updates);
        $funding->save();

        $this->audit->log(
            $user,
            'funding_request',
            (int) $funding->id,
            'UPDATE_FUNDING_REQUEST',
            ['status' => $status],
            $request,
        );

        return back()->with('status', 'Funding request status updated');
    }
}
