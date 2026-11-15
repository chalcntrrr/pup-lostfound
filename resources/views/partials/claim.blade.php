{{-- One claim as a history card. Needs: $claim, $showItem (bool). Admins approve/reject pending claims here. --}}
@php
    $viewer = auth()->user();
    $hasActions = ($viewer->isAdmin() || $viewer->id === $claim->user_id) && $claim->isPending();
    $canSeeSlip = $claim->isApproved() && ($viewer->isAdmin() || $viewer->id === $claim->user_id);
    $thisForm = old('claim_id') == $claim->id; // show validation errors only on the card that was submitted
@endphp
<article class="history-card claim-card {{ ($hasActions || $canSeeSlip) ? '' : 'no-actions' }}">
    <div>
        <div class="history-head">
            <span class="history-log-id">CLAIM #{{ $claim->id }}</span>
            <span class="badge badge-{{ $claim->status }}">{{ strtoupper($claim->status) }}</span>
            <span class="history-timestamp">{{ $claim->created_at->format('Y-m-d g:i A') }}</span>
        </div>

        <div class="history-card-title">
            @if ($showItem)
                <a href="{{ route('items.show', $claim->item) }}">{{ $claim->item->name }}</a>
                <span class="history-card-sub">· {{ $claim->item->category->name ?? '' }}</span>
            @else
                {{ $claim->claimantName() }}
            @endif
        </div>

        <div class="history-card-sub">
            @if ($showItem) Claimant: {{ $claim->claimantName() }}{{ $claim->user?->isFaculty() ? ' (Faculty)' : '' }}{{ $claim->isWalkIn() ? ' (Walk-in)' : '' }} · @endif
            {{ $claim->claimantIdNumber() ?? 'No ID number' }}
            @if ($claim->claimantSection()) · {{ $claim->claimantSection() }} @endif
            @if ($claim->contact_number) · {{ $claim->contact_number }} @endif
            @if ($claim->date_lost) · Date lost: {{ $claim->date_lost->format('Y-m-d') }} @endif
        </div>

        <div class="claim-proof">{{ $claim->proof_details }}</div>

        @if ($claim->proof_image_path)
            <div class="claim-links">
                <a href="{{ route('claims.proof', $claim) }}" target="_blank" rel="noopener">View proof of claim</a>
            </div>
        @endif

        @unless ($claim->isPending())
            <div class="claim-note">
                {{ ucfirst($claim->status) }}{{ $claim->reviewer ? ' by ' . $claim->reviewer->name : '' }}{{ $claim->reviewed_at ? ' on ' . $claim->reviewed_at->format('Y-m-d g:i A') : '' }}.
                @if ($claim->id_presented) ID presented: {{ $claim->id_presented }}. @endif
                @if ($claim->admin_notes) Note: {{ $claim->admin_notes }} @endif
            </div>
        @endunless
    </div>

    @if ($hasActions && $viewer->isAdmin())
        <div class="claim-actions">
            <form method="POST" action="{{ route('admin.claims.approve', $claim) }}">
                @csrf
                <input type="hidden" name="claim_id" value="{{ $claim->id }}">
                <div class="field-hint" style="margin:0">Check the ID against: <strong>{{ $claim->claimantName() }}</strong>{{ $claim->claimantIdNumber() ? ' · ' . $claim->claimantIdNumber() : '' }}</div>
                <input class="form-input {{ $thisForm && $errors->has('id_presented') ? 'is-invalid' : '' }}" name="id_presented"
                       value="{{ $thisForm ? old('id_presented') : '' }}" placeholder="ID presented (e.g. School ID)" aria-label="ID presented for claim {{ $claim->id }}">
                @if ($thisForm && $errors->has('id_presented'))
                    <div class="error-label" style="margin-top:-4px">{{ $errors->first('id_presented') }}</div>
                @endif
                <textarea class="form-input" name="admin_notes" placeholder="Note to the claimant (optional)" aria-label="Note for claim {{ $claim->id }}">{{ $thisForm ? old('admin_notes') : '' }}</textarea>
                <button class="btn-primary btn-green btn-sm" type="submit">APPROVE CLAIM</button>
                <button class="btn-danger btn-sm" type="submit" formaction="{{ route('admin.claims.reject', $claim) }}">REJECT CLAIM</button>
            </form>
        </div>
    @elseif ($hasActions)
        <div class="claim-actions">
            <form method="POST" action="{{ route('claims.destroy', $claim) }}" onsubmit="return confirm('Cancel this claim?')">
                @csrf
                @method('DELETE')
                <button class="btn-danger btn-sm" type="submit">CANCEL CLAIM</button>
            </form>
        </div>
    @elseif ($canSeeSlip)
        <div class="claim-actions">
            <a class="btn-primary btn-sm" href="{{ route('claims.slip', $claim) }}">VIEW CLAIM SLIP</a>
            @if ($viewer->id === $claim->user_id)
                <div class="pickup-note">Keep your claim slip as proof that you received the item.</div>
            @endif
        </div>
    @endif
</article>
