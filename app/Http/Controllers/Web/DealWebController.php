<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\DealController as ApiDealController;
use App\Http\Controllers\Controller;
use App\Models\Deal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DealWebController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->canViewDeals(), 403);

        $query = Deal::query()->with('owner')->orderByDesc('created_at');

        if ($request->query('mine') === '1') {
            $query->where('owner_id', auth()->id());
        }

        $user = auth()->user();

        return view('deals.index', [
            'deals' => $query->get(),
            'canCreate' => $user->canCreateDeals(),
            'canSalesReview' => $user->canSalesReviewDeal(),
            'canManagerReview' => $user->canManagerReviewDeal(),
            'mine' => $request->query('mine') === '1',
        ]);
    }

    public function store(Request $request, ApiDealController $api): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateDeals(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:2'],
            'customer_name' => ['required', 'string', 'min:2'],
            'product_category' => ['required', 'string', 'min:1'],
            'deal_value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'lead_id' => ['nullable', 'integer'],
        ]);

        $response = $api->store($request->merge($validated));
        $payload = $response->getData(true);

        if (! ($payload['success'] ?? false)) {
            return back()->withErrors(['title' => $payload['error']['message'] ?? 'Failed']);
        }

        return back()->with('status', 'Deal created');
    }

    public function update(Request $request, int $id, ApiDealController $api): RedirectResponse
    {
        abort_unless(auth()->user()?->canViewDeals(), 403);

        $response = $api->update($request, $id);
        $payload = $response->getData(true);

        if (! ($payload['success'] ?? false)) {
            return back()->withErrors(['status' => $payload['error']['message'] ?? 'Failed to update deal status']);
        }

        return back()->with('status', 'Deal status updated');
    }

    public function salesReview(Request $request, int $id, ApiDealController $api): RedirectResponse
    {
        abort_unless(auth()->user()?->canSalesReviewDeal(), 403);

        $response = $api->salesReview($request, $id);
        $payload = $response->getData(true);

        if (! ($payload['success'] ?? false)) {
            return back()->withErrors(['review' => $payload['error']['message'] ?? 'Sales review failed']);
        }

        $action = $request->input('action');

        return back()->with(
            'status',
            $action === 'approve' ? 'Deal forwarded to Company Manager' : 'Deal marked lost',
        );
    }

    public function managerReview(Request $request, int $id, ApiDealController $api): RedirectResponse
    {
        abort_unless(auth()->user()?->canManagerReviewDeal(), 403);

        $response = $api->managerReview($request, $id);
        $payload = $response->getData(true);

        if (! ($payload['success'] ?? false)) {
            return back()->withErrors(['review' => $payload['error']['message'] ?? 'Manager review failed']);
        }

        $action = $request->input('action');

        return back()->with(
            'status',
            $action === 'approve' ? 'Deal approved' : 'Deal returned to sales',
        );
    }
}
