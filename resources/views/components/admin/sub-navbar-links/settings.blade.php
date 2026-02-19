<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fa-solid fa-cogs"></i>
        Settings
    </a>
    <ul class="dropdown-menu">
        @can('read company-information')
            <li><a class="dropdown-item" href="{{route('company.index')}}">Company Information</a></li>
        @endcan
        @can('read scheduler')
            <li><a class="dropdown-item" href="{{route('scheduler.index')}}">Scheduler</a></li>
        @endcan
        @can('read tranches')
            <li><a class="dropdown-item" href="{{route('tranches.index')}}">Tranches</a></li>
        @endcan
        @can('read holidays')
            <li><a class="dropdown-item" href="{{route('holiday.index')}}">Holiday</a></li>
        @endcan
        <li><a class="dropdown-item" href="{{ route('payroll.settings') }}">Payroll Settings</a></li>
        @canany([
            'read departments',
            'read sections'
        ])
            <li class="nav-item dropstart">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Location Management
                </a>
                <ul class="dropdown-menu">
                    @can('read departments')
                        <li><a class="dropdown-item" href="{{route('department.index')}}">Departments</a></li>
                    @endcan

                    @can('read sections')
                        <li><a class="dropdown-item" href="{{route('section.index')}}">Sections</a></li>
                    @endcan
                </ul>
            </li>
        @endcanany
        @canany([
            'read assessments',
            'read requirements',
        ])
            <li class="nav-item dropstart">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Recruitment
                </a>
                <ul class="dropdown-menu">
                    @can('read assessments')
                        <li><a class="dropdown-item" href="{{route('job.interview.index')}}">Assessment</a></li>
                    @endcan
                    @can('read requirements')
                        <li><a class="dropdown-item" href="{{route('job.requirements.index')}}">Requirements</a></li>
                    @endcan 
                </ul>
            </li>
        @endcanany
        @canany([
            'read users',
            'read roles',
        ])
            <li class="nav-item dropstart">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    User Management
                </a>
                <ul class="dropdown-menu">
                    @can('read users')
                        <li><a class="dropdown-item" href="{{route('users.index', ['type' => 'applicants'])}}">Users</a></li>
                    @endcan
                    @can('read roles')
                        <li><a class="dropdown-item" href="{{route('users.access.index')}}">Roles</a></li>
                    @endcan
                </ul>
            </li>
        @endcanany
        @canany([
            'read bank-information',
            'read employment-type',
            'read positions',
            'read violations',
            'read leave-types',
            'read offset-credits',
            'read gsis-billing',
            'read other-earnings',
            'read other-deductions',
        ])
            <li class="nav-item dropstart">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    HRIS
                </a>
                <ul class="dropdown-menu">
                    {{-- @can('read bank-information')
                        <li><a class="dropdown-item" href="{{route('bank-information.index')}}">Bank Information</a></li>
                    @endcan --}}
                    @can('read employment-type')
                        <li><a class="dropdown-item" href="{{route('employment-type.index')}}">Employment Type</a></li>
                    @endcan
                    @can('read positions')
                        <li><a class="dropdown-item" href="{{route('position.index')}}">Positions</a></li>
                    @endcan

                    @can('read violations')
                        <li><a class="dropdown-item" href="{{route('violation.index')}}">Violations</a></li>
                    @endcan

                    @can('read leave-types')
                        <li><a class="dropdown-item" href="{{route('leave.index')}}">Leaves</a></li>
                    @endcan
                    @can('read offset-credits')
                        <li><a class="dropdown-item" href="{{ route('offset-credits.index') }}">Offset Credits</a></li>
                    @endcan

                    @can('read gsis-billing')
                        <li><a class="dropdown-item" href="{{route('gsis.index')}}">GSIS Billings</a></li>
                    @endcan

                    @can('read other-earnings')
                        <li><a class="dropdown-item" href="{{route('other-earnings.index')}}">Earnings</a></li>
                    @endcan

                    @can('read other-deductions')
                        <li><a class="dropdown-item" href="{{route('other-deductions.index')}}">Deductions</a></li>
                    @endcan
                       @can('read other-deductions')
                        <li><a class="dropdown-item" href="{{ route('leave.import.index') }}">Import Leaves</a></li>
                        @endcan
                </ul>
            </li>
        @endcanany
        @canany([
            'read shift-schedule',
            'read employee-schedule',
        ])
            <li class="nav-item dropstart">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Timekeeping
                </a>
                <ul class="dropdown-menu">
                    @can('read shift-schedule')
                        <li><a class="dropdown-item" href="{{route('shift-schedule.index')}}">Shift Schedule</a></li>
                    @endcan
                    @can('read employee-schedule')
                        <li><a class="dropdown-item" href="{{route('employee-schedule.index')}}">Employee Schedule</a></li>
                    @endcan
                </ul>
            </li>
        @endcanany
        <li><a class="dropdown-item" href="{{route('system.jobs')}}">System Jobs</a></li>
        @if(auth()->user() && method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('superadmin'))
            <li><a class="dropdown-item" href="{{ route('settings.employee-modules') }}">Employee Modules</a></li>
        @endif
    </ul>
</li>