<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Party;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmployeeWebController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->canViewHr(), 403);

        $employees = Employee::query()->with('party')->orderByDesc('created_at')->get();

        return view('hr.employees.index', [
            'employees' => $employees,
            'canCreate' => auth()->user()->canEditHr(),
        ]);
    }

    public function show(int $id): View
    {
        abort_unless(auth()->user()?->canViewHr(), 403);

        $employee = Employee::query()->with('party')->findOrFail($id);

        return view('hr.employees.show', [
            'employee' => $employee,
            'detail' => $employee->toDetailArray(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canEditHr(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'job_title' => ['nullable', 'string'],
            'department' => ['nullable', 'string'],
            'hire_date' => ['nullable', 'date'],
            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
            'employee_account' => ['nullable', 'string'],
            'bank_name' => ['nullable', 'string'],
            'bank_account_number' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string'],
            'emergency_contact_phone' => ['nullable', 'string'],
            'photo_url' => ['nullable', 'string'],
            'id_image_url' => ['nullable', 'string'],
        ]);

        $employee = DB::transaction(function () use ($validated) {
            $party = Party::query()->create([
                'party_type' => 'employee',
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'approval_status' => 'approved',
            ]);

            return Employee::query()->create([
                'party_id' => $party->id,
                'employee_no' => 'EMP-'.substr((string) (int) (microtime(true) * 1000), -4),
                'job_title' => $validated['job_title'] ?? null,
                'department' => $validated['department'] ?? null,
                'hire_date' => $validated['hire_date'] ?? now()->toDateString(),
                'monthly_salary' => $validated['monthly_salary'] ?? null,
                'employee_account' => $validated['employee_account'] ?? null,
                'bank_name' => $validated['bank_name'] ?? 'Dashen Bank',
                'bank_account_number' => $validated['bank_account_number'] ?? null,
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
                'photo_url' => $validated['photo_url'] ?? null,
                'id_image_url' => $validated['id_image_url'] ?? null,
                'employment_status' => 'ACTIVE',
            ]);
        });

        $this->audit->log(auth()->user(), 'employee', (int) $employee->id, 'CREATE_EMPLOYEE', [
            'name' => $validated['name'],
        ], $request);

        return redirect()
            ->route('hr.employees')
            ->with('status', 'Employee registered');
    }
}
