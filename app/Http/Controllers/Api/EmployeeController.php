<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Party;
use App\Models\User;
use App\Services\AuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): JsonResponse
    {
        $rows = Employee::query()
            ->with('party')
            ->latestFirst()
            ->get()
            ->map(fn (Employee $e) => $e->toListArray())
            ->values()
            ->all();

        return ApiResponse::success($rows);
    }

    public function show(int $id): JsonResponse
    {
        $employee = Employee::query()->with('party')->find($id);
        if (! $employee) {
            return ApiResponse::error('Employee not found', 'NOT_FOUND', 404);
        }

        return ApiResponse::success($employee->toDetailArray());
    }

    public function store(Request $request): JsonResponse
    {
        $name = $request->input('name');
        if (! $name) {
            return ApiResponse::error('Employee name is required', 'INVALID_INPUT', 400);
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        $created = DB::transaction(function () use ($request, $name) {
            $party = Party::query()->create([
                'party_type' => 'employee',
                'name' => $name,
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'address' => $request->input('address'),
                'approval_status' => 'approved',
            ]);

            $empNo = 'EMP-'.substr((string) (int) (microtime(true) * 1000), -4);

            $attrs = [
                'party_id' => $party->id,
                'user_id' => $request->input('user_id'),
                'employee_no' => $empNo,
                'department' => $request->input('department'),
                'job_title' => $request->input('job_title'),
                'hire_date' => $request->input('hire_date') ?: now()->toDateString(),
            ];

            foreach ([
                'employee_number', 'national_id_number', 'employee_account',
                'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone',
                'photo_url', 'id_image_url', 'cv_url', 'bank_name', 'bank_account_number', 'monthly_salary',
                'employment_status',
            ] as $field) {
                if ($request->has($field)) {
                    $attrs[$field] = $request->input($field);
                }
            }

            if ($request->filled('national_id') && ! isset($attrs['national_id_number'])) {
                $attrs['national_id_number'] = $request->input('national_id');
            }

            return Employee::query()->create($attrs);
        });

        $this->audit->log($user, 'employee', (int) $created->id, 'CREATE_EMPLOYEE', [
            'name' => $name,
            'job_title' => $created->job_title,
            'department' => $created->department,
        ], $request);

        return ApiResponse::success([
            'id' => (int) $created->id,
            'party_id' => (int) $created->party_id,
            'name' => $name,
            'job_title' => $created->job_title,
            'department' => $created->department,
        ], null, 201);
    }
}
