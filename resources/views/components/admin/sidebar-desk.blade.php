<div class="employee-sidebar" id="adminSidebar">

    <div class="sidebar-header">
        <img src="{{ asset('/img/' . $provider['client_logo']) }}" class="sidebar-logo">
        <h4 class="company-name">{{ $companyInfo->name ?? 'Admin Panel' }}</h4>
    </div>

    <div class="sidebar-menu">

    <!-- Dashboard -->
        <a href="{{ route('admin.dashboard') }}" class="menu-item">
            <i class="fa-solid fa-house"></i> Dashboard
        </a>

        @unlessrole('supervisor')
        <!-- Recruitment -->
        @canany([
            'read jobs',
            'read applicants'
        ])
        <div class="menu-group">
            <p class="menu-group-title"><i class="fa-solid fa-user-plus"></i> Recruitment</p>
            <div class="submenu-items">
                @can('read jobs')
                <a href="{{ route('job.posts.index') }}" class="submenu-item">
                    <i class="fa-solid fa-briefcase"></i> Job Posting
                </a>
                @endcan

                @can('read applicants')
                <a href="{{ route('job.applicants.index', ['status' => 'pending']) }}" class="submenu-item">
                    <i class="fa-solid fa-user-check"></i> Applicants
                </a>
                @endcan
            </div>
        </div>
        @endcanany

        <!-- HRIS -->
        <a href="{{ route('hris.index') }}" class="menu-item">
            <i class="fa-solid fa-users"></i> HRIS
        </a>

        <!-- Timekeeping -->
        @if(config('app.allow_upload_timelogs'))
        <div class="menu-group">
            <p class="menu-group-title"><i class="fa-solid fa-clock"></i> Timekeeping</p>
            <div class="submenu-items">
                 @if(config('app.allow_upload_timelogs'))
                    @can('write timelogs')
                        <a href="{{ route('timekeeping.upload') }}" class="submenu-item">
                            <i class="fa-solid fa-upload"></i>  Add Time Logs
                        </a>
                    @endcan
                 @endif   
            </div>
        </div>
        @endif

        <!-- Payroll -->
        <a href="{{ route('payroll.index') }}" class="menu-item">
            <i class="fa-solid fa-dollar-sign"></i> Payroll
        </a>
        @endunlessrole

         <!-- ESS -->
        @canany([
            'read leave',
            'read obs',
            'read atro',
            'read announcements',
            'read employee-profile-update',
            'read request-status'
        ])
        <div class="menu-group">
            <p class="menu-group-title"><i class="fa-solid fa-user-cog"></i> Employee Self Service</p>
            <div class="submenu-items">
                @can('read leave')
                <a href="{{ route('ess.leave') }}" class="submenu-item">
                    <i class="fa-solid fa-calendar-minus"></i> Leave Applications
                </a>
                @endcan

               {{--  @can('read leave')
                <a href="{{ route('ess.loan') }}" class="submenu-item">
                    <i class="fa-solid fa-hand-holding-dollar"></i> Loan Applications
                </a>
                @endcan --}}

                @can('read obs')
                <a href="{{ route('ess.obs') }}" class="submenu-item">
                    <i class="fa-solid fa-briefcase"></i> Official Business Slip Application
                </a>
                @endcan

                @can('read offset')
                <a href="{{ route('ess.offset') }}" class="submenu-item">
                    <i class="fa-solid fa-calendar-day"></i> Offset Application
                </a>
                @endcan

                @can('read atro')
                <a href="{{ route('ess.atro') }}" class="submenu-item">
                    <i class="fa-solid fa-clock"></i> Authority To Render Overtime Application</a>
                @endcan

                @can('read time-adjustments')
                <a href="{{ route('ess.time-adjustments') }}" class="submenu-item">
                    <i class="fa-solid fa-clock-rotate-left"></i> Time Adjustments</a>
                @endcan

                @can('read payslip-request')
                <a href="{{ route('ess.payslip-request') }}" class="submenu-item">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Payslip Request</a>
                @endcan

                @can('read announcements')
                <a href="{{ route('ess.announcements.index') }}" class="submenu-item">
                    <i class="fa-solid fa-bullhorn"></i> Announcements</a>
                @endcan

                @can('read employee-profile-approval')
                <a href="{{ route('ess.approval-profile.index') }}" class="submenu-item">
                    <i class="fa-solid fa-user-check"></i> Employee Profile Approval</a>
                @endcan

                @can('read messages')
                <a href="{{ route('ess.messages') }}" class="submenu-item">
                    <i class="fa-solid fa-envelope"></i> Messages</a>
                @endcan

                @can('read faqs')
                <a href="{{ route('ess.faqs.index') }}" class="submenu-item">
                     <i class="fa-solid fa-question-circle"></i> FAQs</a>
                @endcan
            </div>
        </div>
        @endcanany
        

        @canany([
            'read dtr',
            'read bir-2316'
        ])
        <div class="menu-group">
            <p class="menu-group-title"><i class="fa-solid fa-chart-line"></i> Reports</p>
            <div class="submenu-items">
                @can('read dtr')
                <a href="{{ route('reports.dtr') }}" class="submenu-item">
                    <i class="fa-solid fa-clipboard-list"></i> Daily Time Record</a>
                @endcan
                @unlessrole('supervisor')
                    @if($product == 'private')
                        <a href="{{ route('reports.bir') }}" class="submenu-item">
                            <i class="fa-solid fa-file-lines"></i> BIR</a>
                        <a href="{{ route('reports.philhealth') }}" class="submenu-item">
                            <i class="fa-solid fa-heart-circle-check"></i> PhilHeath</a>
                        <a href="{{ route('reports.sss') }}" class="submenu-item">
                            <i class="fa-solid fa-id-card"></i> SSS</a>
                        <a href="{{ route('reports.pagibig') }}" class="submenu-item">
                            <i class="fa-solid fa-hand-holding-heart"></i> Pagibig</a>
                    @endif
                @endunlessrole
            </div>    
        </div>
         @endcanany 

         @unlessrole('supervisor')
         <!-- HRIS -->
        @canany([
            'read company-information', 'read scheduler', 'read tranches', 'read holidays',
            'read branches', 'read departments', 'read sections', 'read assessments', 'read requirements',
            'read users', 'read roles', 'read bank-information', 'read employment-type', 'read positions',
            'read violations', 'read leave-types', 'read gsis-billing', 'read other-earnings', 'read other-deductions'
        ])
        <!-- Settings -->
        <div class="menu-group">
            <p class="menu-group-title"><i class="fa-solid fa-cogs"></i> Settings</p>

            <div class="submenu-items">
                @can('read company-information')
                <a href="{{ route('company.index') }}" class="submenu-item">
                    <i class="fa-solid fa-building"></i> Company Information</a>
                 @endcan
                 @can('read scheduler')
                    <a class="submenu-item" href="{{route('scheduler.index')}}">
                         <i class="fa-solid fa-calendar"></i> Scheduler</a>
                @endcan
                
                @can('read holidays')
                    <a class="submenu-item" href="{{route('holiday.index')}}">
                         <i class="fa-solid fa-umbrella-beach"></i> Holiday</a>
                @endcan

                <!-- Location Management -->
                @canany(['read departments', 'read sections'])
                <div class="submenu-subgroup">
                    <p class="submenu-subtitle"> <i class="fa-solid fa-map-pin"></i> Location Management</p>
                    @can('read sections')
                    <a href="{{ route('section.index') }}" class="submenu-item">
                        <i class="fa-solid fa-sitemap"></i> Department</a>
                    @endcan

                </div>
                @endcanany

                <!-- Recruitment -->
                @canany(['read assessments', 'read requirements'])
                <div class="submenu-subgroup">
                    <p class="submenu-subtitle"> <i class="fa-solid fa-users-gear"></i> Recruitment</p>
                    @can('read assessments')
                    <a href="{{ route('job.interview.index') }}" class="submenu-item">
                        <i class="fa-solid fa-clipboard-question"></i> Assessment</a>
                    @endcan
                    @can('read requirements')
                    <a href="{{ route('job.requirements.index') }}" class="submenu-item">
                        <i class="fa-solid fa-file-circle-check"></i> Requirements</a>
                    @endcan
                </div>
                @endcanany

                <!-- User Management -->
                @canany(['read users', 'read roles'])
                <div class="submenu-subgroup">
                    <p class="submenu-subtitle">
                        <i class="fa-solid fa-user-gear"></i> User Management</p>
                    @can('read users')
                    <a href="{{ route('users.index', ['type' => 'applicants']) }}" class="submenu-item">
                        <i class="fa-solid fa-users"></i> Users</a>
                    @endcan
                    @can('read users')
                    <a href="{{ route('user.trails') }}" class="submenu-item">
                        <i class="fa-solid fa-clock-rotate-left"></i> Users Audit Trail Logs
                    </a>
                    @endcan
                    @can('read roles')
                    <a href="{{ route('users.access.index') }}" class="submenu-item">
                        <i class="fa-solid fa-shield-halved"></i> Roles</a>
                    @endcan
                </div>
                @endcanany

                <!-- HRIS -->
                @canany([
                    'read bank-information', 'read employment-type', 'read positions',
                    'read violations', 'read leave-types', 'read gsis-billing',
                    'read other-earnings', 'read other-deductions'
                ])
                <div class="submenu-subgroup">
                    <p class="submenu-subtitle"><i class="fa-solid fa-id-card-clip"></i> HRIS</p>

                   {{-- @can('read bank-information')
                    <a href="{{ route('bank-information.index') }}" class="submenu-item">Bank Information</a>
                    @endcan--}}
                    @can('read employment-type')
                    <a href="{{ route('employment-type.index') }}" class="submenu-item">
                        <i class="fa-solid fa-user-tag"></i> Employment Type</a>
                    @endcan
                    {{-- @can('read employment-type')
                    <a href="{{ route('loan-type.index') }}" class="submenu-item">
                        <i class="fa-solid fa-hand-holding-dollar"></i> Loan Type</a>
                    @endcan--}}
                    @can('read positions')
                    <a href="{{ route('position.index') }}" class="submenu-item">
                        <i class="fa-solid fa-briefcase"></i> Positions</a>
                    @endcan
                    @can('read violations')
                    <a href="{{ route('violation.index') }}" class="submenu-item">
                        <i class="fa-solid fa-triangle-exclamation"></i> Violations</a>
                    @endcan
                    @can('read leave-types')
                    <a href="{{ route('leave.index') }}" class="submenu-item">
                        <i class="fa-solid fa-calendar-check"></i>  Leaves</a>
                    @endcan
                    
                    @can('read other-earnings')
                    <a href="{{ route('other-earnings.index') }}" class="submenu-item">
                        <i class="fa-solid fa-coins"></i>  Earnings</a>
                    @endcan
                    @can('read other-deductions')
                    <a href="{{ route('other-deductions.index') }}" class="submenu-item">
                        <i class="fa-solid fa-receipt"></i>  Deductions</a>
                    @endcan
                </div>
                @endcanany

                @canany(['read shift-schedule', 'read employee-schedule'])
                <div class="submenu-subgroup">
                    <p class="submenu-subtitle"><i class="fa-solid fa-clock"></i> Timekeeping</p>
                    @can('read shift-schedule')
                     <a href="{{ route('shift-schedule.index') }}" class="submenu-item">
                        <i class="fa-solid fa-user-tag"></i> Shift Schedule</a>
                     @endcan
                     @can('read employee-schedule')
                     <a href="{{ route('employee-schedule.index') }}" class="submenu-item">
                        <i class="fa-solid fa-user-tag"></i> Employee Schedule</a>
                     @endcan
                </div>    
                               
                @endcanany

            </div>
        </div>
        @endcanany 
        @endunlessrole

        <a href="{{ route('admin.change-password') }}" class="menu-item mt-2">
            <i class="fa-solid fa-key"></i> Change Password
        </a>
    </div>

</div>
