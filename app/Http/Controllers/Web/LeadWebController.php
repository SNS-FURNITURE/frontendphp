<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\DealController as ApiDealController;
use App\Http\Controllers\Api\LeadController as ApiLeadController;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadWebController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->canViewLeads(), 403);

        $user = auth()->user();
        $query = Lead::query()->orderByDesc('created_at');

        if (! $user->canVerifyLeads()) {
            $query->where('created_by', $user->id);
        }

        if ($request->query('source') === 'website') {
            $query->whereIn('source', ['quote_form', 'contact_form', 'website']);
        } elseif ($request->query('source') === 'manual') {
            $query->where(function ($q) {
                $q->whereNull('source')->orWhereNotIn('source', ['quote_form', 'contact_form', 'website']);
            });
        }

        return view('leads.index', [
            'leads' => $query->get(),
            'canCreate' => $user->canCreateLeads(),
            'canVerify' => $user->canVerifyLeads(),
            'canConvert' => $user->canCreateDeals(),
            'sourceFilter' => $request->query('source', 'all'),
        ]);
    }

    public function store(Request $request, ApiLeadController $api): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateLeads(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2'],
            'phone' => ['required', 'string', 'min:8'],
            'email' => ['nullable', 'email'],
            'source' => ['required', 'string', 'min:1'],
            'product_interest' => ['required', 'string', 'min:1'],
            'design_source' => ['required', 'string', 'min:1'],
            'room_details' => ['nullable', 'string'],
            'material_preference' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ], [
            'product_interest.min' => 'Please select product category',
        ]);

        $response = $api->store($request->merge($validated));
        $payload = $response->getData(true);

        if (! ($payload['success'] ?? false)) {
            return back()->withErrors(['name' => $payload['error']['message'] ?? 'Failed']);
        }

        return back()->with('status', 'Lead for "'.$validated['name'].'" added successfully');
    }

    public function update(Request $request, int $id, ApiLeadController $api): RedirectResponse
    {
        abort_unless(auth()->user()?->canViewLeads(), 403);

        $lead = Lead::query()->findOrFail($id);
        $user = auth()->user();

        if (! $user->canVerifyLeads() && (int) $lead->created_by !== (int) $user->id) {
            abort(403);
        }

        $response = $api->update($request, $id);
        $payload = $response->getData(true);

        if (! ($payload['success'] ?? false)) {
            return back()->withErrors(['status' => $payload['error']['message'] ?? 'Could not update status']);
        }

        return back()->with('status', 'Lead updated');
    }

    public function convert(Request $request, int $id, ApiDealController $deals): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateDeals(), 403);

        $lead = Lead::query()->findOrFail($id);
        if ($lead->status !== 'verified') {
            return back()->withErrors(['convert' => 'Lead must be verified before converting to a deal']);
        }

        $request->merge([
            'title' => $lead->name.' deal',
            'customer_name' => $lead->name,
            'lead_id' => $lead->id,
            'product_category' => $lead->product_interest,
            'notes' => $lead->notes,
        ]);

        $response = $deals->store($request);
        $payload = $response->getData(true);

        if (! ($payload['success'] ?? false)) {
            return back()->withErrors(['convert' => $payload['error']['message'] ?? 'Failed to convert lead to deal']);
        }

        return redirect()
            ->route('deals.index')
            ->with('status', 'Deal created from lead');
    }
}
