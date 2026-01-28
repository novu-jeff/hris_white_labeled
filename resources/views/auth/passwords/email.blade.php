@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center my-5">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header pt-4 px-4 bg-transparent border-0">
                    <h6 class="text-uppercase fw-bold mb-0">{{ __('Reset Password') }}</h6>
                    <p class="mt-2 text-muted mb-0">Please provide the email that's associated to your account to perform recovery.</p>
                    <hr>
                </div>
                <div class="card-body px-4">
                    
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="form-group mb-3">
                            <label for="email" class="mb-2">{{ __('Email Address') }}</label>
                            <input id="email" type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   name="email" value="{{ old('email') }}" placeholder="jeff@novulutions.com">
                            <div class="error mt-2">
                                @error('email')
                                    <span class="text-danger" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group mb-0 d-flex gap-3 justify-content-end mt-5 mb-3">
                            <button type="submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                                {{ __('Proceed') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
