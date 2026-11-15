@extends(auth()->check() ? 'layouts.app' : 'layouts.auth')

@section('title', 'Not Allowed')

@section('content')
    <div class="form-panel" style="max-width:520px;margin-top:40px;text-align:center">
        <h1 class="page-title">NOT ALLOWED</h1>
        <p>{{ $exception->getMessage() ?: 'Your account does not have permission to do this.' }}</p>
        <div class="button-row"><a class="btn-primary" href="{{ route('dashboard') }}">BACK TO DASHBOARD</a></div>
    </div>
@endsection
