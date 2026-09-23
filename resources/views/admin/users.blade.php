@extends('layouts.app')

@section('title', 'Users')

@section('content')
<div class="page-head" style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap">
    <div>
        <h1>Users</h1>
        <p class="muted" style="margin:.35rem 0 0">
            Admin only: create ERP login accounts. Username and company email are auto-built from the first name
            (e.g. <code>abebe</code> / <code>abebe@sns.com</code>). Default password is <strong>password123</strong>.
        </p>
    </div>
    <button class="btn" type="button" onclick="document.getElementById('create-user').hidden=false">Add user account</button>
</div>

<div class="card" id="create-user" style="margin-bottom:1.25rem" @if(!$errors->any()) hidden @endif>
    <h2 style="margin-top:0;font-size:1.1rem">Create user account</h2>
    <form method="POST" action="{{ route('admin.users.store') }}" id="hire-form">
        @csrf
        <div class="grid-2">
            <div>
                <label for="full_name">Full name *</label>
                <input id="full_name" name="full_name" value="{{ old('full_name') }}" required minlength="2"
                       placeholder="e.g. Abebe Kebede" autocomplete="name">
                <p class="muted" style="margin:.35rem 0 0;font-size:.8rem" id="cred-preview">Username and company email are created from this name.</p>
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
    <p class="muted" style="margin:0 0 1rem;font-size:.85rem">Only admins can change any user’s full name and phone (including their own).</p>
    <div class="table-wrap">
    <table class="data">
        <thead>
        <tr>
            <th>Name</th>
            <th>Phone</th>
            <th>Username</th>
            <th>Company email</th>
            <th>Roles</th>
            <th>Active</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse ($users as $user)
            <tr>
                <td>{{ $user->full_name }}</td>
                <td>{{ $user->phone ?: '—' }}</td>
                <td>{{ '@'.($user->username ?: '—') }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</td>
                <td>
                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                    @if ($user->id === auth()->id()) <span class="muted" style="font-size:0.8rem"> (you)</span> @endif
                </td>
                <td>
                    <div style="display:flex;gap:0.5rem;align-items:center">
                        <button type="button" class="btn" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; background: #000; color: #fff; border-color: #000;" onclick="document.getElementById('edit-user-{{ $user->id }}').hidden = !document.getElementById('edit-user-{{ $user->id }}').hidden">Edit</button>
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Are you sure you want to completely delete {{ $user->full_name }}? This cannot be undone.');" style="margin:0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn ghost" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; color: #dc2626;">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <tr id="edit-user-{{ $user->id }}" hidden>
                <td colspan="7" style="padding-top:1rem;padding-bottom:1rem; background: var(--bg-alt, #f9f9f9);">
                    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="grid-2" style="margin:0;align-items:end">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label>Full name</label>
                            <input name="full_name" value="{{ old('full_name', $user->full_name) }}" required minlength="2" style="margin-bottom:0">
                        </div>
                        <div>
                            <label>Phone</label>
                            <input name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+2519…" style="margin-bottom:0">
                        </div>
                        @if (!$user->isAdmin())
                        <div>
                            <label>ERP Role</label>
                            <select name="role" required style="margin-bottom:0">
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}" @selected($user->roles->contains('name', $role->name))>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <div>
                            <label>ERP Role</label>
                            <input value="Admin" readonly disabled style="margin-bottom:0; background: #eee;">
                        </div>
                        @endif
                        <div class="toolbar" style="justify-content:flex-end;width:100%;grid-column: 1 / -1; margin-top: 0.5rem;">
                            <button type="button" class="btn ghost" onclick="document.getElementById('edit-user-{{ $user->id }}').hidden = true">Cancel</button>
                            <button type="submit" class="btn" style="margin:0">Save changes</button>
                        </div>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="muted">No users found.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

<script>
(function () {
    const nameInput = document.getElementById('full_name');
    const preview = document.getElementById('cred-preview');
    if (!nameInput || !preview) return;

    function slugName(name) {
        const first = String(name || '').trim().split(/\s+/)[0] || '';
        let slug = first.toLowerCase().replace(/[^a-z0-9]+/g, '').slice(0, 64) || 'user';
        if (slug.length < 3) slug = 'user' + slug;
        return slug;
    }

    function updatePreview() {
        const username = slugName(nameInput.value);
        preview.textContent = 'Will create: @' + username + ' / ' + username + '@sns.com (unique suffix added if needed)';
    }

    nameInput.addEventListener('input', updatePreview);
    updatePreview();
})();
</script>
@endsection
