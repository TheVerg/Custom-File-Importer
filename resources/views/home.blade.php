@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{ __('You are logged in!') }}
                <h3>Welcome to the Home Page</h3>
                <p>This is your dashboard where you can manage your account and view recent activity.</p>
                </div>
            </div>

            <div class="mt-4">
                <h3>Proceed to Import Data</h3>
                <p>Click the button below to navigate to the data import section.</p>
                <a href="{{ route('import.index') }}" class="btn btn-primary">Go to Import</a>
        </div>
    </div>
</div>
@endsection
