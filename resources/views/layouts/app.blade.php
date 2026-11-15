<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') - PUPSRC Lost and Found</title>
    <link rel="icon" href="{{ asset('images/logo-dashboard.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@php($user = auth()->user())
<header class="navbar">
    <a class="navbar-brand" href="{{ route('dashboard') }}">
        <img src="{{ asset('images/logo-dashboard.png') }}" alt="">
        <span class="navbar-title">PUPSRC Lost and Found</span>
    </a>

    <div class="navbar-spacer"></div>

    @yield('search-bar')

    {{-- "+" button (the original ReportMenuHelper): students/faculty report their own LOST items;
         the office logs FOUND items, or a LOST item for a walk-in owner who has no account. --}}
    @if ($user->isAdmin())
        <details class="menu">
            <summary class="navbar-icon-btn" title="New report" aria-label="New report"><span class="plus-icon">+</span></summary>
            <div class="menu-panel">
                <a href="{{ route('items.create', ['type' => 'found']) }}">Log a FOUND (surrendered) item</a>
                <a href="{{ route('items.create', ['type' => 'lost']) }}">Log a LOST item (walk-in owner)</a>
            </div>
        </details>
    @else
        <a class="navbar-icon-btn" href="{{ route('items.create', ['type' => 'lost']) }}" title="Report a lost item" aria-label="Report a lost item"><span class="plus-icon">+</span></a>
    @endif

    {{-- ☰ hamburger menu (same as the original NavbarHelper) --}}
    <details class="menu">
        <summary class="navbar-icon-btn" title="Menu" aria-label="Menu"><img src="{{ asset('images/menu-bar.png') }}" alt=""></summary>
        <div class="menu-panel">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('account') }}">Account</a>
            <a href="{{ route('items.mine') }}">My Reports</a>
            @if ($user->isAdmin())
                @php($pending = \App\Models\Claim::where('status', 'pending')->count())
                <a href="{{ route('admin.claims') }}">Claims @if($pending)<span class="menu-count">{{ $pending }}</span>@endif</a>
                <a href="{{ route('admin.users') }}">Users</a>
                @if ($user->isSuperAdmin())
                    <a href="{{ route('admin.logs') }}">Activity Log</a>
                @endif
            @else
                <a href="{{ route('claims.mine') }}">My Claims</a>
            @endif
            <hr>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="menu-logout" type="submit">Logout</button>
            </form>
        </div>
    </details>
</header>

<main class="main-content">
    @include('partials.flash')
    @yield('content')
</main>

<script>
    // Close an open dropdown when clicking anywhere else, and keep only one open.
    document.addEventListener('click', function (e) {
        document.querySelectorAll('details.menu[open]').forEach(function (d) {
            if (!d.contains(e.target)) d.removeAttribute('open');
        });
    });
</script>
@stack('scripts')
</body>
</html>
