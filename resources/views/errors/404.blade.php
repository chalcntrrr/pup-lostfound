@extends(auth()->check() ? 'layouts.app' : 'layouts.auth')

@section('title', 'Page Not Found')

@section('content')
    <div class="form-panel" style="max-width:520px;margin-top:40px;text-align:center">
        <h1 class="page-title">PAGE NOT FOUND</h1>
        <p>The report may have been deleted, or the link is wrong.</p>
        <div class="button-row"><a class="btn-primary" href="{{ url('/') }}">BACK TO DASHBOARD</a></div>
    </div>
@endsection
