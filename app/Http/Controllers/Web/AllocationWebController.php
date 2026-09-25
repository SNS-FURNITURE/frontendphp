<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FundingRequest;
use Illuminate\View\View;

class AllocationWebController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->canViewAllocations(), 403);

        $requests = FundingRequest::query()
            ->with(['requester', 'approver'])
            ->whereIn('status', FundingRequest::ALLOCATION_STATUSES)
            ->latestFirst()
            ->get();

        return view('finance.allocations', [
            'requests' => $requests,
        ]);
    }
}
