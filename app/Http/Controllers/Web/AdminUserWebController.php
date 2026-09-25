<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Support\ErpRoles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserWebController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $users = User::query()->with('roles')->latestFirst()->get();
        $roleGroups = ErpRoles::assignableGrouped();

        return view('admin.users', compact('users', 'roleGroups'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        ErpRoles::syncCatalog();

        $data = $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'role' => ['required', 'string', Rule::in(ErpRoles::catalogNames())],
        ]);

        if (strtolower($data['role']) === 'admin') {
            return back()->withErrors(['role' => 'Admin role cannot be assigned from this screen'])->withInput();
        }

        $role = Role::query()->where('name', $data['role'])->first();
        if (! $role) {
            return back()->withErrors(['role' => 'Unknown role'])->withInput();
        }

        $fullName = trim($data['full_name']);
        $email = $this->uniqueCredentialsFromName($fullName);

        try {
            DB::transaction(function () use ($data, $fullName, $email, $role, $request) {
                $user = new User;
                $user->full_name = $fullName;
                $user->email = $email;
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
                    'role' => $role->name,
                ], $request);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'full_name' => 'Could not create user account. Try again.',
            ])->withInput();
        }

        return redirect()
            ->route('admin.users')
            ->with('status', 'User account created for '.$fullName.' — '.$email.' / password123');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        ErpRoles::syncCatalog();

        $data = $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'role' => ['nullable', 'string', Rule::in(ErpRoles::catalogNames())],
        ]);

        $fullName = trim($data['full_name']);
        $user->full_name = $fullName;
        $user->phone = ($data['phone'] ?? null) !== null && $data['phone'] !== ''
            ? $data['phone']
            : null;
        $user->save();

        if (! empty($data['role']) && ! $user->isAdmin()) {
            $role = Role::query()->where('name', $data['role'])->first();
            if ($role) {
                $user->roles()->sync([$role->id => ['assigned_at' => now()]]);
            }
        }

        $this->audit->log(auth()->user(), 'user', (int) $user->id, 'UPDATE_USER_CONTACT', [
            'full_name' => $fullName,
            'phone' => $user->phone,
            'role' => $data['role'] ?? null,
        ], $request);

        return redirect()
            ->route('admin.users')
            ->with('status', 'Updated account for '.$fullName);
    }

    public function destroy(Request $request, User $user): RedirectResponse|JsonResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        if ($user->id === auth()->id()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You cannot delete your own account.',
                ], 422);
            }

            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        $fullName = $user->full_name;
        $userId = $user->id;

        $user->roles()->detach();
        $user->delete();

        $this->audit->log(auth()->user(), 'user', (int) $userId, 'DELETE_USER_ACCOUNT', [
            'full_name' => $fullName,
        ], $request);

        $message = 'Deleted user account for '.$fullName;

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'id' => (int) $userId,
            ]);
        }

        return redirect()
            ->route('admin.users')
            ->with('status', $message);
    }

    private function uniqueCredentialsFromName(string $fullName): string
    {
        $base = $this->slugFromFullName($fullName);
        $username = $base;
        $n = 2;

        while (
            User::query()
                ->where('email', $username.'@sns.com')
                ->exists()
        ) {
            $suffix = (string) $n;
            $username = substr($base, 0, max(1, 64 - strlen($suffix))).$suffix;
            $n++;
            if ($n > 9999) {
                $username = 'user.'.substr((string) (int) (microtime(true) * 1000), -8);
                break;
            }
        }

        return $username.'@sns.com';
    }

    private function slugFromFullName(string $fullName): string
    {
        $firstName = preg_split('/\s+/', trim($fullName), 2)[0] ?? $fullName;
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '', $firstName) ?? ''));
        $slug = substr($slug !== '' ? $slug : 'user', 0, 64);

        if (strlen($slug) < 3) {
            $slug = 'user'.$slug;
        }

        return $slug;
    }
}
