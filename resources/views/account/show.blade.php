@extends('layouts.app')

{{-- ACCOUNT, same layout as the original Account.fxml --}}
@section('title', 'Account')

@section('content')
    <div class="account-card">
        <h1 class="page-title" style="margin-bottom:6px">ACCOUNT</h1>
        <hr class="account-divider">

        <div class="avatar-circle" aria-hidden="true">{{ mb_substr($user->name, 0, 1) }}</div>
        <div class="account-role-label">{{ $user->roleLabel() }}</div>
        <div class="account-name-field">{{ $user->name }}</div>

        <div class="account-info">
            {{ $user->email }}
            @if ($user->idNumber())<br>{{ $user->idNumber() }}@endif
            @if ($user->course_section) · {{ $user->course_section }}@endif
            @if ($user->contact_number)<br>{{ $user->contact_number }}@endif
        </div>

        <div class="account-actions">
            @if ($user->isSuperAdmin())
                <a class="btn-dark-pill" href="{{ route('admin.admins.create') }}">+ Add Another Admin</a>
                <a class="btn-dark-pill" href="{{ route('admin.users') }}">Manage Accounts</a>
                <a class="btn-dark-pill" href="{{ route('admin.claims') }}">Review Claims{{ $pendingClaims ? " ({$pendingClaims})" : '' }}</a>
                <a class="btn-dark-pill" href="{{ route('items.create', ['type' => 'found']) }}">Log Surrendered Item</a>
                <a class="btn-dark-pill" href="{{ route('items.create', ['type' => 'lost']) }}">Log Walk-in Lost Item</a>
            @elseif ($user->isAdmin())
                <a class="btn-dark-pill" href="{{ route('admin.claims') }}">Review Claims{{ $pendingClaims ? " ({$pendingClaims})" : '' }}</a>
                <a class="btn-dark-pill" href="{{ route('items.create', ['type' => 'found']) }}">Log Surrendered Item</a>
                <a class="btn-dark-pill" href="{{ route('items.create', ['type' => 'lost']) }}">Log Walk-in Lost Item</a>
            @else
                <a class="btn-dark-pill" href="{{ route('items.mine') }}">My Reports</a>
            @endif
        </div>

        <details class="account-edit" @if($errors->hasAny(['name', 'contact_number', 'course_section'])) open @endif>
            <summary>Edit my details</summary>
            <form method="POST" action="{{ route('account.update') }}" novalidate>
                @csrf
                @method('PUT')
                <div class="field">
                    <label class="form-label" for="name">Name</label>
                    <input class="form-input @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}">
                    @error('name') <div class="error-label">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label class="form-label" for="contact_number">Contact Number</label>
                    <input class="form-input @error('contact_number') is-invalid @enderror" id="contact_number" name="contact_number" value="{{ old('contact_number', $user->contact_number) }}" placeholder="09XX-XXX-XXXX">
                    @error('contact_number') <div class="error-label">{{ $message }}</div> @enderror
                </div>
                @unless ($user->isAdmin())
                    <div class="field">
                        <label class="form-label" for="course_section">{{ $user->isFaculty() ? 'Department / College' : 'Course and Section' }}</label>
                        <input class="form-input" id="course_section" name="course_section" value="{{ old('course_section', $user->course_section) }}">
                    </div>
                @endunless
                <div class="button-row" style="margin-top:8px"><button class="btn-primary" type="submit">SAVE</button></div>
            </form>
        </details>

        <details class="account-edit" @if($openPassword) open @endif>
            <summary>Change password</summary>
            <form method="POST" action="{{ route('account.password') }}" novalidate>
                @csrf
                @method('PUT')
                <div class="field">
                    <label class="form-label" for="current_password">Current Password</label>
                    <input class="form-input @error('current_password') is-invalid @enderror" id="current_password" type="password" name="current_password" autocomplete="current-password">
                    @error('current_password') <div class="error-label">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label class="form-label" for="new_password">New Password</label>
                    <input class="form-input @error('password') is-invalid @enderror" id="new_password" type="password" name="password" autocomplete="new-password">
                    <div class="field-hint">At least 6 characters.</div>
                    @error('password') <div class="error-label">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label class="form-label" for="password_confirmation">Confirm New Password</label>
                    <input class="form-input" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">
                </div>
                <div class="button-row" style="margin-top:8px"><button class="btn-primary" type="submit">CHANGE PASSWORD</button></div>
            </form>
        </details>

        <hr class="account-divider" style="margin-top:24px">
        <h2 class="history-section-label">History</h2>

        @forelse ($history as $log)
            <div class="history-card">
                <div class="history-head">
                    <span class="history-log-id">LOG #{{ $log->id }}</span>
                    <span class="history-timestamp">{{ $log->created_at->format('Y-m-d g:i A') }}</span>
                </div>
                <div class="history-card-title">{{ $log->action }}</div>
                @if ($log->details)<div class="history-card-sub">{{ $log->details }}</div>@endif
            </div>
        @empty
            <p class="muted" style="text-align:left">No activity yet.</p>
        @endforelse
    </div>
@endsection
