{{-- Item card, same layout as the original ItemCard.fxml. Needs: $item --}}
@php($word = $item->isLost() ? 'Lost' : 'Found')
<article class="item-card {{ ($item->match_count ?? 0) > 0 ? 'has-match' : '' }}">
    @if (($item->match_count ?? 0) > 0)
        <a class="match-strip" href="{{ route('items.show', $item) }}#matches">POSSIBLE MATCH FOUND</a>
    @endif
    <div class="card-image-wrap">
        @if ($item->image_path)
            <img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}" loading="lazy">
        @endif
        @include('partials.badge', ['item' => $item])
    </div>
    <div class="card-body">
        <h3 class="card-name">{{ $item->name }}</h3>
        <div class="card-meta">
            @if ($item->description)<span class="clip" title="{{ $item->description }}">{{ $item->description }}</span>@endif
            <span class="clip">Category: {{ $item->category->name }}</span>
            <span class="clip">Date {{ strtolower($word) }}: {{ $item->date_happened->format('Y-m-d') }}</span>
            <span class="clip">Location: {{ $item->location }}</span>
            @if ($item->isFound() && $item->isOpen())
                <span class="clip {{ $item->isLongUnclaimed() ? 'warn' : '' }}">At the office: {{ $item->daysAtOfficeLabel() }}</span>
            @endif
        </div>
    </div>
    <div class="card-footer">
        <a class="btn-view" href="{{ route('items.show', $item) }}">VIEW DETAILS</a>
    </div>
</article>
