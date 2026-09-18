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

        $employees = Employee::query()->active()->with('party')->orderBy('id')->get();
        $marks = Attendance::query()
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->get()
            ->groupBy(fn (Attendance $a) => $a->employee_id.'|'.$a->date?->format('Y-m-d').'|'.$a->session);

        $today = now()->toDateString();
        $todaySummary = [
            'total' => $employees->count(),
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'half_day' => 0,
            'leave_holiday' => 0,
            'not_marked' => 0,
        ];

        foreach ($employees as $emp) {
            $am = $marks->get($emp->id.'|'.$today.'|morning')?->first();
            $pm = $marks->get($emp->id.'|'.$today.'|afternoon')?->first();
            $statuses = array_values(array_filter([$am?->status, $pm?->status]));

            if ($statuses === []) {
                $todaySummary['not_marked']++;
            } elseif (in_array('holiday', $statuses, true) || in_array('leave', $statuses, true)) {
                $todaySummary['leave_holiday']++;
            } elseif (in_array('absent', $statuses, true) && ! in_array('present', $statuses, true) && ! in_array('late', $statuses, true)) {
                $todaySummary['absent']++;
            } elseif (in_array('half_day', $statuses, true) && ! in_array('present', $statuses, true) && ! in_array('late', $statuses, true)) {
                $todaySummary['half_day']++;
            } elseif (in_array('late', $statuses, true) && ! in_array('present', $statuses, true)) {
                $todaySummary['late']++;
            } elseif (in_array('present', $statuses, true) || in_array('late', $statuses, true)) {
                $todaySummary['present']++;
            } else {
                $todaySummary['not_marked']++;
            }
        }

        $sundays = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dow = (int) (new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $d)))->format('w');
            if ($dow === 0) {
                $sundays++;
            }
        }

        $submissions = AttendanceSubmission::query()->with('compiler')->orderByDesc('period')->limit(12)->get();

        return view('hr.attendance.index', [
            'period' => $period,
            'daysInMonth' => $daysInMonth,
            'year' => $year,
            'month' => $month,
            'employees' => $employees,
            'marks' => $marks,
            'submissions' => $submissions,
            'today' => $today,
            'todaySummary' => $todaySummary,
            'sundays' => $sundays,
            'workingDays' => max(0, $daysInMonth - $sundays),
            'periodLabel' => \DateTimeImmutable::createFromFormat('Y-m', $period)?->format('F Y') ?: $period,
            'canEdit' => auth()->user()->canMarkAttendance(),
            'canApprove' => auth()->user()->hasRole('company_manager') || auth()->user()->hasRole('manager'),
        ]);
    }

    public function mark(Request $request, ApiAttendanceController $api): RedirectResponse
    {
        abort_unless(auth()->user()?->canMarkAttendance(), 403);

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
        abort_unless(auth()->user()?->canMarkAttendance(), 403);
        $date = $request->input('date') ?: now()->toDateString();
        $api->holidayAll($request);

        return back()->with('status', "Today ({$date}) marked as Holiday for all active employees");
    }

    public function compile(Request $request, ApiAttendanceController $api): RedirectResponse
    {
        abort_unless(auth()->user()?->canMarkAttendance(), 403);
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
            auth()->user()?->hasRole('company_manager') || auth()->user()?->hasRole('manager'),
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
