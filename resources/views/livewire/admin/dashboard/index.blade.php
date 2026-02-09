<div class="main-content flex-grow-1 p-4" >
        
        <style>
            .dashboard-card-hover {
                border-radius: 0.85rem;
                border: 1px solid rgba(226, 232, 240, 0.9);
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
                transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            }

            .dashboard-card-hover:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
                border-color: rgba(148, 163, 184, 0.6);
            }
        </style>
   
        
        <div class="d-lg-flex justify-content-between align-items-center">
            <div class="section-title">
                <h1>Dashboard</h1>
            </div>
        </div>
        @php
            $adminUser = auth()->user();
            $isSupervisor = $adminUser && method_exists($adminUser, 'hasRole') && $adminUser->hasRole('supervisor');
        @endphp

        @if($isSupervisor)
            <div class="row mt-5">
                <div class="col-12 col-lg-7 mb-3">
                    <div class="card dashboard-card-hover">
                        <div class="card-header bg-primary text-white px-4">
                            <h5 class="my-2 text-uppercase fw-bold">My Team Applications (Pending)</h5>
                        </div>
                        <div class="card-body px-4">
                            <div class="row gy-3">
                                <div class="col-12 col-md-6">
                                    <a href="{{ route('ess.leave', ['status' => 'pending']) }}" class="text-decoration-none">
                                        <div class="alert alert-secondary text-uppercase fw-bold mb-0">
                                            Leave Applications: {{ $stats['leave']['pending'] }}
                                        </div>
                                    </a>
                                </div>
                                <div class="col-12 col-md-6">
                                    <a href="{{ route('ess.obs', ['status' => 'pending']) }}" class="text-decoration-none">
                                        <div class="alert alert-secondary text-uppercase fw-bold mb-0">
                                            Official Business: {{ $stats['obs']['pending'] }}
                                        </div>
                                    </a>
                                </div>
                                <div class="col-12 col-md-6">
                                    <a href="{{ route('ess.atro', ['status' => 'pending']) }}" class="text-decoration-none">
                                        <div class="alert alert-secondary text-uppercase fw-bold mb-0">
                                            Overtime Application: {{ $stats['atro']['pending'] }}
                                        </div>
                                    </a>
                                </div>
                                <div class="col-12 col-md-6">
                                    <a href="{{ route('ess.offset', ['status' => 'pending']) }}" class="text-decoration-none">
                                        <div class="alert alert-secondary text-uppercase fw-bold mb-0">
                                            Offset Applications: {{ $stats['offset']['pending'] }}
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-5 mb-3">
                    <div class="card dashboard-card-hover">
                        <div class="card-header bg-primary text-white px-4">
                            <h5 class="my-2 text-uppercase fw-bold">Quick Actions</h5>
                        </div>
                        <div class="card-body px-4">
                            <div class="d-grid gap-2">
                                <a href="{{ route('ess.leave', ['status' => 'pending']) }}" class="btn btn-outline-primary text-uppercase fw-bold">
                                    Review Leave Applications
                                </a>
                                <a href="{{ route('ess.obs', ['status' => 'pending']) }}" class="btn btn-outline-primary text-uppercase fw-bold">
                                    Review Official Business
                                </a>
                                <a href="{{ route('ess.atro', ['status' => 'pending']) }}" class="btn btn-outline-primary text-uppercase fw-bold">
                                    Review Overtime Application
                                </a>
                                <a href="{{ route('ess.offset', ['status' => 'pending']) }}" class="btn btn-outline-primary text-uppercase fw-bold">
                                    Review Offset Applications
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
        <div class="row mt-5">
            <div class="row">
                <div class="col-12 col-md-7">
                    <div class="col-12 mb-3">
                        <div class="card dashboard-card-hover">
                            <div class="card-header bg-primary text-white px-4">
                                <h5 class="my-2 text-uppercase fw-bold">Employees</h5>
                            </div>
                            <div class="card-body px-3">
                                <div class="swiper-container">
                                    <div class="swiper-wrapper">
                                        @forelse($stats['employee'] as $types)
                                            <div class="swiper-slide text-uppercase bg-info p-3 rounded-3 text-white">
                                                <p class="mb-0 fw-bold">{{$types['employment_type']}}</p>
                                                <hr>
                                                <h1>{{$types['employee_count']}}</h1>
                                            </div>
                                        @empty
                                            <div class="w-100 text-uppercase bg-info p-3 rounded-3 text-white">
                                                No employment types to show
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                                @if(count($stats['employee']) > 3) 
                                    <div class="float-end">
                                        <small class="text-uppercase text-muted fw-bold d-flex gap-2 align-items-center">
                                            <i class="fa-solid fa-arrow-right-arrow-left"></i>
                                            Swipe left or right to view more
                                        </small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mb-3">
                        <div class="card dashboard-card-hover">
                            <div class="card-header bg-primary text-white px-4 d-flex justify-content-between">
                                <h5 class="my-2 text-uppercase fw-bold">Clock In & Out</h5>
                                <h5 class="my-2 text-uppercase fw-bold">{{ \Carbon\Carbon::now()->format('F d, Y') }}</h5>
                            </div>
                            <div class="card-body px-3 d-flex">
                                <div class="d-lg-flex gap-3 w-100">
                                    <div class="mb-3 w-100 text-uppercase bg-info p-3 rounded-3 text-white">
                                        <p class="mb-0 fw-bold">Clocked In</p>
                                        <hr>
                                        <h1>{{$stats['clockinout']['clockin']}}</h1>
                                    </div>
                                    <div class="mb-3 w-100 text-uppercase bg-info p-3 rounded-3 text-white">
                                        <p class="mb-0 fw-bold">In Progress</p>
                                        <hr>
                                        <h1>{{$stats['clockinout']['inprogress']}}</h1>
                                    </div>
                                    <div class="mb-3 w-100 text-uppercase bg-info p-3 rounded-3 text-white">
                                        <p class="mb-0 fw-bold">Clocked Out</p>
                                        <hr>
                                        <h1>{{$stats['clockinout']['clockout']}}</h1>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mb-3">
                        <div class="card dashboard-card-hover">
                            <div class="card-header bg-primary text-white px-4">
                                <h5 class="my-2 text-uppercase fw-bold">Leave Applications</h5>
                            </div>
                            <div class="card-body px-3 d-flex">
                                <div class="d-lg-flex gap-3 w-100">
                                    <div class="mb-3 w-100 text-uppercase bg-secondary p-3 rounded-3 text-white">
                                        <p class="mb-0 fw-bold">Pending</p>
                                        <hr>
                                        <h1>{{$stats['leave']['pending']}}</h1>
                                        <div class="float-end">
                                            <a href="{{route('ess.leave', ['status' => 'pending'])}}" class="text-white">View</a>
                                        </div>
                                    </div>
                                    <div class="mb-3 w-100 text-uppercase bg-success p-3 rounded-3 text-white">
                                        <p class="mb-0 fw-bold">Granted</p>
                                        <hr>
                                        <h1>{{$stats['leave']['granted']}}</h1>
                                        <div class="float-end">
                                            <a href="{{route('ess.leave', ['status' => 'granted'])}}" class="text-white">View</a>
                                        </div>
                                    </div>
                                    <div class="mb-3 w-100 text-uppercase bg-danger p-3 rounded-3 text-white">
                                        <p class="mb-0 fw-bold">Disapproved</p>
                                        <hr>
                                        <h1>{{$stats['leave']['rejected']}}</h1>
                                        <div class="float-end">
                                            <a href="{{route('ess.leave', ['status' => 'disapproved'])}}" class="text-white">View</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mb-3">
                        <div class="card dashboard-card-hover">
                            <div class="card-header bg-primary text-white px-4">
                                <h5 class="my-2 text-uppercase fw-bold">Official Business Applications</h5>
                            </div>
                            <div class="card-body px-3 d-flex">
                                <div class="d-lg-flex gap-3 w-100">
                                    <div class="mb-3 w-100 text-uppercase bg-secondary p-3 rounded-3 text-white">
                                        <p class="mb-0 fw-bold">Pending</p>
                                        <hr>
                                        <h1>{{$stats['obs']['pending']}}</h1>
                                        <div class="float-end">
                                            <a href="{{route('ess.obs', ['status' => 'pending'])}}" class="text-white">View</a>
                                        </div>
                                    </div>
                                    <div class="mb-3 w-100 text-uppercase bg-success p-3 rounded-3 text-white">
                                        <p class="mb-0 fw-bold">Granted</p>
                                        <hr>
                                        <h1>{{$stats['obs']['granted']}}</h1>
                                        <div class="float-end">
                                            <a href="{{route('ess.obs', ['status' => 'granted'])}}" class="text-white">View</a>
                                        </div>
                                    </div>
                                    <div class="mb-3 w-100 text-uppercase bg-danger p-3 rounded-3 text-white">
                                        <p class="mb-0 fw-bold">Disapproved</p>
                                        <hr>
                                        <h1>{{$stats['obs']['rejected']}}</h1>
                                        <div class="float-end">
                                            <a href="{{route('ess.obs', ['status' => 'disapproved'])}}" class="text-white">View</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mb-3">
                        <div class="card dashboard-card-hover">
                            <div class="card-header bg-primary text-white px-4">
                                <h5 class="my-2 text-uppercase fw-bold">Authority To Render Overtime Applications</h5>
                            </div>
                            <div class="card-body px-3 d-flex">
                                <div class="d-lg-flex gap-3 w-100">
                                    <div class="mb-3 w-100 text-uppercase bg-secondary p-3 rounded-3 text-white">
                                        <p class="mb-0 fw-bold">Pending</p>
                                        <hr>
                                        <h1>{{$stats['atro']['pending']}}</h1>
                                        <div class="float-end">
                                            <a href="{{route('ess.atro', ['status' => 'pending'])}}" class="text-white">View</a>
                                        </div>
                                    </div>
                                    <div class="mb-3 w-100 text-uppercase bg-success p-3 rounded-3 text-white">
                                        <p class="mb-0 fw-bold">Granted</p>
                                        <hr>
                                        <h1>{{$stats['atro']['granted']}}</h1>
                                        <div class="float-end">
                                            <a href="{{route('ess.atro', ['status' => 'granted'])}}" class="text-white">View</a>
                                        </div>
                                    </div>
                                    <div class="mb-3 w-100 text-uppercase bg-danger p-3 rounded-3 text-white">
                                        <p class="mb-0 fw-bold">Disapproved</p>
                                        <hr>
                                        <h1>{{$stats['atro']['rejected']}}</h1>
                                        <div class="float-end">
                                            <a href="{{route('ess.atro', ['status' => 'disapproved'])}}" class="text-white">View</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mb-3">
                        <div class="card">
                            <div class="card-header bg-primary text-white px-4">
                                <h5 class="my-2 text-uppercase fw-bold">Offset Applications</h5>
                            </div>
                            <div class="card-body px-3 d-flex">
                                <div class="d-lg-flex gap-3 w-100">
                                    <div class="mb-3 w-100 text-uppercase bg-secondary p-3 rounded-3 text-white">
                                        <p class="mb-0 fw-bold">Pending</p>
                                        <hr>
                                        <h1>{{$stats['offset']['pending']}}</h1>
                                        <div class="float-end">
                                            <a href="{{route('ess.offset', ['status' => 'pending'])}}" class="text-white">View</a>
                                        </div>
                                    </div>
                                    <div class="mb-3 w-100 text-uppercase bg-success p-3 rounded-3 text-white">
                                        <p class="mb-0 fw-bold">Granted</p>
                                        <hr>
                                        <h1>{{$stats['offset']['granted']}}</h1>
                                        <div class="float-end">
                                            <a href="{{route('ess.offset', ['status' => 'granted'])}}" class="text-white">View</a>
                                        </div>
                                    </div>
                                    <div class="mb-3 w-100 text-uppercase bg-danger p-3 rounded-3 text-white">
                                        <p class="mb-0 fw-bold">Disapproved</p>
                                        <hr>
                                        <h1>{{$stats['offset']['rejected']}}</h1>
                                        <div class="float-end">
                                            <a href="{{route('ess.offset', ['status' => 'disapproved'])}}" class="text-white">View</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
                <div class="col-12 col-md-5">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <div class="card dashboard-card-hover">
                                <div class="card-header bg-primary text-white px-4">
                                    <h5 class="my-2 text-uppercase fw-bold">Recruitment</h5>
                                </div>
                                <div class="card-body px-4">
                                    <div class="row">
                                        <div class="col-12 mb-3 col-md-6">
                                            <a href="{{route('job.applicants.index', ['status' => 'pending'])}}" class="nav-link">
                                                <div class="mb-0 alert alert-info w-100 text-uppercase fw-bold">Pending: {{$stats['recruitment']['pending']}}</div>
                                            </a>
                                        </div>
                                        <div class="col-12 mb-3 col-md-6">
                                            <a href="{{route('job.applicants.index', ['status' => 'interview'])}}" class="nav-link">
                                                <div class="mb-0 alert alert-warning w-100 text-uppercase fw-bold">Interview: {{$stats['recruitment']['interview']}}</div>
                                            </a>
                                        </div>
                                        <div class="col-12 mb-3 col-md-6">
                                            <a href="{{route('job.applicants.index', ['status' => 'placement'])}}" class="nav-link">
                                                <div class="mb-0 alert alert-secondary w-100 text-uppercase fw-bold">Placement: {{$stats['recruitment']['placement']}}</div>
                                            </a>
                                        </div>
                                        <div class="col-12 mb-3 col-md-6">
                                            <a href="{{route('job.applicants.index', ['status' => 'onboarding'])}}" class="nav-link">
                                                <div class="mb-0 alert alert-primary w-100 text-uppercase fw-bold">Onboarding: {{$stats['recruitment']['onboarding']}}</div>
                                            </a>
                                        </div>
                                        <div class="col-12 mb-3 col-md-6">
                                            <a href="{{route('job.applicants.index', ['status' => 'hired'])}}" class="nav-link">
                                                <div class="mb-0 alert alert-success w-100 text-uppercase fw-bold">Hired: {{$stats['recruitment']['hired']}}</div>
                                            </a>
                                        </div>
                                        <div class="col-12 mb-3 col-md-6">
                                            <a href="{{route('job.applicants.index', ['status' => 'rejected'])}}" class="nav-link">
                                                <div class="mb-0 alert alert-danger w-100 text-uppercase fw-bold">Rejected: {{$stats['recruitment']['rejected']}}</div>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-center">
                                        <small class="text-uppercase text-muted fw-bold d-flex gap-2 align-items-center">
                                            Click the recruitement status above to view more
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="card dashboard-card-hover">
                                <div class="card-header bg-primary text-white px-4">
                                    <h5 class="my-2 text-uppercase fw-bold">Payroll Summary</h5>
                                </div>
                                <div class="card-body px-4">
                                    <div class="row">
                                        <div class="col-12 mb-3 col-md-6">
                                                <div class="mb-0 alert alert-info w-100 text-uppercase fw-bold">Approved: {{$stats['payroll']['approved']}}</div>
                                           
                                        </div>
                                        <div class="col-12 mb-3 col-md-6">
                                                <div class="mb-0 alert alert-warning w-100 text-uppercase fw-bold">Pending: {{$stats['payroll']['pending']}}</div>
                                         
                                        </div>
                                        
                                        
                                    </div>
                                  
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <div class="card dashboard-card-hover">
                                <div class="card-header bg-primary text-white px-4">
                                    <h5 class="my-2 text-uppercase fw-bold">Work anniversaries & welcome — {{ now()->format('F Y') }}</h5>
                                    <small class="opacity-90">Including new hires and interns this month.</small>
                                </div>
                                <div class="card-body">
                                    @if(!empty($stats['work_anniversaries_this_month']) && count($stats['work_anniversaries_this_month']) > 0)
                                        <ul class="list-unstyled mb-0 text-uppercase fw-bold" style="font-size: 13px;">
                                            @foreach($stats['work_anniversaries_this_month'] as $emp)
                                                <li class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                                    <span>
                                                        {{ $emp['name'] }}
                                                        @if(!empty($emp['type_label']))
                                                            <span class="badge bg-success ms-1">{{ $emp['type_label'] }}</span>
                                                        @endif
                                                        <small class="text-muted fw-normal d-block">{{ $emp['position'] }}</small>
                                                    </span>
                                                    <span class="text-muted small">{{ $emp['date'] }} @if($emp['years'] > 0)({{ $emp['years'] }}y)@endif</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <small class="text-uppercase text-muted">No work anniversaries or new hires this month.</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <div class="card dashboard-card-hover">
                                <div class="card-header bg-primary text-white px-4">
                                    <h5 class="my-2 text-uppercase fw-bold">Birthdays — {{ now()->format('F Y') }}</h5>
                                </div>
                                <div class="card-body">
                                    @if(!empty($stats['birthdays_this_month']) && count($stats['birthdays_this_month']) > 0)
                                        <ul class="list-unstyled mb-0 text-uppercase fw-bold" style="font-size: 13px;">
                                            @foreach($stats['birthdays_this_month'] as $emp)
                                                <li class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                                    <span>{{ $emp['name'] }} <small class="text-muted fw-normal d-block">{{ $emp['position'] }}</small></span>
                                                    <span class="text-muted small">{{ $emp['date'] }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <small class="text-uppercase text-muted">No birthdays this month.</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="card dashboard-card-hover">
                                <div class="card-header bg-primary text-white px-4">
                                    <h5 class="my-2 text-uppercase fw-bold">Other Deductions</h5>
                                </div>
                                <div class="card-body px-4 d-flex">
                                    @if(count($stats['deductions']) > 0)
                                        <ul class="text-uppercase fw-bold list-unstyled">
                                            @foreach($stats['deductions'] as $deductions)
                                                <li style="font-size: 12px">{{$deductions['name']}} <i class="fa fa-check text-primary fs-6 ms-1" aria-hidden="true"></i></li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <small class="text-uppercase text-muted">No Deductions Found.</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                        <div class="card trail dashboard-card-hover">
                            <div class="card-header bg-primary text-white px-4 d-flex justify-content-between">
                                <div>
                                    <h5 class="my-2 text-uppercase fw-bold">Audit Trail Logs</h5>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="scrollable">
                                    <div class="px-4 pt-3">
                                        @if(!empty($this->trails))
                                            <ul class="list-unstyled">
                                                @foreach($this->trails as $index => $log)
                                                    <li>
                                                        <a href="javascript:void(0)" wire:click="download('{{$log}}')" class="d-flex align-items-center gap-2"><i class="fa-solid fa-download"></i> {{$log}}</a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else 
                                            <p class="text-muted fw-bold text-uppercase text-center">no trails found</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>       
            </div>
        </div>
        @endif
   
</div>