@extends('layouts.app')

{{-- "+ Add Another Admin", the original CreateAdminAccount card. Super Admins only. --}}
@section('title', 'Create Admin Account')

@section('content')
    <div style="display:flex;justify-content:center;padding-top:24px">
        <div class="admin-login-card">
            <h2 class="admin-login-title">Create Admin Account</h2>
            <p class="admin-login-subtitle">Only a Super Admin can add accounts. The new account can log in right away.</p>

            <form method="POST" action="{{ route('admin.admins.store') }}" novalidate>
                @csrf
                @php($role = old('role', 'admin'))
                <div class="field">
                    <span class="form-label">Account Type <span class="required-star">*</span></span>
                    <div class="role-pick">
                        <label><input type="radio" name="role" value="admin" @checked($role === 'admin')> Admin</label>
                        <label @if($slotsLeft === 0) class="muted" @endif><input type="radio" name="role" value="superadmin" @checked($role === 'superadmin') @disabled($slotsLeft === 0)> Super Admin</label>
                    </div>
                    <div class="field-hint">
                        Admins handle items and claims. Super Admins can also add accounts and reset passwords
                        ({{ $slotsLeft === 0 ? 'limit of ' . \App\Models\User::MAX_SUPERADMINS . ' reached' : $slotsLeft . ' of ' . \App\Models\User::MAX_SUPERADMINS . ' Super Admin slot' . ($slotsLeft === 1 ? '' : 's') . ' left' }}).
                    </div>
                    @error('role') <div class="error-label">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label class="form-label" for="name">Name <span class="required-star">*</span></label>
                    <input class="form-input @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="Surname, First Name">
                    @error('name') <div class="error-label">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label class="form-label" for="email">Username (Email) <span class="required-star">*</span></label>
                    <input class="form-input @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email') }}">
                    @error('email') <div class="error-label">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label class="form-label" for="password">Password <span class="required-star">*</span></label>
                    <input class="form-input @error('password') is-invalid @enderror" id="password" type="password" name="password" placeholder="Enter password">
                    @error('password') <div class="error-label">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label class="form-label" for="password_confirmation">Confirm Password <span class="required-star">*</span></label>
                    <input class="form-input" id="password_confirmation" type="password" name="password_confirmation" placeholder="Re-enter password">
                </div>

                <button class="btn-primary" type="submit">CREATE ACCOUNT</button>
                <a class="btn-cancel" href="{{ route('admin.users') }}">BACK</a>
            </form>
        </div>
    </div>
@endsection
