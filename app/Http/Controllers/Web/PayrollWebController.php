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
            'canFinalize' => auth()->user()->canFinalizePayroll(),
        ]);
    }

    public function show(int $id): View
    {
        abort_unless(auth()->user()?->canViewPayroll(), 403);

        $run = PayrollRun::query()->with('lines')->findOrFail($id);
        $user = auth()->user();
        $editable = $user->canEditPayroll()
            && in_array($run->status, [PayrollRun::STATUS_DRAFT, PayrollRun::STATUS_REJECTED], true);

        return view('finance.payroll.show', [
            'run' => $run,
            'canEdit' => $editable,
            'canSendToManager' => $user->canEditPayroll()
                && in_array($run->status, [PayrollRun::STATUS_DRAFT, PayrollRun::STATUS_REJECTED], true),
            'canFinalize' => $user->canFinalizePayroll()
                && $run->status === PayrollRun::STATUS_PENDING_MANAGER,
            'canMarkPaid' => $user->canEditPayroll()
                && $run->status === PayrollRun::STATUS_PROCESSED,
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
            ->with('status', 'Payroll draft ready for '.$period.'. Review, then send to Company Manager.');
    }

    public function updateStatus(Request $request, int $id, ApiPayrollRunController $api): RedirectResponse
    {
        $user = auth()->user();
        $status = (string) $request->input('status');

        $allowed = $user?->canEditPayroll()
            || ($user?->canFinalizePayroll() && in_array($status, [
                PayrollRun::STATUS_PROCESSED,
                PayrollRun::STATUS_REJECTED,
            ], true));

        abort_unless($allowed, 403);

        $response = $api->updateStatus($request, $id);
        $payload = $response->getData(true);

        if (! ($payload['success'] ?? false)) {
            return back()->withErrors(['status' => $payload['error']['message'] ?? 'Status update failed']);
        }

        $message = match ($status) {
            PayrollRun::STATUS_PENDING_MANAGER => 'Payroll sent to Company Manager for final decision',
            PayrollRun::STATUS_PROCESSED => 'Payroll approved',
            PayrollRun::STATUS_REJECTED => 'Payroll rejected — Finance can revise and resubmit',
            PayrollRun::STATUS_PAID => 'Payroll marked paid',
            PayrollRun::STATUS_DRAFT => 'Payroll returned to draft',
            default => 'Payroll status updated',
        };

        return back()->with('status', $message);
    }

    public function updateLine(Request $request, int $id, int $lineId, ApiPayrollRunController $api): RedirectResponse
    {
        abort_unless(auth()->user()?->canEditPayroll(), 403);

        $run = PayrollRun::query()->findOrFail($id);
        abort_unless(in_array($run->status, [PayrollRun::STATUS_DRAFT, PayrollRun::STATUS_REJECTED], true), 403);

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
