<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
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
            DB::transaction(function () use ($data, $fullName, $email, $username, $role, $request) {
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

                $this->audit->log(auth()->user(), 'user', (int) $user->id, 'CREATE_USER_ACCOUNT', [
                    'full_name' => $fullName,
                    'email' => $email,
                    'username' => $username,
                    'role' => $role->name,
                ], $request);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'full_name' => 'Could not create user account. Check unique email/username.',
            ])->withInput();
        }

        return redirect()
            ->route('admin.users')
            ->with('status', 'User account created for '.$fullName.' — login: '.$email.' / password123');
    }

    private function emailFromFullName(string $fullName): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '.', $fullName) ?? '', '.'));
        $slug = preg_replace('/\.+/', '.', $slug) ?: 'employee';
        $slug = substr($slug, 0, 64);

        return $slug.'@sns.com';
    }
}
