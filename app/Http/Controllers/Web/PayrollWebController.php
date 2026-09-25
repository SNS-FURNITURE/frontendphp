<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\PayrollRunController as ApiPayrollRunController;
use App\Http\Controllers\Controller;
use App\Models\PayrollRun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollWebController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->canViewPayroll(), 403);

        return view('finance.payroll.index', [
            'runs' => PayrollRun::query()->latestFirst('period')->get(),
            'canGenerate' => auth()->user()->canEditPayroll(),
        ]);
    }

    public function show(int $id): View
    {
        abort_unless(auth()->user()?->canViewPayroll(), 403);

        $run = PayrollRun::query()->with('lines')->findOrFail($id);

        return view('finance.payroll.show', [
            'run' => $run,
            'canEdit' => auth()->user()->canEditPayroll() && $run->status === 'draft',
        ]);
    }

    public function generate(Request $request, ApiPayrollRunController $api): RedirectResponse
    {
        abort_unless(auth()->user()?->canEditPayroll(), 403);

        $response = $api->store($request);
        $payload = $response->getData(true);

        if (! ($payload['success'] ?? false)) {
            return back()->withErrors(['period' => $payload['error']['message'] ?? 'Generate failed']);
        }

        $period = $payload['data']['period'] ?? $request->input('period');

        return redirect()
            ->route('finance.payroll.show', $payload['data']['id'])
            ->with('status', 'Payroll processed for '.$period);
    }

    public function updateStatus(Request $request, int $id, ApiPayrollRunController $api): RedirectResponse
    {
        abort_unless(auth()->user()?->canEditPayroll(), 403);

        $response = $api->updateStatus($request, $id);
        $payload = $response->getData(true);

        if (! ($payload['success'] ?? false)) {
            return back()->withErrors(['status' => $payload['error']['message'] ?? 'Status update failed']);
        }

        $status = $request->input('status');

        return back()->with(
            'status',
            $status === 'paid' ? 'Payroll marked paid' : 'Payroll processed',
        );
    }

    public function updateLine(Request $request, int $id, int $lineId, ApiPayrollRunController $api): RedirectResponse
    {
        abort_unless(auth()->user()?->canEditPayroll(), 403);

        $response = $api->updateLine($request, $id, $lineId);
        $payload = $response->getData(true);

        if (! ($payload['success'] ?? false)) {
            return back()->withErrors(['line' => $payload['error']['message'] ?? 'Update failed']);
        }

        return back()->with('status', 'Salary line updated');
    }

    public function csv(int $id, ApiPayrollRunController $api): StreamedResponse|RedirectResponse
    {
        abort_unless(auth()->user()?->canViewPayroll(), 403);

        $result = $api->csv($id);
        if ($result instanceof StreamedResponse) {
            return $result;
        }

        return back()->withErrors(['csv' => 'Payroll run not found']);
    }
}
