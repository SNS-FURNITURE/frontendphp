@extends('layouts.app')

@section('title', 'Users · SNS Furniture')

@section('content')
<div class="page-head" style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap">
    <div>
        <h1>Users</h1>
        <p class="muted" style="margin:.35rem 0 0">
            Admin only: create ERP login accounts. Default password is <strong>password123</strong>.
            Email defaults to <code>firstname.lastname@sns.com</code>. Employee HR details stay on the HR employees page.
        </p>
    </div>
    <button class="btn" type="button" onclick="document.getElementById('create-user').hidden=false">Add user account</button>
</div>

@if (session('status'))
    <div class="card" style="margin-bottom:1rem;border-color:#16a34a">
        <p style="margin:0;color:#15803d">{{ session('status') }}</p>
    </div>
@endif

@if ($errors->any())
    <div class="card" style="margin-bottom:1rem;border-color:#dc2626">
        <ul style="margin:0;padding-left:1.2rem;color:#b91c1c">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card" id="create-user" style="margin-bottom:1.25rem" @if(!$errors->any()) hidden @endif>
    <h2 style="margin-top:0;font-size:1.1rem">Create user account</h2>
    <form method="POST" action="{{ route('admin.users.store') }}" id="hire-form">
        @csrf
        <div class="grid-2">
            <div>
                <label for="full_name">Full name *</label>
                <input id="full_name" name="full_name" value="{{ old('full_name') }}" required minlength="2"
                       placeholder="e.g. Abebe Kebede" autocomplete="name">
                <p class="muted" style="margin:.35rem 0 0;font-size:.8rem">Used to build login email automatically.</p>
            </div>
            <div>
                <label for="email">Login email (optional override)</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                       placeholder="auto: name@sns.com">
                <p class="muted" style="margin:.35rem 0 0;font-size:.8rem" id="email-preview">Leave blank to auto-generate.</p>
            </div>
            <div>
                <label for="phone">Phone</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" placeholder="+2519…">
            </div>
            <div>
                <label for="role">ERP role *</label>
                <select id="role" name="role" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}" @selected(old('role') === $role->name)>{{ $role->name }}</option>
                    @endforeach
                </select>
                <p class="muted" style="margin:.35rem 0 0;font-size:.8rem">Admin role cannot be assigned here.</p>
            </div>
            <div>
                <label>Password</label>
                <input type="text" value="password123" disabled>
                <p class="muted" style="margin:.35rem 0 0;font-size:.8rem">Fixed default — user should change after first login.</p>
            </div>
        </div>
        <div class="toolbar" style="margin-top:1rem">
            <button type="submit" class="btn">Create account</button>
            <button class="btn ghost" type="button" onclick="document.getElementById('create-user').hidden=true">Cancel</button>
        </div>
    </form>
</div>

<div class="card">
    <h2 style="margin-top:0;font-size:1.1rem">Existing accounts</h2>
    <table class="data">
        <thead>
        <tr>
            <th>Name</th>
            <th>Username</th>
            <th>Email</th>
            <th>Roles</th>
            <th>Active</th>
            <th>Created</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($users as $user)
            <tr>
                <td>{{ $user->full_name }}</td>
                <td>{{ '@'.($user->username ?: '—') }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</td>
                <td>{{ $user->is_active ? 'Yes' : 'No' }}</td>
                <td>{{ optional($user->created_at)->format('Y-m-d') }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">No users found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<script>
(function () {
    const nameInput = document.getElementById('full_name');
    const emailInput = document.getElementById('email');
    const preview = document.getElementById('email-preview');
    if (!nameInput || !preview) return;

    function slugEmail(name) {
        const slug = String(name || '')
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, '.')
            .replace(/^\.+|\.+$/g, '')
            .replace(/\.+/g, '.')
            .slice(0, 64) || 'employee';
        return slug + '@sns.com';
    }

    function updatePreview() {
        if (emailInput && emailInput.value.trim() !== '') {
            preview.textContent = 'Will use: ' + emailInput.value.trim().toLowerCase();
            return;
        }
        preview.textContent = 'Will use: ' + slugEmail(nameInput.value);
    }

    nameInput.addEventListener('input', updatePreview);
    if (emailInput) emailInput.addEventListener('input', updatePreview);
    updatePreview();
})();
</script>
@endsection
