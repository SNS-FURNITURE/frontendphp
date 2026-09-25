<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\AuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): JsonResponse
    {
        $rows = LeaveRequest::query()
            ->with('approver')
            ->latestFirst()
            ->get()
            ->map(fn (LeaveRequest $l) => $l->toApiArray())
            ->values()
            ->all();

        return ApiResponse::success($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $employeeName = $request->input('employee_name');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (! $employeeName || ! $startDate || ! $endDate) {
            return ApiResponse::error(
                'Employee name, start date, and end date are required',
                'INVALID_INPUT',
                400,
            );
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        $reason = $request->input('reason') ?: 'Leave';
        if ($request->filled('leave_type') && $reason === 'Leave') {
            $reason = (string) $request->input('leave_type');
        }

        $leave = LeaveRequest::query()->create([
            'employee_id' => $request->input('employee_id'),
            'employee_name' => $employeeName,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        $this->audit->log($user, 'leave_request', (int) $leave->id, 'CREATE_LEAVE_REQUEST', [
            'employee_name' => $employeeName,
            'reason' => $reason,
        ], $request);

        return ApiResponse::success($leave->fresh('approver')?->toApiArray(), null, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $status = $request->input('status');
        if (! in_array($status, ['pending', 'approved', 'rejected'], true)) {
            return ApiResponse::error('Invalid leave status', 'INVALID_INPUT', 400);
        }

        $leave = LeaveRequest::query()->find($id);
        if (! $leave) {
            return ApiResponse::error('Leave request not found', 'NOT_FOUND', 404);
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        $leave->status = $status;
        if ($status === 'approved') {
            $leave->approved_by = $user?->id;
        }
        $leave->save();

        $this->audit->log($user, 'leave_request', (int) $leave->id, 'UPDATE_LEAVE_STATUS', [
            'status' => $status,
        ], $request);

        return ApiResponse::success($leave->fresh('approver')?->toApiArray());
    }
}
