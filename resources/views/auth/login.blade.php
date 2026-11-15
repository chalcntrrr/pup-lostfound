@extends('layouts.auth')

@section('title', 'Login')

@section('content')
    {{-- Same layout as the original AdminLogin.fxml, now used by students, faculty and admins --}}
    <div class="login-split login">
        <section class="login-maroon">
            <img class="login-logo-md" src="{{ asset('images/logo.png') }}" alt="">
            <h1 class="login-welcome" style="line-height:1.25">PUPSRC LOST<br>AND FOUND</h1>
            <p class="login-welcome-subtitle" style="margin-top:14px">Log in to report lost items, claim found items, and track your claims.</p>
        </section>

        <section class="login-white">
            <div class="admin-login-card">
                @include('partials.flash')

                <h2 class="admin-login-title">Login</h2>
                <p class="admin-login-subtitle">Enter your credentials to continue.</p>

                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf
                    <div class="field">
                        <label class="form-label" for="login">Email, Student ID or Employee ID</label>
                        <input class="form-input @error('login') is-invalid @enderror" id="login" name="login"
                               value="{{ old('login') }}" placeholder="Email, YYYY-00000-SR-0 or Employee ID" autocomplete="username" autofocus>
                    </div>
                    <div class="field">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-input" id="password" type="password" name="password" placeholder="Password" autocomplete="current-password">
                    </div>
                    <label class="form-check"><input type="checkbox" name="remember" value="1"> Keep me logged in</label>

                    @if ($errors->any())
                        <div class="error-label">{{ $errors->first() }}</div>
                    @endif

                    <button class="btn-primary" type="submit">LOG IN</button>
                    <a class="btn-cancel" href="{{ route('home') }}">BACK</a>
                </form>

                <p class="forgot-text">Don't have an account? <a href="{{ route('register') }}">Register here</a></p>

            </div>
        </section>
    </div>
@endsection
