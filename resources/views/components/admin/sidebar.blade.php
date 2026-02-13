<div class="sidebar">
    <div class="overlay"></div>
    <div class="sidebar-content">
        <div class="close-icon d-xl-none">
            <i class="fa-solid fa-xmark"></i>
        </div>
        <div class="w-100 px-4">
            <a class="navbar-brand text-uppercase" href="{{ url('/') }}">
                <img src="{{ asset('/img/' . $provider['client_logo']) }}">            
            </a>
            <div class="content">
                <ul class="list-unstyled sidebar-menu">
                    <hr>
                    <div class="d-flex align-items-center gap-3 pb-0">
                        <img class="profile-img" style="width: 40px; height: 40px" src="https://ui-avatars.com/api/?background=005668&amp;color=ffffff&amp;font-size=0.4&amp;bold=true&amp;name=Kim+Mariano" alt="Profile Image">
                        <div class="name">
                            <p class="fw-bold text-uppercase" style="margin-bottom: -4px;">{{Auth::user()->name}}</p>
                            <small class="text-uppercase fw-bold text-muted mb-0">{{ Auth::user()->getRoleNames()->first() }}</small>
                        </div>
                    </div>
                    <hr>
                    <p class="text-muted fw-bold text-uppercase mt-4 mb-2" style="font-size: 12px;">Menu</p>
                    <div class="scrollable">

                        <!-- Dashboard -->
                        <li class="list-item">
                            <a class="nav-link" href="{{route('admin.dashboard')}}">
                                <i class="fa-solid fa-house"></i>
                                Dashboard
                            </a>
                        </li>
                
                        @unlessrole('supervisor')
                        <!-- Recruitment -->
                        <li class="list-item has-submenu">
                            <a class="nav-link toggle-link">
                                <i class="fa-solid fa-user-plus"></i>
                                Recruitment
                            </a>
                            <ul class="submenu">
                                @can('read jobs')
                                    <li><a class="dropdown-item" href="{{route('job.posts.index')}}">Job Posting</a></li>
                                @endcan
                                @can('read applicants')
                                    <li><a class="dropdown-item" href="{{route('job.applicants.index', ['status' => 'pending'])}}">Applicants</a></li>
                                @endcan
                            </ul>
                        </li>
                
                        <!-- HRIS -->
                        <li class="list-item">
                            <a class="nav-link" href="{{route('hris.index')}}">
                                <i class="fa-solid fa-users"></i>
                                HRIS
                            </a>
                        </li>
                
                        <!-- Timekeeping -->
                        <li class="list-item has-submenu">
                            <a class="nav-link toggle-link">
                                <i class="fa-solid fa-clock"></i>
                                Timekeeping
                            </a>
                            <ul class="submenu">
                                @can('write timelogs')
                                    <li><a class="dropdown-item" href="{{route('timekeeping.upload')}}">Add Time Logs</a></li>
                                @endcan
                            </ul>
                        </li>
                        @endunlessrole

                        <!-- ESS -->
                        <li class="list-item has-submenu">
                            <a class="nav-link toggle-link">
                                <i class="fa-solid fa-user-cog"></i>
                                Employee Self Service
                            </a>
                            <ul class="submenu">
                                @can('read leave')
                                    <li><a class="dropdown-item" href="{{route('ess.leave')}}">Leave Applications</a></li>
                                @endcan
                        
                                @can('read obs')
                                    <li><a class="dropdown-item" href="{{route('ess.obs')}}">Official Business Slip Application</a></li>
                                @endcan
                        
                                @can('read offset')
                                    <li><a class="dropdown-item" href="{{route('ess.offset')}}">Offset Application</a></li>
                                @endcan
                        
                                @can('read atro')
                                    <li><a class="dropdown-item" href="{{route('ess.atro')}}">Authority To Render Overtime Application</a></li>
                                @endcan
                        
                                @can('read announcements')
                                    <li><a class="dropdown-item" href="{{route('ess.announcements.index')}}">Announcements</a></li>
                                @endcan
                        
                                @can('read employee-profile-approval')
                                    <li><a class="dropdown-item" href="{{route('ess.approval-profile.index', )}}">Employee Profile Approval</a></li>
                                @endcan
                        
                                {{--@can('read request-status')
                                    <li><a class="dropdown-item" href="{{route('ess.request-status')}}">Request Status</a></li>
                                @endcan--}}
                            </ul>
                        </li>
                        <!-- REPORTS -->
                        <li class="list-item has-submenu">
                            <a class="nav-link toggle-link">
                                <i class="fa-solid fa-chart-line"></i>
                                Reports
                            </a>
                            <ul class="submenu">
                                @can('read dtr')
                                    <li><a href="{{ route('reports.dtr') }}" class="dropdown-item">Daily Time Record (DTR)</a></li>
                                @endcan
                            </ul>
                        </li>

                        @unlessrole('supervisor')
                        <!-- SETTINGS -->
                        <li class="list-item has-submenu">
                            <a class="nav-link toggle-link">
                                <i class="fa-solid fa-cogs"></i>
                                Settings
                            </a>
                            <ul class="submenu">
                                <li><a class="dropdown-item" href="{{ route('company.index') }}">Company Information</a></li>
                                @canany(['read branches', 'read departments', 'read sections'])
                                <li class="list-item">
                                    <a class="nav-link toggle-link">Location Management</a>
                                    <ul class="submenu">
                                        @can('read branches')
                                        <li><a class="dropdown-item" href="{{ route('branch.index') }}">Branches</a></li>
                                        @endcan
                                        @can('read departments')
                                        <li><a class="dropdown-item" href="{{ route('department.index') }}">Departments</a></li>
                                        @endcan
                                        @can('read sections')
                                        <li><a class="dropdown-item" href="{{ route('section.index') }}">Sections</a></li>
                                        @endcan
                                    </ul>
                                </li>
                                @endcanany

                                @canany(['read assessments', 'read requirements'])
                                <li class="list-item">
                                    <a class="nav-link toggle-link">Recruitment</a>
                                    <ul class="submenu">
                                        @can('read assessments')
                                        <li><a class="dropdown-item" href="{{ route('job.interview.index') }}">Assessment</a></li>
                                        @endcan
                                        @can('read requirements')
                                        <li><a class="dropdown-item" href="{{ route('job.requirements.index') }}">Requirements</a></li>
                                        @endcan
                                    </ul>
                                </li>
                                @endcanany

                                @canany(['read users', 'read roles'])
                                <li class="list-item">
                                    <a class="nav-link toggle-link">User Management</a>
                                    <ul class="submenu">
                                        @can('read users')
                                        <li><a class="dropdown-item" href="{{ route('users.index', ['type' => 'applicants']) }}">Users</a></li>
                                        @endcan
                                        @can('read roles')
                                        <li><a class="dropdown-item" href="{{ route('users.access.index') }}">Roles</a></li>
                                        @endcan
                                    </ul>
                                </li>
                                @endcanany

                                @canany([
                                    'read bank-information', 'read employment-type', 'read positions',
                                    'read violations', 'read leave-types', 'read offset-credits', 'read gsis-billing',
                                    'read other-earnings', 'read other-deductions'
                                ])
                                <li class="list-item">
                                    <a class="nav-link toggle-link">HRIS</a>
                                    <ul class="submenu">
                                        @can('read bank-information')
                                        <li><a class="dropdown-item" href="{{ route('bank-information.index') }}">Bank Information</a></li>
                                        @endcan
                                        @can('read employment-type')
                                        <li><a class="dropdown-item" href="{{ route('employment-type.index') }}">Employment Type</a></li>
                                        @endcan
                                        @can('read positions')
                                        <li><a class="dropdown-item" href="{{ route('position.index') }}">Positions</a></li>
                                        @endcan
                                        @can('read violations')
                                        <li><a class="dropdown-item" href="{{ route('violation.index') }}">Violations</a></li>
                                        @endcan
                                        @can('read leave-types')
                                        <li><a class="dropdown-item" href="{{ route('leave.index') }}">Leaves</a></li>
                                        @endcan
                                        @can('read offset-credits')
                                        <li><a class="dropdown-item" href="{{ route('offset-credits.index') }}">Offset Credits</a></li>
                                        @endcan
                                        @can('read gsis-billing')
                                        <li><a class="dropdown-item" href="{{ route('gsis.index') }}">GSIS Billings</a></li>
                                        @endcan
                                        @can('read other-earnings')
                                        <li><a class="dropdown-item" href="{{ route('other-earnings.index') }}">Earnings</a></li>
                                        @endcan
                                        @can('read other-deductions')
                                        <li><a class="dropdown-item" href="{{ route('other-deductions.index') }}">Deduction</a></li>
                                        @endcan
                                        @can('read other-deductions')
                                        <li><a class="dropdown-item" href="{{ route('leave.import.index') }}">Import Leaves</a></li>
                                        @endcan
                                    </ul>
                                </li>
                                @endcanany

                                @canany(['read shift-schedule', 'read employee-schedule'])
                                <li class="list-item">
                                    <a class="nav-link toggle-link">Timekeeping</a>
                                    <ul class="submenu">
                                        @can('read shift-schedule')
                                        <li><a class="dropdown-item" href="{{ route('shift-schedule.index') }}">Shift Schedule</a></li>
                                        @endcan
                                        @can('read employee-schedule')
                                        <li><a class="dropdown-item" href="{{ route('employee-schedule.index') }}">Employee Schedule</a></li>
                                        @endcan
                                    </ul>
                                </li>
                                @endcanany

                                @canany(['read holidays', 'read payroll-period', 'read payroll-configuration'])
                                <li class="list-item">
                                    <a class="nav-link toggle-link">Payroll</a>
                                    <ul class="submenu">
                                        @can('read holidays')
                                        <li><a class="dropdown-item" href="{{ route('holiday.index') }}">Holidays</a></li>
                                        @endcan
                                        @can('read payroll-period')
                                        <li><a class="dropdown-item" href="#">Payroll Period</a></li>
                                        @endcan
                                        @can('read payroll-configuration')
                                        <li><a class="dropdown-item" href="#">Payroll Configuration</a></li>
                                        @endcan
                                    </ul>
                                </li>
                                @endcanany
                            </ul>
                        </li>
                        @endunlessrole

                        @if(auth()->check() && method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('superadmin'))
                            <li class="list-item mt-3">
                                <a class="nav-link" href="{{ route('settings.employee-modules') }}">
                                    <i class="fa-solid fa-sliders"></i>
                                    Employee Modules
                                </a>
                            </li>
                        @endif

                        <li class="list-item mt-2">
                            <a class="nav-link" href="{{ route('admin.change-password') }}">
                                <i class="fa-solid fa-key"></i>
                                Change Password
                            </a>
                        </li>

                        <!-- LOGOUT -->
                        <li class="list-item">
                            <a class="nav-link" href="{{route('admin.logout')}}">
                                <i class="fa-solid fa-arrow-right-from-bracket fa-flip-horizontal"></i>
                                Logout
                            </a>
                        </li>
                    </div>
                </ul>
            </div>
        </div>
        <div class="footer">
            &copy; {{ now()->format('Y') }} Novulutions Inc. All Rights Reserved.
        </div>
    </div>
</div>