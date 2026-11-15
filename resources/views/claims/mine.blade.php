@extends('layouts.app')

@section('title', 'My Claims')

@section('content')
    <h1 class="page-title">MY CLAIMS</h1>

    <div class="claims-list">
        @forelse ($claims as $claim)
            @include('partials.claim', ['claim' => $claim, 'showItem' => true])
        @empty
            <div class="empty-state">
                <p>No claims yet. Open a FOUND item and press CLAIM if it's yours.</p>
                <a class="btn-primary" href="{{ route('items.index', ['filter' => 'FOUND']) }}">VIEW FOUND ITEMS</a>
            </div>
        @endforelse
        {{ $claims->links() }}
    </div>
@endsection
