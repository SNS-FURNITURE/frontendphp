@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="page-head">
    <div>
        <h1>My Profile</h1>
        <p class="muted" style="margin:0.35rem 0 0">Update your workspace identity and password.</p>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <h3 style="margin-top:0">Profile</h3>
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PATCH')
            <label>Full name</label>
            <input name="full_name" value="{{ old('full_name', $user->full_name) }}" required>
            <label>Username</label>
            <input name="username" value="{{ old('username', $user->username) }}" required>
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
            <label>Phone</label>
            <input name="phone" value="{{ old('phone', $user->phone) }}">
            <button class="btn" type="submit">Save profile</button>
        </form>
    </div>
    <div class="card">
        <h3 style="margin-top:0">Change password</h3>
        <form method="POST" action="{{ route('profile.password') }}">
            @csrf
            @method('PATCH')
            <label>Current password</label>
            <input type="password" name="current_password" required>
            <label>New password</label>
            <input type="password" name="new_password" required>
            <label>Confirm password</label>
            <input type="password" name="confirm_password" required>
            <p class="muted">At least 8 characters with one letter and one number.</p>
            <button class="btn secondary" type="submit">Update password</button>
        </form>
    </div>
</div>
@endsection
