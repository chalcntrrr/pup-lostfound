@extends('layouts.app')

@section('title', 'Activity Log')

@section('content')
    <h1 class="page-title">ACTIVITY LOG</h1>
    <p class="page-subtitle">Office activity only (Admin and Super Admin). Student and faculty activity is not recorded. The latest {{ number_format($keep) }} entries are kept.</p>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Log</th><th>Timestamp</th><th>User</th><th>Activity</th><th>Details</th><th>IP Address</th></tr></thead>
            <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td><span class="history-log-id">#{{ $log->id }}</span></td>
                    <td><span class="history-timestamp">{{ $log->created_at->format('Y-m-d g:i:s A') }}</span></td>
                    <td>{{ $log->user?->name ?? 'Guest' }}@if($log->user)<br><span class="muted">{{ $log->user->roleLabel() }}</span>@endif</td>
                    <td><strong>{{ $log->action }}</strong></td>
                    <td>{{ $log->details }}</td>
                    <td class="muted">{{ $log->ip_address }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No activity yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $logs->links() }}
@endsection
