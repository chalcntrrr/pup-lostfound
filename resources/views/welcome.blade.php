@extends('layouts.auth')

@section('title', 'Welcome')

@section('content')
    {{-- Same as the original Login.fxml welcome screen --}}
    <div class="login-split welcome">
        <section class="login-maroon">
            <h1 class="login-welcome">WELCOME TO<br>PUPSRC LOST AND FOUND</h1>
            <p class="login-welcome-subtitle">Lost and Found office system. Authorized staff only.</p>
        </section>

        <section class="login-white">
            <img class="login-logo-lg" src="{{ asset('images/logo.png') }}" alt="Polytechnic University of the Philippines seal">
            <div style="height:40px"></div>
            <a class="login-role-btn" href="{{ route('login') }}">ADMIN LOGIN</a>
        </section>
    </div>
@endsection
