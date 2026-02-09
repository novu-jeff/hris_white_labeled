@canany([
    'read leave',
    'read obs',
    'read offset',
    'read atro',
    'read announcements',
    'read employee-profile-update',
    'read request-status'
])
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fa-solid fa-user-cog"></i>
        Employee Self Service
    </a>
    <ul class="dropdown-menu">
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

        @can('read time-adjustments')
            <li><a class="dropdown-item" href="{{route('ess.time-adjustments')}}">Time Adjustments</a></li>
        @endcan

        @can('read payslip-request')
            <li><a class="dropdown-item" href="{{route('ess.payslip-request')}}">Payslip Request</a></li>
        @endcan

        @can('read announcements')
            <li><a class="dropdown-item" href="{{route('ess.announcements.index')}}">Announcements</a></li>
        @endcan

        @can('read employee-profile-approval')
            <li><a class="dropdown-item" href="{{route('ess.approval-profile.index')}}">Employee Profile Approval</a></li>
        @endcan

        @can('read messages')
            <li><a class="dropdown-item" href="{{route('ess.messages')}}">Messages</a></li>
        @endcan

        @can('read faqs')
            <li><a class="dropdown-item" href="{{route('ess.faqs.index')}}">FAQs</a></li>
        @endcan
    </ul>
</li>
@endcanany