@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
@php $isAdmin = auth()->user()?->isAdmin(); @endphp
<div class="page-head">
    <div>
        <h1>My Profile</h1>
        <p class="muted" style="margin:0.35rem 0 0">
            @if ($isAdmin)
                As an admin, you can update your profile details.
            @else
                You can update your password below. Your profile details are managed by the admin.
            @endif
        </p>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <h3 style="margin-top:0">Profile</h3>
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PATCH')
            <label>Full name</label>
            @if ($isAdmin)
                <input name="full_name" value="{{ old('full_name', $user->full_name) }}" required minlength="2">
            @else
                <input value="{{ $user->full_name }}" readonly disabled>
                <p class="muted" style="margin:-0.5rem 0 0.85rem;font-size:.8rem">Only an admin can change your full name.</p>
            @endif
            <label>Username</label>
            @if ($isAdmin)
                <input name="username" value="{{ old('username', $user->username) }}" required>
            @else
                <input value="{{ $user->username }}" readonly disabled>
            @endif
            <label>Email</label>
            @if ($isAdmin)
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
            @else
                <input value="{{ $user->email }}" readonly disabled>
            @endif
            <label>Phone</label>
            @if ($isAdmin)
                <input name="phone" value="{{ old('phone', $user->phone) }}">
                <button class="btn" type="submit" style="margin-top: 1rem;">Save profile</button>
            @else
                <input value="{{ $user->phone }}" readonly disabled>
                <p class="muted" style="margin:-0.5rem 0 0.85rem;font-size:.8rem">Only an admin can change your profile details.</p>
            @endif
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
