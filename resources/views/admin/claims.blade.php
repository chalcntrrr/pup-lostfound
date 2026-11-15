@extends('layouts.app')

@section('title', 'Claims')

@section('content')
    <h1 class="page-title">CLAIMS</h1>
    <p class="page-subtitle">Compare each claimant's description and proof with the actual item before approving.</p>

    <nav class="tabs" aria-label="Claim status">
        @foreach (['pending' => 'PENDING', 'approved' => 'APPROVED', 'rejected' => 'REJECTED', 'all' => 'ALL'] as $key => $label)
            <a class="{{ $status === $key ? 'page-btn-active' : 'page-btn' }}" href="{{ route('admin.claims', ['status' => $key]) }}">{{ $label }}</a>
        @endforeach
    </nav>

    <div class="claims-list">
        @forelse ($claims as $claim)
            @include('partials.claim', ['claim' => $claim, 'showItem' => true])
        @empty
            <div class="empty-state"><p>No {{ $status === 'all' ? '' : $status }} claims.</p></div>
        @endforelse
        {{ $claims->links() }}
    </div>
@endsection
