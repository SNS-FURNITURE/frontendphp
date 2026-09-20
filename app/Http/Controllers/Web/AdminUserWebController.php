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
            'email' => ['required', 'email', 'max:255'],
            'username' => ['nullable', 'string', 'max:64'],
            'phone' => ['nullable', 'string', 'max:64'],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'string'],
        ]);

        if (strtolower($data['role']) === 'admin') {
            return back()->withErrors(['role' => 'Admin role cannot be assigned from this screen']);
        }

        $role = Role::query()->where('name', $data['role'])->first();
        if (! $role) {
            return back()->withErrors(['role' => 'Unknown role']);
        }

        $email = strtolower(trim($data['email']));
        $username = isset($data['username']) && trim((string) $data['username']) !== ''
            ? strtolower(trim((string) $data['username']))
            : preg_replace('/[^a-z0-9._-]/', '.', explode('@', $email)[0] ?? 'user');
        $username = substr((string) $username, 0, 64);
        if (strlen($username) < 3) {
            $username = 'user.'.substr((string) time(), -6);
        }

        try {
            DB::transaction(function () use ($data, $email, $username, $role, $request) {
                $user = new User;
                $user->full_name = trim($data['full_name']);
                $user->email = $email;
                $user->username = $username;
                $user->phone = ($data['phone'] ?? null) ?: null;
                $user->password_hash = Hash::make($data['password'] ?: 'password123');
                $user->status = 'ACTIVE';
                $user->is_active = true;
                $user->save();

                DB::table('user_roles')->insert([
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'assigned_at' => now(),
                ]);

                $this->audit->log(auth()->user(), 'user', (int) $user->id, 'CREATE_USER', [
                    'full_name' => $user->full_name,
                    'email' => $email,
                    'username' => $username,
                    'role' => $role->name,
                ], $request);
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['email' => 'Email or username is already in use'])->withInput();
        }

        return redirect()->route('admin.users')->with('status', 'User account for '.$data['full_name'].' created');
    }
}
