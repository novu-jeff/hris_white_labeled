@php use App\Helpers\EmployeeModules; @endphp
<div class="employee-sidebar" id="employeeSidebar">

    <div class="sidebar-header">
        <img src="{{ asset('/img/' . $provider['client_logo']) }}" class="sidebar-logo">
        <h4 class="company-name" >{{ $companyInfo->name ?? 'Novulutions Inc.' }}</h4>
        
    </div>

    <div class="sidebar-menu">

        @if(EmployeeModules::isNavModuleEnabled('dashboard'))
        <a href="{{ route('employee.dashboard') }}" class="menu-item">
            <i class="fa-solid fa-gauge"></i> Dashboard
        </a>
        @endif

        @if(EmployeeModules::isNavModuleEnabled('announcements'))
        <a href="{{ route('employee.announcements.index') }}" class="menu-item">
            <i class="fa-solid fa-bullhorn"></i> Announcements
        </a>
        @endif

        @if(EmployeeModules::isNavModuleEnabled('dtr'))
        <a href="{{ route('employee.dtr') }}" class="menu-item">
            <i class="fa-solid fa-file-lines"></i> Daily Time Record
        </a>
        @endif

        @if(EmployeeModules::isNavModuleEnabled('time_adjustments'))
        <a href="{{ route('employee.time-adjustments') }}" class="menu-item">
            <i class="fa-solid fa-clock-rotate-left"></i> Time Adjustments
        </a>
        @endif

        @if(EmployeeModules::isNavModuleEnabled('payslip'))
        <a href="{{ route('employee.payslip') }}" class="menu-item">
            <i class="fa-solid fa-money-check-dollar"></i> Payslip
        </a>
        @endif

        @if(EmployeeModules::isNavModuleEnabled('leave'))
        <a href="{{ route('employee.leave') }}" class="menu-item">
            <i class="fa-solid fa-umbrella-beach"></i> Leave Application
        </a>
        @endif

        @if(EmployeeModules::isNavModuleEnabled('atro'))
        <a href="{{ route('employee.atro') }}" class="menu-item">
            <i class="fa-solid fa-business-time"></i> Overtime (ATRO)
        </a>
        @endif

        @if(EmployeeModules::isNavModuleEnabled('obs'))
        <a href="{{ route('employee.obs.index') }}" class="menu-item">
            <i class="fa-solid fa-briefcase"></i> Official Business
        </a>
        @endif

        @if(EmployeeModules::isNavModuleEnabled('offset'))
        <a href="{{ route('employee.offset.index') }}" class="menu-item">
            <i class="fa-solid fa-calendar-day"></i> Offset Application
        </a>
        @endif

        @if(EmployeeModules::isNavModuleEnabled('team'))
        <a href="{{ route('employee.team') }}" class="menu-item">
            <i class="fa-solid fa-users"></i> My Team
        </a>
        @endif

        @if(EmployeeModules::isNavModuleEnabled('messages'))
        <a href="{{ route('employee.messages') }}" class="menu-item">
            <i class="fa-solid fa-envelope"></i> Contact HR
        </a>
        @endif

        @if(EmployeeModules::isNavModuleEnabled('directory'))
        <a href="{{ route('employee.directory') }}" class="menu-item">
            <i class="fa-solid fa-table-cells-large"></i> Directory
        </a>
        @endif

        @if(EmployeeModules::isNavModuleEnabled('tutorial'))
        <a href="{{ route('employee.tutorial') }}" class="menu-item">
            <i class="fa-solid fa-graduation-cap"></i> Tutorials
        </a>
        @endif

        @if(EmployeeModules::isNavModuleEnabled('profile'))
        <a href="{{ route('employee.profile', ['form' => 'personal']) }}" class="menu-item">
            <i class="fa-solid fa-user"></i> My Profile
        </a>
        @endif

        @if(EmployeeModules::isNavModuleEnabled('security_notifications'))
        <a href="{{ route('employee.profile', ['form' => 'security-notifications']) }}" class="menu-item">
            <i class="fa-solid fa-shield-halved"></i> Security &amp; Notifications
        </a>
        @endif

    </div>

</div>
