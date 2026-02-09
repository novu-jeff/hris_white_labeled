@extends('layouts.auth', [
    'title' => 'HRIS | Employee Login'
])

@section('content')
    <div class="login">
        <div class="row d-flex justify-content-center align-items-center h-100">
            <div class="col-12 col-md-10 col-lg-5">
                <div class="container">
                    <form method="POST" action="{{route('employee.login')}}">
                        @method('POST')
                        @csrf
                        <div class="card shadow p-3">
                            <div class="card-header bg-transparent py-2 border-0">
                                <div class="d-lg-flex justify-content-between align-items-center">  
                                    <div class="logo" >
                                        <img src="{{ asset('/img/' . $provider['client_logo'])}}" style="position: relative; {{config('app.product') == 'government' ? 'left: -20px' : ''}}">
                                    </div>
                                    <ul class="nav nav-pills" id="pills-tab" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button type="button" class="nav-link active" id="pills-login-tab" data-bs-toggle="pill" data-bs-target="#pills-login" type="button" role="tab" aria-controls="pills-login" aria-selected="true">Employee Login</button>
                                        </li>
                                    </ul>   
                                </div>                 
                                <div class="note mt-4 mb-3">
                                    By signing in, you agree to the {{$provider['company']}} HRIS Terms of Service and acknowledge our Cookie and Privacy Policies. This system is designed to help you securely access and manage your employment records, including personal details, job information, payroll, and other HR services through employee self-service features, in accordance with company policies and applicable regulations.
                                </div>                
                            </div>
                            <hr class="my-2 mx-3">
                            <div class="card-body">
                                <div class="row">
                                    @if (session()->has('error'))
                                        <div class="alert alert-danger mb-4 text-uppercase fw-bold text-center" style="font-size:12px;">
                                            {!! session('error') !!}
                                        </div>
                                    @endif
                                    <div class="col-12 mb-3">
                                        <label for="email" class="mb-2">Login <span class="text-danger">*</span></label>
                                        <input type="text" name="email" id="email" class="form-control" value="{{old('email')}}" placeholder="Email, E-ID or E-No." autocomplete="username">
                                        <div class="error-field mt-1">
                                            @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label for="password" class="mb-2">Password <span class="text-danger">*</span></label>
                                        <input type="password" name="password" id="password" class="form-control" placeholder="Enter Password" autocomplete="off">
                                        <div class="error-field mt-1">
                                            @error('password') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="text-uppercase fw-bold" style="font-size: 12px; letter-spacing: 1px;">
                                    Forgot Password? Click <a href="{{route('password.request')}}">Here</a>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0 d-flex gap-2 justify-content-end pb-3">
                                <button class="btn btn-primary px-5 py-3 text-uppercase fw-bold">Proceed</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection