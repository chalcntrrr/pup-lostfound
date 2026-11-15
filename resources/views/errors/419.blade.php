@extends(auth()->check() ? 'layouts.app' : 'layouts.auth')

@section('title', 'Page Expired')

@section('content')
    <div class="form-panel" style="max-width:520px;margin-top:40px;text-align:center">
        <h1 class="page-title">PAGE EXPIRED</h1>
        <p>The form was open too long. Go back, refresh the page, and try again.</p>
        <div class="button-row"><a class="btn-primary" href="{{ url()->previous() }}">GO BACK</a></div>
    </div>
@endsection
