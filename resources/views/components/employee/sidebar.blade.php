<div class="employee-sidebar" id="employeeSidebar">

    <div class="sidebar-header">
        <img src="{{ asset('/img/' . $provider['client_logo']) }}" class="sidebar-logo">
        <h4 class="company-name" >{{ $companyInfo->name ?? 'Novulutions Inc.' }}</h4>
        
    </div>

    <div class="sidebar-menu">

        <a href="{{ route('employee.dashboard') }}" class="menu-item">
            <i class="fa-solid fa-gauge"></i> Dashboard
        </a>

        <a href="{{ route('employee.announcements.index') }}" class="menu-item">
            <i class="fa-solid fa-bullhorn"></i> Announcements
        </a>

        <a href="{{ route('employee.dtr') }}" class="menu-item">
            <i class="fa-solid fa-file-lines"></i> Daily Time Record
        </a>

        <a href="{{ route('employee.time-adjustments') }}" class="menu-item">
            <i class="fa-solid fa-clock-rotate-left"></i> Time Adjustments
        </a>
        <a href="{{ route('employee.payslip') }}" class="menu-item">
            <i class="fa-solid fa-money-check-dollar"></i> Payslip
        </a>

        <a href="{{ route('employee.leave') }}" class="menu-item">
            <i class="fa-solid fa-umbrella-beach"></i> Leave Application
        </a>

    <!--   <a href="{{ route('employee.loan') }}" class="menu-item">
            <i class="fa-solid fa-hand-holding-dollar"></i> Loan Application
        </a>-->
        
      <!-- <a href="{{ route('employee.leave-card') }}" class="menu-item">
            <i class="fa-solid fa-id-card"></i> Leave Card
        </a>

        <a href="{{ route('employee.credit') }}" class="menu-item">
            <i class="fa-solid fa-coins"></i> Leave Credits
        </a>-->

        <a href="{{ route('employee.atro') }}" class="menu-item">
            <i class="fa-solid fa-business-time"></i> Overtime (ATRO)
        </a>

        <a href="{{ route('employee.obs.index') }}" class="menu-item">
            <i class="fa-solid fa-briefcase"></i> Official Business
        </a>

       <!-- <a href="{{ route('employee.clock') }}" class="menu-item clock-button">
            <i class="fa-solid fa-clock"></i> Clock In / Out
        </a>-->
        <a href="{{ route('employee.team') }}" class="menu-item">
            <i class="fa-solid fa-users"></i> My Team
        </a>

        <a href="{{ route('employee.messages') }}" class="menu-item">
            <i class="fa-solid fa-envelope"></i> Contact HR
        </a>

       <a href="{{ route('employee.directory') }}" class="menu-item">
            <i class="fa-solid fa-table-cells-large"></i> Directory
        </a>
        <a href="{{ route('employee.tutorial') }}" class="menu-item">
            <i class="fa-solid fa-graduation-cap"></i> Tutorials
        </a>

    </div>

</div>

<!-- MOBILE TOGGLE -->
<!--<div class="sidebar-toggle" id="sidebarToggle_">
    <i class="fa-solid fa-bars"></i>
</div>-->
