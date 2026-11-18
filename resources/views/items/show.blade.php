@extends('layouts.app')

{{-- FULL DETAILS, same layout as the original FullDetails.fxml --}}
@php
    $user = auth()->user();
    $Word = $item->isLost() ? 'Lost' : 'Found';
    $reporter = $item->user;
@endphp

@section('title', $item->name . ' - Details')

@section('content')
    <div class="form-panel">
        <h1 class="page-title">FULL DETAILS</h1>

        <div class="form-columns">
            <div>
                <h2 class="section-heading">{{ $Word }} Item Details</h2>

                <div class="detail-img-wrap">
                    @if ($item->image_path)
                        <img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}">
                    @else
                        No image uploaded
                    @endif
                </div>

                <div class="field">
                    <span class="form-label">Item Name</span>
                    <div class="value-box">{{ $item->name }}</div>
                </div>
                <div class="field">
                    <span class="form-label">Category</span>
                    <div class="value-box soft">{{ $item->category->name }}</div>
                </div>
                <div class="field">
                    <span class="form-label">Description</span>
                    <div class="value-box multiline">{{ $item->description }}</div>
                </div>
            </div>

            <div>
                @if ($item->isLost())
                    <h2 class="section-heading">Reporter Information</h2>

                    <div class="field">
                        <span class="form-label">Name</span>
                        <div class="value-box">{{ $item->ownerName() }}{{ $item->isWalkIn() ? ' (Walk-in)' : ($reporter->isFaculty() ? ' (Faculty)' : '') }}</div>
                    </div>
                    @if ($user->isAdmin() || $user->id === $reporter->id)
                        <div class="field">
                            <span class="form-label">Student / Employee ID</span>
                            <div class="value-box {{ $item->ownerIdNumber() ? '' : 'muted' }}">{{ $item->ownerIdNumber() ?: 'Not provided' }}</div>
                        </div>
                        <div class="field">
                            <span class="form-label">Contact Number</span>
                            <div class="value-box {{ $item->ownerContact() ? '' : 'muted' }}">{{ $item->ownerContact() ?: '09XX-XXX-XXXX' }}</div>
                        </div>
                        @if ($item->isWalkIn() && $user->isAdmin())
                            <div class="field">
                                <span class="form-label">Logged by (walk-in report, owner has no account)</span>
                                <div class="value-box soft">{{ $reporter->name }}</div>
                            </div>
                        @endif
                    @else
                        {{-- Contact details are only for the office: found items are surrendered there, not handed over student to student. --}}
                        <div class="field">
                            <span class="form-label">Contact</span>
                            <div class="value-box soft">Found it? Surrender it at the Lost and Found office.</div>
                        </div>
                    @endif
                @else
                    <h2 class="section-heading">Surrender Information</h2>

                    <div class="field">
                        <span class="form-label">Where to claim</span>
                        <div class="value-box">Lost and Found Office</div>
                    </div>
                    @if ($item->isOpen())
                        <div class="field">
                            <span class="form-label">Days at the office</span>
                            <div class="value-box {{ $item->isLongUnclaimed() ? 'warn' : '' }}">{{ $item->daysAtOfficeLabel() }}</div>
                            @if ($user->isAdmin() && $item->isLongUnclaimed())
                                <div class="field-hint">Unclaimed for {{ \App\Models\Item::LONG_UNCLAIMED_DAYS }}+ days. Following office policy, you can RESOLVE it outside the system (e.g. donated or disposed).</div>
                            @endif
                        </div>
                    @endif
                    @if ($user->isAdmin())
                        {{-- Finder details are confidential: admins only (privacy disclaimer on the form) --}}
                        <div class="field">
                            <span class="form-label">Surrendered by (visible to admins only)</span>
                            <div class="value-box {{ $item->finder_name ? '' : 'muted' }}">
                                @if ($item->finder_name)
                                    {{ $item->finder_name }}{{ $item->finder_student_id ? ' · ' . $item->finder_student_id : '' }}{{ $item->finder_contact ? ' · ' . $item->finder_contact : '' }}
                                @else
                                    Anonymous finder
                                @endif
                            </div>
                        </div>
                        <div class="field">
                            <span class="form-label">Logged by</span>
                            <div class="value-box soft">{{ $reporter->name }}</div>
                        </div>
                    @endif
                @endif
                <div class="field">
                    <span class="form-label">Location {{ $Word }}</span>
                    <div class="value-box">{{ $item->location }}</div>
                </div>
                <div class="field-row">
                    <div class="field">
                        <span class="form-label">Date {{ $Word }}</span>
                        <div class="value-box">{{ $item->date_happened->format('Y-m-d') }}</div>
                    </div>
                    <div class="field">
                        <span class="form-label">Time {{ $Word }}</span>
                        <div class="value-box">{{ $item->timeLabel() ?? '-' }}</div>
                    </div>
                </div>
                <div class="field">
                    <span class="form-label">Status</span>
                    <div class="value-box soft">@include('partials.badge', ['item' => $item])</div>
                </div>
                @if ($item->resolution === 'matched' && $item->matchedItem)
                    <p class="resolution-note">Resolved with found item
                        <a href="{{ route('items.show', $item->matchedItem) }}">Tag #{{ str_pad($item->matchedItem->id, 4, '0', STR_PAD_LEFT) }} · {{ $item->matchedItem->name }}</a>.</p>
                @elseif ($item->resolution === 'outside')
                    <p class="resolution-note">Resolved outside the system.</p>
                @endif
                @foreach ($item->resolvedLostReports as $lost)
                    <p class="resolution-note">Returned to the owner of lost report
                        <a href="{{ route('items.show', $lost) }}">Tag #{{ str_pad($lost->id, 4, '0', STR_PAD_LEFT) }} · {{ $lost->name }}</a>.</p>
                @endforeach
                <p class="field-hint">Reported on {{ $item->created_at->format('M j, Y g:i A') }}</p>
            </div>
        </div>

        <div class="button-row">
            <a class="btn-cancel" href="{{ url()->previous() !== url()->current() ? url()->previous() : route('items.index') }}">CANCEL</a>

            @if ($user->isAdmin() && $item->isFound() && $item->isOpen())
                <button class="btn-primary" type="button" data-open-release>RELEASE TO CLAIMANT</button>
            @endif

            @if (($user->isAdmin() && $item->status !== 'resolved') || ($item->user_id === $user->id && $item->isLost() && $item->isOpen()))
                <button class="btn-primary" type="button" data-open-resolve>RESOLVE</button>
            @endif

            @if ($item->canBeManagedBy($user))
                <a class="btn-cancel" href="{{ route('items.edit', $item) }}">EDIT</a>
                @unless ($item->deleteBlockReason())
                    <form method="POST" action="{{ route('items.destroy', $item) }}" onsubmit="return confirm('Delete this report? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button class="btn-danger" type="submit">DELETE</button>
                    </form>
                @endunless
            @endif
        </div>
    </div>

    @if ($claims->isNotEmpty())
        <section class="section-block">
            <h2 class="section-heading">{{ $user->isAdmin() ? 'Claims for this item' : 'Your claim' }}</h2>
            @foreach ($claims as $claim)
                @include('partials.claim', ['claim' => $claim, 'showItem' => false])
            @endforeach
        </section>
    @endif

    @if (($user->isAdmin() && $item->status !== 'resolved') || ($item->user_id === $user->id && $item->isLost() && $item->isOpen()))
        @php($canMatch = $user->isAdmin() && $item->isLost() && $item->isOpen())
        @php($defaultMode = old('mode', $canMatch && $matches->isNotEmpty() ? 'matched' : 'outside'))
        {{-- RESOLVE dialog, same choices as the Java app --}}
        <dialog class="resolve-dialog" id="resolve-dialog" @if($errors->has('mode') || $errors->has('found_item_id') || $errors->has('id_presented')) data-open @endif>
            <form method="POST" action="{{ route('items.resolve', $item) }}">
                @csrf
                <h2 class="section-heading" style="margin-bottom:6px">Resolve report</h2>
                <p class="resolve-helper">{{ $item->name }}</p>

                @if ($canMatch)
                    <label class="form-check resolve-option {{ $matches->isEmpty() ? 'is-disabled' : '' }}">
                        <input type="radio" name="mode" value="matched" @checked($defaultMode === 'matched') @disabled($matches->isEmpty())>
                        Resolve with an existing found item
                    </label>
                    <div class="resolve-match-list" data-match-list>
                        @forelse ($matches as $match)
                            <label class="resolve-match">
                                <input type="radio" name="found_item_id" value="{{ $match->id }}" @checked(old('found_item_id') == $match->id || ($loop->first && ! old('found_item_id')))>
                                <span>
                                    <span class="resolve-match-title">#{{ str_pad($match->id, 4, '0', STR_PAD_LEFT) }} {{ $match->name }}</span>
                                    <span class="resolve-match-description">{{ $match->category->name }} · Found at {{ $match->location }} · {{ $match->date_happened->format('Y-m-d') }}</span>
                                </span>
                            </label>
                        @empty
                            <p class="resolve-helper" style="padding:12px">No matching found items are currently posted.</p>
                        @endforelse
                    </div>
                    <div class="field" data-match-id style="margin:8px 0 4px">
                        <label class="form-label" for="resolve_id_presented">ID presented by the owner <span class="required-star">*</span></label>
                        <div class="field-hint" style="margin:0 0 6px">Check it against: {{ $item->ownerName() }}{{ $item->ownerIdNumber() ? ' · ' . $item->ownerIdNumber() : '' }}</div>
                        <input class="form-input @error('id_presented') is-invalid @enderror" id="resolve_id_presented" name="id_presented"
                               value="{{ old('id_presented') }}" placeholder="e.g. PUP School ID">
                    </div>
                    <p class="resolve-helper">Do this when {{ $item->ownerName() }} is at the office to receive the item. It will be marked as CLAIMED, released to them, and a claim slip will be issued.</p>
                @endif

                <label class="form-check resolve-option">
                    <input type="radio" name="mode" value="outside" @checked($defaultMode === 'outside')>
                    @if ($item->isLost() && $item->user_id === $user->id)
                        I got it back (resolved outside the system)
                    @elseif ($item->isFound())
                        Resolve outside the system (e.g. turned over or unclaimed)
                    @else
                        Resolve outside the system database
                    @endif
                </label>

                @error('mode') <div class="error-label">{{ $message }}</div> @enderror
                @error('found_item_id') <div class="error-label">{{ $message }}</div> @enderror
                @error('id_presented') <div class="error-label">{{ $message }}</div> @enderror

                <div class="button-row" style="margin-top:20px">
                    <button class="btn-cancel" type="button" data-close-resolve>CANCEL</button>
                    <button class="btn-primary" type="submit">RESOLVE</button>
                </div>
            </form>
        </dialog>
    @endif

    @if ($user->isAdmin() && $item->isFound() && $item->isOpen())
        {{-- RELEASE TO CLAIMANT: the person is at the office, no account or earlier report needed --}}
        <dialog class="resolve-dialog" id="release-dialog" @if($errors->hasAny(['claimant_name', 'claimant_id_number', 'claimant_contact', 'proof_details', 'id_presented'])) data-open @endif>
            <form method="POST" action="{{ route('items.release', $item) }}">
                @csrf
                <h2 class="section-heading" style="margin-bottom:6px">Release to claimant</h2>
                <p class="resolve-helper">{{ $item->name }}. Check the claimant's ID and ask them to describe the item before releasing it.</p>

                <div class="field">
                    <label class="form-label" for="claimant_name">Claimant's name <span class="required-star">*</span></label>
                    <input class="form-input @error('claimant_name') is-invalid @enderror" id="claimant_name" name="claimant_name" maxlength="100" value="{{ old('claimant_name') }}" placeholder="Surname, First Name">
                    @error('claimant_name') <div class="error-label">{{ $message }}</div> @enderror
                </div>
                <div class="field-row">
                    <div class="field">
                        <label class="form-label" for="claimant_id_number">Student / Employee ID</label>
                        <input class="form-input @error('claimant_id_number') is-invalid @enderror" id="claimant_id_number" name="claimant_id_number" maxlength="30" value="{{ old('claimant_id_number') }}" placeholder="If any">
                        @error('claimant_id_number') <div class="error-label">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label class="form-label" for="claimant_contact">Contact number</label>
                        <input class="form-input @error('claimant_contact') is-invalid @enderror" id="claimant_contact" name="claimant_contact" value="{{ old('claimant_contact') }}" placeholder="09XX-XXX-XXXX">
                        @error('claimant_contact') <div class="error-label">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="field">
                    <label class="form-label" for="proof_details">What the claimant said about the item <span class="required-star">*</span></label>
                    <textarea class="form-input @error('proof_details') is-invalid @enderror" id="proof_details" name="proof_details" rows="3" maxlength="1000" placeholder="Description, contents, marks, where and when it was lost">{{ old('proof_details') }}</textarea>
                    @error('proof_details') <div class="error-label">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label class="form-label" for="release_id_presented">ID presented by the claimant <span class="required-star">*</span></label>
                    <input class="form-input @error('id_presented') is-invalid @enderror" id="release_id_presented" name="id_presented" maxlength="100" value="{{ old('id_presented') }}" placeholder="e.g. PUP School ID, Driver's License">
                    @error('id_presented') <div class="error-label">{{ $message }}</div> @enderror
                </div>
                <p class="resolve-helper">The item will be marked as CLAIMED and a claim slip will be issued.</p>

                <div class="button-row" style="margin-top:20px">
                    <button class="btn-cancel" type="button" data-close-release>CANCEL</button>
                    <button class="btn-primary" type="submit">RELEASE</button>
                </div>
            </form>
        </dialog>
    @endif

    @if ($matches->isNotEmpty())
        <section class="section-block" id="matches">
            <h2 class="section-heading">Possible matches</h2>
            <p class="field-hint" style="margin:-12px 0 14px">
                @if ($item->isLost() && $item->user_id === $user->id && ! $user->isAdmin())
                    These items at the Lost and Found office look similar to yours. If one is yours, open it and press CLAIM.
                @else
                    {{ $item->isLost() ? 'Found items' : 'Lost reports' }} with the same category or similar words.
                @endif
            </p>
            <div class="item-grid">
                @foreach ($matches->take(4) as $match)
                    @include('partials.card', ['item' => $match])
                @endforeach
            </div>
        </section>
    @endif
@endsection

@push('scripts')
<script>
    (function () {
        var rel = document.getElementById('release-dialog');
        if (!rel) return;
        document.querySelectorAll('[data-open-release]').forEach(function (b) {
            b.addEventListener('click', function () { rel.showModal(); });
        });
        rel.querySelector('[data-close-release]').addEventListener('click', function () { rel.close(); });
        if (rel.hasAttribute('data-open')) { rel.showModal(); }
    })();
    (function () {
        var dialog = document.getElementById('resolve-dialog');
        if (!dialog) return;
        var list = dialog.querySelector('[data-match-list]');
        function syncList() {
            if (!list) return;
            var matched = dialog.querySelector('input[name="mode"][value="matched"]');
            var on = matched && matched.checked;
            list.classList.toggle('is-disabled', !on);
            list.querySelectorAll('input').forEach(function (i) { i.disabled = !on; });
            var idField = dialog.querySelector('[data-match-id]');
            if (idField) { idField.hidden = !on; idField.querySelector('input').disabled = !on; }
        }
        document.querySelectorAll('[data-open-resolve]').forEach(function (b) {
            b.addEventListener('click', function () { dialog.showModal(); syncList(); });
        });
        dialog.querySelector('[data-close-resolve]').addEventListener('click', function () { dialog.close(); });
        dialog.querySelectorAll('input[name="mode"]').forEach(function (r) { r.addEventListener('change', syncList); });
        if (dialog.hasAttribute('data-open')) { dialog.showModal(); }
        syncList();
    })();
</script>
@endpush
