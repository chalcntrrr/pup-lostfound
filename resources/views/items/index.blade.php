@extends('layouts.app')

@section('title', auth()->user()->isAdmin() ? 'Admin Dashboard' : auth()->user()->roleLabel() . ' Dashboard')
@section('search-bar')
    <form method="GET" action="{{ route('items.index') }}" role="search">
        @if ($filter !== 'All')<input type="hidden" name="filter" value="{{ $filter }}">@endif
        <input class="search-field" type="search" name="q" value="{{ $search }}" placeholder="Search..." aria-label="Search items">
    </form>
@endsection

@section('content')
    <h1 class="page-title">{{ auth()->user()->isAdmin() ? 'ADMIN DASHBOARD' : strtoupper(auth()->user()->roleLabel()) . ' DASHBOARD' }}</h1>

    <form class="toolbar" method="GET" action="{{ route('items.index') }}">
        @if ($search)<input type="hidden" name="q" value="{{ $search }}">@endif
        <select class="filter-combo" name="filter" aria-label="Filter" onchange="this.form.submit()">
            @foreach ($filters as $option)
                <option value="{{ $option }}" @selected($filter === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <noscript><button class="btn-outline" type="submit">Filter</button></noscript>
    </form>

    @if ($items->isEmpty())
        <div class="empty-state">
            <p>{{ $search ? 'No items match "' . $search . '".' : 'No open items right now.' }}</p>
            <a class="btn-primary" href="{{ route('items.create', ['type' => 'lost']) }}">REPORT LOST ITEM</a>
        </div>
    @else
        <div class="item-grid">
            @foreach ($items as $item)
                @include('partials.card', ['item' => $item])
            @endforeach
        </div>
        {{ $items->links() }}
    @endif
@endsection
