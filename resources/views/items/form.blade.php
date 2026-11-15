@extends('layouts.app')

{{-- LOST ITEM REPORT / FOUND ITEM REPORT, same layout as the original ReportForm.fxml.
     Used for both "report" (new) and "edit". --}}
@php
    $user = auth()->user();
    $editing = $item->exists;
    $type = old('type', $item->type);
    $isLost = $type === 'lost';
    $Word = $isLost ? 'Lost' : 'Found';
    $reporter = $editing ? $item->user : $user;
    // An admin logging a LOST item is doing it for a walk-in owner (someone without an account).
    $walkIn = $isLost && ($editing ? $item->isWalkIn() : $user->isAdmin());
@endphp

@section('title', ($editing ? 'Edit ' : '') . ($walkIn ? 'Walk-in ' : '') . $Word . ' Item Report')

@section('content')
    <form class="form-panel" method="POST" enctype="multipart/form-data" novalidate
          action="{{ $editing ? route('items.update', $item) : route('items.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif
        <input type="hidden" name="type" value="{{ $type }}">

        <h1 class="page-title">{{ $editing ? 'EDIT ' : '' }}{{ strtoupper($Word) }} ITEM REPORT</h1>
        @if ($walkIn)
            <p class="page-subtitle">Walk-in report: for an owner who has no account. Type their details below; they are visible to admins only.</p>
        @endif

        @error('status') <div class="flash flash-error">{{ $message }}</div> @enderror

        <div class="form-columns">
            {{-- Left: item details --}}
            <div>
                <h2 class="section-heading">{{ $Word }} Item Details</h2>

                <div class="field">
                    <label class="form-label" for="name">{{ $Word }} Item Name <span class="required-star">*</span></label>
                    <input class="form-input @error('name') is-invalid @enderror" id="name" name="name" maxlength="100"
                           value="{{ old('name', $item->name) }}" placeholder="Name of Item">
                    @error('name') <div class="error-label">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label class="form-label" for="category_id">Category <span class="required-star">*</span></label>
                    <select class="form-input @error('category_id') is-invalid @enderror" id="category_id" name="category_id">
                        <option value=""></option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $item->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <div class="error-label">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label class="form-label" for="description">Description <span class="required-star">*</span></label>
                    <textarea class="form-input @error('description') is-invalid @enderror" id="description" name="description" maxlength="1000"
                              placeholder="Describe the {{ $isLost ? 'missing' : 'found' }} item (e.g., color, brand, unique marks)">{{ old('description', $item->description) }}</textarea>
                    @error('description') <div class="error-label">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label class="upload-btn @error('image') is-invalid @enderror">
                        <span data-file-label>{{ $item->image_path ? 'Change Image' : 'Upload Images' }}</span>
                        <input type="file" name="image" accept="image/*" data-file-input>
                    </label>
                    @error('image') <div class="error-label">{{ $message }}</div> @enderror
                    @if ($item->image_path)
                        <label class="form-check"><input type="checkbox" name="remove_image" value="1"> Remove current image</label>
                    @endif
                </div>
            </div>

            {{-- Right: reporter + where/when --}}
            <div>
                <h2 class="section-heading">{{ $walkIn ? 'Owner Information' : ($isLost ? 'Reporter Information' : 'Surrender Information') }}</h2>

                @if ($walkIn)
                    <div class="field">
                        <label class="form-label" for="owner_name">Owner's Name <span class="required-star">*</span></label>
                        <input class="form-input @error('owner_name') is-invalid @enderror" id="owner_name" name="owner_name" maxlength="100"
                               value="{{ old('owner_name', $item->owner_name) }}" placeholder="Surname, First Name">
                        @error('owner_name') <div class="error-label">{{ $message }}</div> @enderror
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label class="form-label" for="owner_id_number">Student / Employee ID</label>
                            <input class="form-input @error('owner_id_number') is-invalid @enderror" id="owner_id_number" name="owner_id_number" maxlength="30"
                                   value="{{ old('owner_id_number', $item->owner_id_number) }}" placeholder="If any">
                            @error('owner_id_number') <div class="error-label">{{ $message }}</div> @enderror
                        </div>
                        <div class="field">
                            <label class="form-label" for="owner_contact">Contact Number <span class="required-star">*</span></label>
                            <input class="form-input @error('owner_contact') is-invalid @enderror" id="owner_contact" name="owner_contact"
                                   value="{{ old('owner_contact', $item->owner_contact) }}" placeholder="09XX-XXX-XXXX">
                            @error('owner_contact') <div class="error-label">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="account-summary">
                        <strong>Logged by {{ $editing ? $item->user->name : $user->name }}</strong>
                        <span>Walk-in report: the owner has no account.</span>
                    </div>
                @elseif ($isLost)
                    <div class="account-summary">
                        <strong>{{ $reporter->name }}</strong>
                        <span>{{ $reporter->idNumber() ?? ($reporter->isAdmin() ? 'Admin' : '') }}</span>
                        <span>{{ $reporter->email }}</span>
                    </div>
                @else
                    <div class="account-summary">
                        <strong>Logged by {{ $reporter->name }}</strong>
                        <span>The item is kept at the Lost and Found office.</span>
                    </div>
                @endif

                @if ($isLost && ! $walkIn)
                    <div class="field">
                        <label class="form-label" for="contact_number">Contact Number <span class="required-star">*</span></label>
                        <input class="form-input @error('contact_number') is-invalid @enderror" id="contact_number" name="contact_number"
                               value="{{ old('contact_number', $reporter->contact_number) }}" placeholder="09XX-XXX-XXXX"
                               @if($editing && $reporter->id !== $user->id) readonly @endif>
                        @error('contact_number') <div class="error-label">{{ $message }}</div> @enderror
                    </div>
                @endif

                <div class="field">
                    <label class="form-label" for="location">Location {{ $Word }} <span class="required-star">*</span></label>
                    <input class="form-input @error('location') is-invalid @enderror" id="location" name="location" list="locations" maxlength="100"
                           value="{{ old('location', $item->location) }}">
                    <datalist id="locations">
                        @foreach (\App\Models\Item::LOCATIONS as $place)
                            <option value="{{ $place }}">
                        @endforeach
                    </datalist>
                    @error('location') <div class="error-label">{{ $message }}</div> @enderror
                </div>

                <div class="field-row">
                    <div class="field">
                        <label class="form-label" for="date_happened">Date {{ $Word }} <span class="required-star">*</span></label>
                        <input class="form-input @error('date_happened') is-invalid @enderror" id="date_happened" type="date" name="date_happened"
                               max="{{ now()->toDateString() }}" value="{{ old('date_happened', optional($item->date_happened)->toDateString()) }}">
                        @error('date_happened') <div class="error-label">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label class="form-label" for="time_happened">Time {{ $Word }}</label>
                        <input class="form-input @error('time_happened') is-invalid @enderror" id="time_happened" type="time" name="time_happened"
                               value="{{ old('time_happened', $item->time_happened ? substr($item->time_happened, 0, 5) : '') }}">
                        @error('time_happened') <div class="error-label">{{ $message }}</div> @enderror
                    </div>
                </div>

                @unless ($isLost)
                    @php($anon = (bool) old('is_anonymous', $item->exists ? $item->is_anonymous : false))
                    <p class="privacy-text">Privacy Disclaimer: Providing the finder's details is completely voluntary. If shared, they are strictly confidential, accessible only to the Admin for verification, and never shown publicly on the dashboard.</p>
                    <label class="form-check"><input type="radio" name="is_anonymous" value="0" data-finder-toggle @checked(! $anon)> Record Finder Details</label>
                    <label class="form-check"><input type="radio" name="is_anonymous" value="1" data-finder-toggle @checked($anon)> Keep Anonymous</label>

                    <div class="finder-fields" data-finder-fields @if($anon) hidden @endif>
                        <div class="field">
                            <label class="form-label" for="finder_name">Finder's Name <span class="required-star">*</span></label>
                            <input class="form-input @error('finder_name') is-invalid @enderror" id="finder_name" name="finder_name"
                                   value="{{ old('finder_name', $item->finder_name) }}" placeholder="Surname, First Name">
                            @error('finder_name') <div class="error-label">{{ $message }}</div> @enderror
                        </div>
                        <div class="field-row">
                            <div class="field">
                                <label class="form-label" for="finder_student_id">Student / Employee ID (If any)</label>
                                <input class="form-input @error('finder_student_id') is-invalid @enderror" id="finder_student_id" name="finder_student_id"
                                       value="{{ old('finder_student_id', $item->finder_student_id) }}" placeholder="e.g. YYYY-00000-SR-0">
                                @error('finder_student_id') <div class="error-label">{{ $message }}</div> @enderror
                            </div>
                            <div class="field">
                                <label class="form-label" for="finder_contact">Contact Number / Email</label>
                                <input class="form-input @error('finder_contact') is-invalid @enderror" id="finder_contact" name="finder_contact"
                                       value="{{ old('finder_contact', $item->finder_contact) }}" placeholder="09XX-XXX-XXXX">
                                @error('finder_contact') <div class="error-label">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                @endunless

            </div>
        </div>

        <div class="button-row">
            <a class="btn-cancel" href="{{ $editing ? route('items.show', $item) : route('items.index') }}">CANCEL</a>
            <button class="btn-primary" type="submit">SAVE REPORT</button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    // Finder fields only when "Record Finder Details" is chosen.
    document.querySelectorAll('[data-finder-toggle]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.querySelector('[data-finder-fields]').hidden = this.value === '1';
        });
    });

    // Show the chosen file name on the "Upload Images" button.
    document.querySelectorAll('[data-file-input]').forEach(function (input) {
        input.addEventListener('change', function () {
            var label = input.parentElement.querySelector('[data-file-label]');
            if (input.files.length) label.textContent = input.files[0].name;
        });
    });
</script>
@endpush
