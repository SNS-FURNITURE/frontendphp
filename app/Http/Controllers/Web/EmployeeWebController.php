<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Party;
use App\Services\AuditService;
use App\Services\Payroll\EthiopiaPayrollCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        $detail = $employee->toDetailArray();

        return view('hr.employees.show', [
            'employee' => $employee,
            'detail' => $detail,
            'salary' => $this->salaryPreview($employee),
            'canEdit' => auth()->user()->canEditHr(),
        ]);
    }

    public function pdf(int $id): View
    {
        abort_unless(auth()->user()?->canViewHr(), 403);

        $employee = Employee::query()->with('party')->findOrFail($id);

        return view('hr.employees.pdf', [
            'employee' => $employee,
            'detail' => $employee->toDetailArray(),
            'salary' => $this->salaryPreview($employee),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canEditHr(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'job_title' => ['nullable', 'string'],
            'department' => ['nullable', 'string'],
            'hire_date' => ['nullable', 'date'],
            'national_id_number' => ['nullable', 'string'],
            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
            'employee_account' => ['nullable', 'string'],
            'bank_name' => ['nullable', 'string'],
            'bank_account_number' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string'],
            'emergency_contact_relationship' => ['nullable', 'string'],
            'emergency_contact_phone' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'id_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
            'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);

        $employee = DB::transaction(function () use ($request, $validated) {
            $party = Party::query()->create([
                'party_type' => 'employee',
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'approval_status' => 'approved',
            ]);

            $employee = Employee::query()->create([
                'party_id' => $party->id,
                'employee_no' => 'EMP-'.substr((string) (int) (microtime(true) * 1000), -4),
                'job_title' => $validated['job_title'] ?? null,
                'department' => $validated['department'] ?? null,
                'hire_date' => $validated['hire_date'] ?? now()->toDateString(),
                'national_id_number' => $validated['national_id_number'] ?? null,
                'monthly_salary' => $validated['monthly_salary'] ?? null,
                'employee_account' => $validated['employee_account'] ?? null,
                'bank_name' => $validated['bank_name'] ?? 'Commercial Bank of Ethiopia',
                'bank_account_number' => $validated['bank_account_number'] ?? null,
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_relationship' => $validated['emergency_contact_relationship'] ?? null,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
                'employment_status' => 'ACTIVE',
            ]);

            $employee->forceFill($this->storeUploadedDocuments($request, (int) $employee->id))->save();

            return $employee->fresh();
        });

        $this->audit->log(auth()->user(), 'employee', (int) $employee->id, 'CREATE_EMPLOYEE', [
            'name' => $validated['name'],
        ], $request);

        return redirect()
            ->route('hr.employees.show', $employee->id)
            ->with('status', 'Employee registered');
    }

    public function updateDocuments(Request $request, int $id): RedirectResponse
    {
        abort_unless(auth()->user()?->canEditHr(), 403);

        $employee = Employee::query()->findOrFail($id);

        $request->validate([
            'photo' => ['nullable', 'image', 'max:5120'],
            'id_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
            'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);

        if (! $request->hasFile('photo') && ! $request->hasFile('id_image') && ! $request->hasFile('cv')) {
            return back()->withErrors(['photo' => 'Choose at least one file to upload']);
        }

        $paths = $this->storeUploadedDocuments($request, (int) $employee->id, $employee);
        $employee->forceFill($paths)->save();

        $this->audit->log(auth()->user(), 'employee', (int) $employee->id, 'UPDATE_EMPLOYEE_FILES', [
            'fields' => array_keys($paths),
        ], $request);

        return back()->with('status', 'Files uploaded');
    }

    /**
     * @return array<string, string>
     */
    private function storeUploadedDocuments(Request $request, int $employeeId, ?Employee $existing = null): array
    {
        $dir = 'employees/'.$employeeId;
        $updates = [];

        foreach ([
            'photo' => 'photo_url',
            'id_image' => 'id_image_url',
            'cv' => 'cv_url',
        ] as $input => $column) {
            if (! $request->hasFile($input)) {
                continue;
            }

            /** @var UploadedFile $file */
            $file = $request->file($input);
            $old = $existing?->{$column};
            $path = $file->store($dir, 'public');
            $updates[$column] = $path;

            if (is_string($old) && $old !== '' && ! preg_match('#^https?://#i', $old) && Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }
        }

        return $updates;
    }

    /**
     * @return array{gross: float, paye: float, net: float}|null
     */
    private function salaryPreview(Employee $employee): ?array
    {
        if ($employee->monthly_salary === null) {
            return null;
        }

        $line = EthiopiaPayrollCalculator::computePayrollLine([
            'basic_salary' => (float) $employee->monthly_salary,
            'days_worked' => EthiopiaPayrollCalculator::STANDARD_MONTHLY_DAYS,
            'role_multiplier' => EthiopiaPayrollCalculator::roleMultiplierFor($employee->job_title),
        ]);

        return [
            'gross' => (float) $line['gross'],
            'paye' => (float) $line['paye'],
            'net' => (float) $line['net'],
        ];
    }
}
