@if (session('success'))
    <div class="flash" role="status">
        {{ session('success') }}
        @if (session('slip'))
            <a class="flash-link" href="{{ route('claims.slip', session('slip')) }}">View claim slip</a>
        @endif
    </div>
@endif
@if (session('error'))
    <div class="flash flash-error" role="alert">{{ session('error') }}</div>
@endif
