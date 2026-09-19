@extends('layouts.app')

@section('title', 'Users · SNS Furniture')

@section('content')
<div class="page-head">
    <div>
        <h1>Users</h1>
        <p class="muted" style="margin:.35rem 0 0">Create staff accounts. Admin role cannot be assigned here.</p>
    </div>
</div>

<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin-top:0;font-size:1.1rem">Create user</h2>
    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        <div class="grid-2">
            <div>
                <label for="full_name">Full name</label>
                <input id="full_name" name="full_name" value="{{ old('full_name') }}" required minlength="2">
            </div>
            <div>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required>
            </div>
            <div>
                <label for="username">Username (optional)</label>
                <input id="username" name="username" value="{{ old('username') }}">
            </div>
            <div>
                <label for="phone">Phone (optional)</label>
                <input id="phone" name="phone" value="{{ old('phone') }}">
            </div>
            <div>
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}" @selected(old('role', 'supervisor') === $role->name)>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="password">Password (optional, default password123)</label>
                <input id="password" name="password" type="password" minlength="6" placeholder="password123">
            </div>
        </div>
        <button type="submit" class="btn">Create user</button>
    </form>
</div>

<div class="card">
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
@endsection
