@extends('layouts.app')

{{-- CLAIM VERIFICATION, layout from the original ClaimVerification.fxml.
     Name/email/ID number come from the account, so they're shown as text instead of form fields.
     The ID is checked by the admin at the office (entered when approving). --}}
@php($user = auth()->user())

@section('title', 'Claim Verification')

@section('content')
    <form class="form-panel" method="POST" action="{{ route('claims.store', $item) }}" enctype="multipart/form-data" novalidate>
        @csrf

        <h1 class="page-title">CLAIM VERIFICATION</h1>
        <p class="page-subtitle">{{ $item->name }}, found at {{ $item->location }}</p>

        <div class="form-columns even">
            <div>
                <h2 class="section-heading">Claimant Information</h2>

                <div class="account-summary">
                    <strong>{{ $user->name }}</strong>
                    <span>{{ $user->idNumber() }}{{ $user->course_section ? ' · ' . $user->course_section : '' }}</span>
                    <span>{{ $user->email }}</span>
                </div>

                <div class="field">
                    <label class="form-label" for="contact_number">Contact Number <span class="required-star">*</span></label>
                    <input class="form-input @error('contact_number') is-invalid @enderror" id="contact_number" name="contact_number"
                           value="{{ old('contact_number', $user->contact_number) }}" placeholder="09XX-XXX-XXXX">
                    <div class="field-hint">The admin will contact you about your claim.</div>
                    @error('contact_number') <div class="error-label">{{ $message }}</div> @enderror
                </div>

                <div class="note-box">
                    <strong>Bring a valid ID.</strong> If your claim is approved, show your ID at the Lost and Found office to receive the item and your claim slip.
                </div>
            </div>

            <div>
                <h2 class="section-heading">Item Verification</h2>

                <div class="field-row">
                    <div class="field">
                        <label class="form-label" for="date_lost">Date Lost <span class="required-star">*</span></label>
                        <input class="form-input @error('date_lost') is-invalid @enderror" id="date_lost" type="date" name="date_lost"
                               max="{{ now()->toDateString() }}" value="{{ old('date_lost') }}">
                        @error('date_lost') <div class="error-label">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label class="form-label">Date Found</label>
                        <input class="form-input detail-readonly" value="{{ $item->date_happened->format('Y-m-d') }}" readonly>
                    </div>
                </div>

                <div class="field">
                    <label class="form-label" for="proof_details">Description <span class="required-star">*</span></label>
                    <textarea class="form-input @error('proof_details') is-invalid @enderror" id="proof_details" name="proof_details" maxlength="1000"
                              placeholder="Describe your item so the admin can verify it's yours (brand, color, marks, contents)">{{ old('proof_details') }}</textarea>
                    @error('proof_details') <div class="error-label">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <span class="form-label">Proof of Claim <span class="required-star">*</span></span>
                    <label class="upload-btn @error('proof_image') is-invalid @enderror">
                        <span data-file-label>Upload Images</span>
                        <input type="file" name="proof_image" accept="image/*" data-file-input>
                    </label>
                    <div class="field-hint">A photo of you with the item, a receipt, etc. Only you and the Admin can see it.</div>
                    @error('proof_image') <div class="error-label">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="button-row">
            <a class="btn-cancel" href="{{ route('items.show', $item) }}">CANCEL</a>
            <button class="btn-primary" type="submit">CONFIRM CLAIM</button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-file-input]').forEach(function (input) {
        input.addEventListener('change', function () {
            var label = input.parentElement.querySelector('[data-file-label]');
            if (input.files.length) label.textContent = input.files[0].name;
        });
    });
</script>
@endpush
