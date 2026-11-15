@extends('layouts.app')

@section('title', 'My Reports')

@section('content')
    <h1 class="page-title">MY REPORTS</h1>

    @if ($items->isEmpty())
        <div class="empty-state">
            <p>You haven't reported anything yet. Lost something? Use the + button at the top to report it. If you found an item, please surrender it at the Lost and Found office.</p>
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
