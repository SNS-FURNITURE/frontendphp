<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\LeaveController as ApiLeaveController;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveWebController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->canViewHr(), 403);

        return view('hr.leave.index', [
            'leaves' => LeaveRequest::query()->with('approver')->orderByDesc('created_at')->get(),
            'employees' => Employee::query()->with('party')->active()->orderBy('id')->get(),
            'canCreate' => auth()->user()->canEditHr(),
            'canApprove' => auth()->user()->canApproveHr(),
        ]);
    }

    public function store(Request $request, ApiLeaveController $api): RedirectResponse
    {
        abort_unless(auth()->user()?->canEditHr(), 403);

        $validated = $request->validate([
            'employee_name' => ['required', 'string', 'min:2'],
            'employee_id' => ['nullable', 'integer'],
            'leave_type' => ['required', 'string', 'min:1'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:3'],
        ]);

        $request->merge([
            'reason' => '['.$validated['leave_type'].'] '.$validated['reason'],
        ]);

        $response = $api->store($request);
        $payload = $response->getData(true);

        if (! ($payload['success'] ?? false)) {
            return back()->withErrors(['employee_name' => $payload['error']['message'] ?? 'Failed']);
        }

        return back()->with('status', 'Leave request for '.$validated['employee_name'].' submitted');
    }

    public function update(Request $request, int $id, ApiLeaveController $api): RedirectResponse
    {
        abort_unless(auth()->user()?->canApproveHr(), 403);

        $response = $api->update($request, $id);
        $payload = $response->getData(true);

        if (! ($payload['success'] ?? false)) {
            return back()->withErrors(['status' => $payload['error']['message'] ?? 'Failed to update status']);
        }

        return back()->with('status', 'Leave request status updated');
    }
}
