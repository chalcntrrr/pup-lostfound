@extends('layouts.auth')

@section('title', 'Create Account')

@section('content')
    {{-- Same layout as the original CreateAdminAccount.fxml, used for student and faculty sign-up. Admins are added by a Super Admin. --}}
    <div class="login-split register">
        <section class="login-white">
            <div class="admin-login-card wide">
                <h2 class="admin-login-title">Create Account</h2>
                <p class="admin-login-subtitle">For PUP students and faculty. Report lost items and claim items surrendered to the office.</p>

                <form method="POST" action="{{ route('register') }}" novalidate>
                    @csrf
                    @php($type = old('account_type', 'student'))

                    <div class="field">
                        <span class="form-label">I am a <span class="required-star">*</span></span>
                        <div class="role-pick">
                            <label><input type="radio" name="account_type" value="student" data-account-type @checked($type === 'student')> Student</label>
                            <label><input type="radio" name="account_type" value="faculty" data-account-type @checked($type === 'faculty')> Faculty / Professor</label>
                        </div>
                        @error('account_type') <div class="error-label">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label class="form-label" for="name">Name <span class="required-star">*</span></label>
                        <input class="form-input @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="Surname, First Name" autocomplete="name">
                        @error('name') <div class="error-label">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label class="form-label" for="email">Email Address <span class="required-star">*</span></label>
                        <input class="form-input @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email">
                        @error('email') <div class="error-label">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <div class="field-row">
                            <div class="field" data-for="student" @if($type === 'faculty') hidden @endif>
                                <label class="form-label" for="student_number">Student ID <span class="required-star">*</span></label>
                                <input class="form-input @error('student_number') is-invalid @enderror" id="student_number" name="student_number" value="{{ old('student_number') }}" placeholder="YYYY-00000-SR-0" @if($type === 'faculty') disabled @endif>
                                @error('student_number') <div class="error-label">{{ $message }}</div> @enderror
                            </div>
                            <div class="field" data-for="faculty" @if($type !== 'faculty') hidden @endif>
                                <label class="form-label" for="employee_number">Employee ID (optional)</label>
                                <input class="form-input @error('employee_number') is-invalid @enderror" id="employee_number" name="employee_number" value="{{ old('employee_number') }}" placeholder="Your PUP employee ID" @if($type !== 'faculty') disabled @endif>
                                @error('employee_number') <div class="error-label">{{ $message }}</div> @enderror
                            </div>
                            <div class="field">
                                <label class="form-label" for="course_section" data-section-label>{{ $type === 'faculty' ? 'Department / College' : 'Course and Section' }}</label>
                                <input class="form-input @error('course_section') is-invalid @enderror" id="course_section" name="course_section" value="{{ old('course_section') }}" placeholder="{{ $type === 'faculty' ? 'e.g. College of Computer Studies' : 'BSIT 2-1' }}">
                                @error('course_section') <div class="error-label">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="field">
                        <label class="form-label" for="contact_number">Contact Number</label>
                        <input class="form-input @error('contact_number') is-invalid @enderror" id="contact_number" name="contact_number" value="{{ old('contact_number') }}" placeholder="09XX-XXX-XXXX" autocomplete="tel">
                        @error('contact_number') <div class="error-label">{{ $message }}</div> @enderror
                    </div>

                    <div class="field-row">
                        <div class="field">
                            <label class="form-label" for="password">Password <span class="required-star">*</span></label>
                            <input class="form-input @error('password') is-invalid @enderror" id="password" type="password" name="password" placeholder="Enter password" autocomplete="new-password">
                            @error('password') <div class="error-label">{{ $message }}</div> @enderror
                        </div>
                        <div class="field">
                            <label class="form-label" for="password_confirmation">Confirm Password <span class="required-star">*</span></label>
                            <input class="form-input" id="password_confirmation" type="password" name="password_confirmation" placeholder="Re-enter password" autocomplete="new-password">
                        </div>
                    </div>

                    <button class="btn-primary" type="submit">CREATE ACCOUNT</button>
                    <a class="btn-cancel" href="{{ route('home') }}">HOME</a>
                </form>

                <p class="forgot-text">Already have an account? <a href="{{ route('login') }}">Log in here</a></p>
            </div>
        </section>

        <section class="login-maroon">
            <img class="login-logo-sm" src="{{ asset('images/logo.png') }}" alt="">
            <h1 class="login-welcome" style="line-height:1.25">PUPSRC LOST<br>AND FOUND</h1>
        </section>
    </div>
@endsection

@push('scripts')
<script>
    // Student sign-up shows Student ID + Course and Section; faculty sign-up shows Employee ID + Department.
    (function () {
        var radios = document.querySelectorAll('[data-account-type]');
        var section = document.querySelector('[data-section-label]');
        var sectionInput = document.getElementById('course_section');
        function sync() {
            var faculty = document.querySelector('[data-account-type]:checked').value === 'faculty';
            document.querySelectorAll('[data-for]').forEach(function (box) {
                var on = box.getAttribute('data-for') === (faculty ? 'faculty' : 'student');
                box.hidden = !on;
                box.querySelector('input').disabled = !on;
            });
            section.textContent = faculty ? 'Department / College' : 'Course and Section';
            sectionInput.placeholder = faculty ? 'e.g. College of Computer Studies' : 'BSIT 2-1';
        }
        radios.forEach(function (r) { r.addEventListener('change', sync); });
    })();
</script>
@endpush
