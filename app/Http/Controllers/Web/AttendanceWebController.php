<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\AttendanceController as ApiAttendanceController;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceSubmission;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceWebController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->canViewHr(), 403);

        $period = (string) ($request->query('period') ?: now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
            $period = now()->format('Y-m');
        }

        [$year, $month] = array_map('intval', explode('-', $period));
        $daysInMonth = (int) (new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t');
        $from = $period.'-01';
        $to = sprintf('%s-%02d', $period, $daysInMonth);

        $employees = Employee::query()->active()->with('party')->latestFirst()->get();
        $marks = Attendance::query()
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->get()
            ->groupBy(fn (Attendance $a) => $a->employee_id.'|'.$a->date?->format('Y-m-d').'|'.$a->session);

        $submissions = AttendanceSubmission::query()->latestFirst('period')->limit(12)->get();
        $today = now()->toDateString();

        return view('hr.attendance.index', [
            'period' => $period,
            'daysInMonth' => $daysInMonth,
            'year' => $year,
            'month' => $month,
            'employees' => $employees,
            'marks' => $marks,
            'submissions' => $submissions,
            'today' => $today,
            'canEdit' => auth()->user()->canEditHr(),
            'canApprove' => auth()->user()->hasRole('company_manager'),
        ]);
    }

    public function mark(Request $request, ApiAttendanceController $api): RedirectResponse
    {
        abort_unless(auth()->user()?->canEditHr(), 403);

        $date = (string) $request->input('date');
        if ($date !== now()->toDateString()) {
            return back()->withErrors(['date' => 'You can only mark attendance for TODAY']);
        }

        $status = $request->input('status');
        if ($status === 'clear') {
            $api->destroy($request);
        } else {
            $api->store($request);
        }

        return back()->with('status', 'Attendance updated');
    }

    public function holidayAll(Request $request, ApiAttendanceController $api): RedirectResponse
    {
        abort_unless(auth()->user()?->canEditHr(), 403);
        $date = $request->input('date') ?: now()->toDateString();
        $api->holidayAll($request);

        return back()->with('status', "Today ({$date}) marked as Holiday for all active employees");
    }

    public function compile(Request $request, ApiAttendanceController $api): RedirectResponse
    {
        abort_unless(auth()->user()?->canEditHr(), 403);
        $response = $api->submissionsStore($request);
        $payload = $response->getData(true);

        if (! ($payload['success'] ?? false)) {
            return back()->withErrors(['period' => $payload['error']['message'] ?? 'Compile failed']);
        }

        $period = $request->input('period') ?: now()->format('Y-m');
        $label = \DateTimeImmutable::createFromFormat('Y-m', $period)?->format('F Y') ?: $period;

        return back()->with('status', "Attendance for {$label} sent to Company Manager");
    }

    public function review(Request $request, int $id, ApiAttendanceController $api): RedirectResponse
    {
        abort_unless(
            auth()->user()?->hasRole('company_manager'),
            403,
        );

        $response = $api->submissionsUpdate($request, $id);
        $payload = $response->getData(true);

        if (! ($payload['success'] ?? false)) {
            return back()->withErrors(['status' => $payload['error']['message'] ?? 'Review failed']);
        }

        $status = $request->input('status');

        return back()->with(
            'status',
            $status === 'approved' ? 'Approved — payroll draft is in Finance' : 'Submission rejected',
        );
    }
}
