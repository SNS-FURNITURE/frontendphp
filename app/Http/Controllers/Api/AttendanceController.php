<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceSubmission;
use App\Models\Employee;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NotifyService;
use App\Services\Payroll\PayrollGenerationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    private const SESSIONS = ['morning', 'afternoon'];

    public function __construct(
        private AuditService $audit,
        private NotifyService $notify,
        private PayrollGenerationService $payroll,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Attendance::query()
            ->with(['employee.party'])
            ->latestFirst('date');

        if ($request->filled('date')) {
            $query->whereDate('date', $request->query('date'));
        }
        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->query('to'));
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->query('employee_id'));
        }

        $rows = $query->get()->map(fn (Attendance $a) => $a->toApiArray())->values()->all();

        return ApiResponse::success($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $employeeId = $request->input('employee_id');
        if (! $employeeId) {
            return ApiResponse::error('Employee ID is required', 'INVALID_INPUT', 400);
        }

        $attDate = $request->input('date') ?: now()->toDateString();
        $session = $this->normalizeSession($request->input('session'));
        $status = $request->input('status', 'present');

        $existing = Attendance::query()
            ->where('employee_id', $employeeId)
            ->whereDate('date', $attDate)
            ->where('session', $session)
            ->first();

        if ($existing) {
            $existing->fill([
                'check_in' => $request->input('check_in'),
                'check_out' => $request->input('check_out'),
                'status' => $status,
            ]);
            $existing->save();
        } else {
            Attendance::query()->create([
                'employee_id' => $employeeId,
                'date' => $attDate,
                'session' => $session,
                'check_in' => $request->input('check_in'),
                'check_out' => $request->input('check_out'),
                'status' => $status,
            ]);
        }

        return ApiResponse::success([
            'employee_id' => (int) $employeeId,
            'date' => $attDate,
            'session' => $session,
            'status' => $status,
        ], null, 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $employeeId = $request->input('employee_id');
        $date = $request->input('date');

        if (! $employeeId || ! $date) {
            return ApiResponse::error('Employee ID and date are required', 'INVALID_INPUT', 400);
        }

        Attendance::query()
            ->where('employee_id', $employeeId)
            ->whereDate('date', $date)
            ->where('session', $this->normalizeSession($request->input('session')))
            ->delete();

        return ApiResponse::success(['message' => 'Attendance mark cleared']);
    }

    public function holidayAll(Request $request): JsonResponse
    {
        $attDate = $request->input('date') ?: now()->toDateString();
        $employees = Employee::query()->pluck('id');

        foreach ($employees as $empId) {
            foreach (self::SESSIONS as $session) {
                $existing = Attendance::query()
                    ->where('employee_id', $empId)
                    ->whereDate('date', $attDate)
                    ->where('session', $session)
                    ->first();

                if ($existing) {
                    $existing->status = 'holiday';
                    $existing->save();
                } else {
                    Attendance::query()->create([
                        'employee_id' => $empId,
                        'date' => $attDate,
                        'session' => $session,
                        'status' => 'holiday',
                    ]);
                }
            }
        }

        return ApiResponse::success([
            'date' => $attDate,
            'message' => 'Marked holiday for all employees',
        ]);
    }

    public function submissionsIndex(): JsonResponse
    {
        $rows = AttendanceSubmission::query()
            ->with(['compiler', 'approver'])
            ->latestFirst('period')
            ->get()
            ->map(fn (AttendanceSubmission $s) => $s->toApiArray())
            ->values()
            ->all();

        return ApiResponse::success($rows);
    }

    public function submissionsStore(Request $request): JsonResponse
    {
        $period = (string) ($request->input('period') ?: now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
            return ApiResponse::error('Period must be in YYYY-MM format', 'INVALID_INPUT', 400);
        }

        $existing = AttendanceSubmission::query()->where('period', $period)->first();
        if ($existing && $existing->status !== 'rejected') {
            return ApiResponse::error(
                'This month has already been compiled for manager review',
                'CONFLICT',
                409,
            );
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        $days = $this->payroll->workingDaysInPeriod($period);
        $from = $period.'-01';
        $to = sprintf('%s-%02d', $period, $days['totalDays']);

        $employees = Employee::query()
            ->active()
            ->with('party')
            ->get();

        $records = Attendance::query()
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->get(['employee_id', 'status']);

        $byEmp = [];
        foreach ($records as $rec) {
            $eid = (int) $rec->employee_id;
            $current = $byEmp[$eid] ?? [
                'present' => 0, 'late' => 0, 'half_day' => 0, 'absent' => 0, 'leave' => 0, 'holiday' => 0,
            ];
            $status = (string) $rec->status;
            if (isset($current[$status])) {
                $current[$status] += 0.5;
            }
            $byEmp[$eid] = $current;
        }

        $summary = [
            'period' => $period,
            'workingDays' => $days['workingDays'],
            'employees' => $employees->map(function (Employee $emp) use ($byEmp) {
                $counts = $byEmp[(int) $emp->id] ?? [
                    'present' => 0, 'late' => 0, 'half_day' => 0, 'absent' => 0, 'leave' => 0, 'holiday' => 0,
                ];

                return array_merge([
                    'id' => (int) $emp->id,
                    'job_title' => $emp->job_title,
                    'department' => $emp->department,
                    'employee_name' => $emp->party?->name,
                ], $counts);
            })->values()->all(),
        ];

        if ($existing) {
            $existing->fill([
                'status' => 'pending_manager',
                'compiled_by' => $user?->id,
                'summary_json' => $summary,
                'submitted_at' => now(),
                'approved_by' => null,
                'reviewed_at' => null,
            ]);
            $existing->save();
        } else {
            AttendanceSubmission::query()->create([
                'period' => $period,
                'status' => 'pending_manager',
                'compiled_by' => $user?->id,
                'summary_json' => $summary,
            ]);
        }

        foreach ($this->notify->activeUserIdsWithRolesRaw(['company_manager']) as $managerId) {
            $this->notify->notifyUser($managerId, [
                'type' => 'attendance_payroll_review',
                'title' => "Attendance compiled for {$period}",
                'message' => ($user?->full_name ?: 'HR')." submitted {$period} attendance for payroll review.",
                'entityType' => 'attendance_submission',
                'entityId' => 0,
            ]);
        }

        $this->audit->log($user, 'attendance_submission', 0, 'COMPILE_ATTENDANCE', ['period' => $period], $request);

        $row = AttendanceSubmission::query()->with(['compiler', 'approver'])->where('period', $period)->first();

        return ApiResponse::success($row?->toApiArray(), null, 201);
    }

    public function submissionsUpdate(Request $request, int $id): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        if (! $user || ! $user->hasRole('company_manager')) {
            return ApiResponse::error('Only the Company Manager can approve attendance', 'FORBIDDEN', 403);
        }

        $status = $request->input('status');
        if (! in_array($status, ['approved', 'rejected'], true)) {
            return ApiResponse::error('Status must be approved or rejected', 'INVALID_INPUT', 400);
        }

        $submission = AttendanceSubmission::query()->find($id);
        if (! $submission) {
            return ApiResponse::error('Submission not found', 'NOT_FOUND', 404);
        }

        if ($submission->status !== 'pending_manager') {
            return ApiResponse::error('This submission is not awaiting manager review', 'CONFLICT', 409);
        }

        $submission->fill([
            'status' => $status,
            'approved_by' => $user->id,
            'reviewed_at' => now(),
        ]);
        $submission->save();

        if ($status === 'approved') {
            foreach ($this->notify->activeUserIdsWithRolesRaw(['finance']) as $financeId) {
                $this->notify->notifyUser($financeId, [
                    'type' => 'attendance_calendar_approved',
                    'title' => "Attendance approved for {$submission->period}",
                    'message' => "Company Manager approved the {$submission->period} attendance calendar. Generate payroll when ready.",
                    'entityType' => 'attendance_submission',
                    'entityId' => (int) $submission->id,
                ]);
            }
        }

        $this->audit->log($user, 'attendance_submission', (int) $submission->id, 'REVIEW_ATTENDANCE', [
            'status' => $status,
            'period' => $submission->period,
        ], $request);

        $updated = AttendanceSubmission::query()->with(['compiler', 'approver'])->find($id);

        return ApiResponse::success($updated?->toApiArray());
    }

    private function normalizeSession(mixed $value): string
    {
        return $value === 'afternoon' ? 'afternoon' : 'morning';
    }
}
