@extends('layouts.app')

{{-- Admins can VIEW accounts. Only a Super Admin can add admins, reset passwords and delete accounts. --}}
@php($me = auth()->user())

@section('title', 'Users')

@section('content')
    <h1 class="page-title">USERS</h1>
    <p class="page-subtitle">
        @if ($me->isSuperAdmin())
            Super Admins: {{ \App\Models\User::MAX_SUPERADMINS - \App\Models\User::superAdminSlotsLeft() }} of {{ \App\Models\User::MAX_SUPERADMINS }}. You can add admins, promote an Admin to Super Admin, reset passwords and delete accounts.
        @else
            Only a Super Admin can add admins, reset passwords or delete accounts.
        @endif
    </p>

    <div class="table-search">
        <form class="actions-inline" method="GET" action="{{ route('admin.users') }}" role="search">
            <input class="form-input" name="q" value="{{ $search }}" placeholder="Name, email, student or employee ID" aria-label="Search users">
            <button class="btn-primary" type="submit">SEARCH</button>
        </form>
        @if ($me->isSuperAdmin())
            <a class="btn-outline" href="{{ route('admin.admins.create') }}">+ ADD ADMIN</a>
        @endif
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr><th>Name</th><th>Role</th><th>ID No.</th><th>Email</th><th>Contact</th><th>Reports</th><th>Claims</th><th>Joined</th>@if($me->isSuperAdmin())<th></th>@endif</tr>
            </thead>
            <tbody>
            @forelse ($users as $u)
                <tr>
                    <td><strong>{{ $u->name }}</strong>@if($u->course_section)<br><span class="muted">{{ $u->course_section }}</span>@endif</td>
                    <td><span class="badge badge-{{ $u->role }}">{{ strtoupper($u->roleLabel()) }}</span></td>
                    <td>{{ $u->idNumber() ?? '-' }}</td>
                    <td>{{ $u->email }}</td>
                    <td>{{ $u->contact_number ?? '-' }}</td>
                    <td>{{ $u->items_count }}</td>
                    <td>{{ $u->claims_count }}</td>
                    <td>{{ $u->created_at->format('Y-m-d') }}</td>
                    @if ($me->isSuperAdmin())
                        <td class="user-actions">
                            @if ($u->id === $me->id)
                                <span class="muted">You</span>
                            @elseif ($u->isSuperAdmin())
                                <form method="POST" action="{{ route('admin.users.demote', $u) }}"
                                      onsubmit="return confirm('Demote {{ addslashes($u->name) }} to Admin? They lose Super Admin access (accounts, activity log).')">
                                    @csrf
                                    @method('PUT')
                                    <button class="btn-cancel btn-sm" type="submit">DEMOTE TO ADMIN</button>
                                </form>
                            @else
                                @if ($u->role === 'admin')
                                    <form method="POST" action="{{ route('admin.users.promote', $u) }}"
                                          onsubmit="return confirm('Promote {{ addslashes($u->name) }} to Super Admin?')">
                                        @csrf
                                        @method('PUT')
                                        <button class="btn-outline btn-sm" type="submit">PROMOTE TO SUPER ADMIN</button>
                                    </form>
                                @endif
                                <details class="reset-pw" @if(old('reset_for') == $u->id) open @endif>
                                    <summary class="btn-cancel btn-sm">RESET PASSWORD</summary>
                                    <form method="POST" action="{{ route('admin.users.password', $u) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="reset_for" value="{{ $u->id }}">
                                        <input class="form-input" type="text" name="new_password" placeholder="New password" aria-label="New password for {{ $u->name }}" autocomplete="off">
                                        @if (old('reset_for') == $u->id && $errors->has('new_password'))
                                            <div class="error-label">{{ $errors->first('new_password') }}</div>
                                        @endif
                                        <button class="btn-primary btn-sm" type="submit">SET PASSWORD</button>
                                    </form>
                                </details>
                                @php($keep = $u->deletionBlockReason())
                                @if ($keep)
                                    <span class="muted" title="{{ $keep }}">Kept (on record)</span>
                                @else
                                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}"
                                          onsubmit="return confirm('Delete {{ addslashes($u->name) }}? Their lost reports and pending claims will be deleted too.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-danger btn-sm" type="submit">DELETE</button>
                                    </form>
                                @endif
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="9" class="muted">No users match "{{ $search }}".</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
@endsection
