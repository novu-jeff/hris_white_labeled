<div class="sidebar">
    <div class="overlay"></div>
    <div class="sidebar-content">
        <div class="close-icon d-lg-none">
            <i class="fa-solid fa-xmark"></i>
        </div>
        <div class="w-100 px-4">
            <a class="navbar-brand text-uppercase" href="{{ url('/') }}">
                <img src="{{ asset('/img/' . $provider['client_logo']) }}">            
            </a>
            <div class="content">
                <ul class="list-unstyled">
                    @if (Auth::guard('applicant')->user() && !Route::is('password.request') && !Route::is('password.reset'))
                        @php
                            $folder = strtolower(Auth::user()->firstname . '_' . Auth::user()->lastname . '_' . Auth::user()->id);
                        @endphp
                        <hr>
                        <div class="dropdown d-flex align-items-center gap-3 pb-0">
                            <img class="profile-img" src="{{
                                Auth::guard('applicant')->user()->image ? Storage::url('public/users/applicant/'. $folder . '/'. Auth::guard('applicant')->user()->image) : 'https://api.dicebear.com/7.x/fun-emoji/svg?seed=10'
                            }}" alt="Profile Image">  
                            <div class="name me-3">
                                <p class="fw-bold text-uppercase text-clamp clamp-1" style="margin-bottom: -4px;">{{ ucwords(Auth::guard('applicant')->user()->firstname . ' ' . Auth::guard('applicant')->user()->lastname ?? 'Guest')}}</p>
                                <small class="text-uppercase text-clamp clamp-1 fw-bold text-muted mb-0">Applicant</small>
                            </div>
                        </div> 
                        <hr>
                    @endif
                    <p class="text-muted fw-bold text-uppercase mt-4 mb-2" style="font-size: 12px; ">Menu</p>
                    @if(!Route::is('password.request') && !Route::is('password.reset'))
                        <li class="list-unstyled-item">
                            <a href="{{route('home.index')}}" class="nav-link 
                            {{ request()->routeIs('home.index') || 
                                request()->routeIs('home.search.*') ||
                                request()->routeIs('home.view-job') ? 'active' : '' 
                            }}">
                                Find Jobs
                            </a>
                        </li>
                        <li class="list-unstyled-item">
                            <a href="{{route('home.applied')}}" class="nav-link {{request()->routeIs('home.applied') || request()->routeIs('home.applied.*') == 'home.applied' ? 'active' : ''}}">
                                My Jobs
                            </a>
                        </li>
                        @if (!Auth::guard('applicant')->user())
                            <li class="list-unstyled-item">
                                <a href="{{route('home.login')}}" class="btn btn-primary py-2 px-4">
                                    Login
                                </a>
                            </li>
                        @endif
                    @else
                        <li class="list-unstyled-item">
                            <a href="{{route('employee.login')}}" class="btn btn-primary py-2 px-4">
                                Login
                            </a>
                        </li>
                    @endif
                    @if (Auth::guard('applicant')->user())
                        <li class="list-unstyled-item">
                            <a href="{{route('home.profile')}}" class="btn btn-primary py-2 px-4">
                                Profile
                            </a>
                        </li>
                        <li class="list-unstyled-item">
                            <a href="{{route('home.logout')}}" class="btn btn-primary py-2 px-4">
                                Logout
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
        <div class="footer">
            &copy; {{ now()->format('Y') }} Novulutions Inc. All Rights Reserved.
        </div>
    </div>
</div>