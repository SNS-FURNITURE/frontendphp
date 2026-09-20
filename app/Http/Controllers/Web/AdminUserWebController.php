<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Party;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminUserWebController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $users = User::query()->with('roles')->orderByDesc('created_at')->get();
        $roles = Role::query()
            ->whereRaw('LOWER(name) != ?', ['admin'])
            ->orderBy('name')
            ->get();

        return view('admin.users', compact('users', 'roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $data = $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'role' => ['required', 'string'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'hire_date' => ['nullable', 'date'],
            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
            'employee_no' => ['nullable', 'string', 'max:100'],
            'national_id_number' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'employee_account' => ['nullable', 'string', 'max:255'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:100'],
        ]);

        if (strtolower($data['role']) === 'admin') {
            return back()->withErrors(['role' => 'Admin role cannot be assigned from this screen'])->withInput();
        }

        $role = Role::query()->where('name', $data['role'])->first();
        if (! $role) {
            return back()->withErrors(['role' => 'Unknown role'])->withInput();
        }

        $fullName = trim($data['full_name']);
        $email = isset($data['email']) && trim((string) $data['email']) !== ''
            ? strtolower(trim((string) $data['email']))
            : $this->emailFromFullName($fullName);

        $username = substr(explode('@', $email)[0] ?? 'user', 0, 64);
        if (strlen($username) < 3) {
            $username = 'user.'.substr((string) time(), -6);
        }

        if (User::query()->where('email', $email)->orWhere('username', $username)->exists()) {
            return back()->withErrors([
                'email' => 'An account with this email/username already exists: '.$email,
            ])->withInput();
        }

        try {
            $created = DB::transaction(function () use ($data, $fullName, $email, $username, $role, $request) {
                $user = new User;
                $user->full_name = $fullName;
                $user->email = $email;
                $user->username = $username;
                $user->phone = ($data['phone'] ?? null) ?: null;
                $user->password_hash = Hash::make('password123');
                $user->status = 'ACTIVE';
                $user->is_active = true;
                $user->save();

                DB::table('user_roles')->insert([
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'assigned_at' => now(),
                ]);

                $party = Party::query()->create([
                    'party_type' => 'employee',
                    'name' => $fullName,
                    'email' => $email,
                    'phone' => ($data['phone'] ?? null) ?: null,
                    'approval_status' => 'approved',
                    'created_by' => auth()->id(),
                    'approved_by' => auth()->id(),
                ]);

                $employeeNo = trim((string) ($data['employee_no'] ?? ''));
                if ($employeeNo === '') {
                    $employeeNo = 'EMP-'.substr((string) (int) (microtime(true) * 1000), -6);
                }

                $employee = Employee::query()->create([
                    'party_id' => $party->id,
                    'user_id' => $user->id,
                    'employee_no' => $employeeNo,
                    'employee_number' => $employeeNo,
                    'national_id_number' => ($data['national_id_number'] ?? null) ?: null,
                    'job_title' => ($data['job_title'] ?? null) ?: null,
                    'department' => ($data['department'] ?? null) ?: null,
                    'hire_date' => ($data['hire_date'] ?? null) ?: now()->toDateString(),
                    'monthly_salary' => $data['monthly_salary'] ?? null,
                    'employee_account' => ($data['employee_account'] ?? null) ?: null,
                    'bank_name' => ($data['bank_name'] ?? null) ?: 'Dashen Bank',
                    'bank_account_number' => ($data['bank_account_number'] ?? null) ?: null,
                    'emergency_contact_name' => ($data['emergency_contact_name'] ?? null) ?: null,
                    'emergency_contact_relationship' => ($data['emergency_contact_relationship'] ?? null) ?: null,
                    'emergency_contact_phone' => ($data['emergency_contact_phone'] ?? null) ?: null,
                    'employment_status' => 'ACTIVE',
                ]);

                $this->audit->log(auth()->user(), 'user', (int) $user->id, 'CREATE_EMPLOYEE_ACCOUNT', [
                    'full_name' => $fullName,
                    'email' => $email,
                    'username' => $username,
                    'role' => $role->name,
                    'employee_id' => $employee->id,
                    'party_id' => $party->id,
                ], $request);

                return ['user' => $user, 'employee' => $employee];
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'full_name' => 'Could not create employee account. Check unique email/employee number.',
            ])->withInput();
        }

        return redirect()
            ->route('admin.users')
            ->with(
                'status',
                'Employee account created for '.$fullName.
                ' — login: '.$email.' / password123 (employee #'.$created['employee']->id.')'
            );
    }

    private function emailFromFullName(string $fullName): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '.', $fullName) ?? '', '.'));
        $slug = preg_replace('/\.+/', '.', $slug) ?: 'employee';
        $slug = substr($slug, 0, 64);

        return $slug.'@sns.com';
    }
}
