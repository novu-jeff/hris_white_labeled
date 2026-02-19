@push('style')
<style>

    td {
        position: relative;
    }

    .underline {
        min-width: 300px;
        width: fit-content;
        border-bottom: 1px solid black;
        padding: 0 10px 0 20px;
        display: inline-flex;
        align-items: end;
    }

    .print-container {
        display: flex;
        justify-content: center;
        width: 100%;
        gap: 20px;
        padding: 0 80px 0 80px;
    }

    .print-container .dtr:nth-of-type(2) {
        display: none;
    }

    .dtr {
        width: 800px;
        margin: 50px auto;
        padding: 10mm 5mm;
        box-sizing: border-box;
        border: 1px solid rgb(178, 178, 178);
        background-color: #fff;
        border-radius: 12px;
        position: relative;
    }

    .loading-screen {
        position: absolute;
        height: 100%;
        width: 100%;
        z-index: 2;
        left: 8px;
        top: 8px;
    }

    .dtr-header {
        position: relative;
        text-align: center;
        margin-bottom: 20px;
    }

    .dtr-header img {
        position: absolute;
        top: -10px;
        left: 30px;
        height: 70px;
    }

    @media(max-width: 993px ) {
        .dtr-header img {
            left: 0;
        }
    }

    .dtr-header h1 {
        font-size: 14px;
        margin: 5px 0;
    }

    .dtr-info {
        margin-bottom: 20px;
        font-size: 14px;
    }

    .dtr-info div {
        margin-bottom: 5px;
    }

    .dtr-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px !important;
    }

    .dtr-table th, .dtr-table td {
        border: 1px solid black;
        text-align: center;
        padding: 5px;
        font-size: 12px; 
    }

    .dtr-summary {
        margin-top: 20px;
        font-size: 12px;
    }
    .dtr-summary td {
        text-align: left
    }

    .dtr-summary .signature {
        margin-top: 40px;
        text-align: center;
        font-size: 12px;
    }

    .signature h5 {
        text-transform: uppercase;
        font-weight: bold;
    }

    .shaded-box {
        position: absolute;
        top: 0;
        right: 0;
        padding: 5px;
    }
    .remarks {
        margin-top: 20px;
        font-size: 12px;
    }


    .dtr-summary {
        text-align: center;
        margin-top: 20px;
    }

    .dtr-summary h5 {
        text-transform: uppercase;
        font-weight: bold;
        margin: 30px 0 30px 0;
    }

    .dtr-summary-container {
        width: 90%;
        margin: auto;
        display: grid;
        grid-template-columns: repeat(3, minmax(200px, 1fr));
        text-align: left;
    }
    .dtr-summary-item {
        font-size: 14px;
        font-weight: 400;
        margin-bottom: 0px !important;
    }

    .signature {
        margin-top: 50px;
        text-align: center;
    }

    .sepe {
        width: 90%;
        height: 1px;
        background: #000;
        margin: 10px auto;
    }
    .certify {
        width: 90%;
        margin: auto;
        text-align: center
    }

    .remarks {
        margin-left: 40px;
    }

    .btn-correction {
        position: absolute;
        right: 0px;
        top: 50%;
        transform: translate(160px, -50%);
        display: flex;
        align-items: center;
    }
    /* --- FIX Bootstrap container blocking two-column print --- */
#print-section .container {
    max-width: 100% !important;
    width: 100% !important;
    padding: 0 !important;
}

/* --- Fix two copies width --- */


   .print-wrapper {
    display: flex;
    width: 100%;
    justify-content: space-between;
    gap: 0;
    padding: 10px;
    flex-wrap: nowrap;
}

.dtr-copy {
    width: 48%;
    max-width: 100%;
    padding: 5px;
}
    .center {
        text-align: center;
        font-weight: bold;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    .p-dtr-table, .p-dtr-table th, .p-dtr-table td {
        border: 1px solid #000;
        font-size: 12px;
        padding: 0px;
        text-align: center;
    }
    .info-table td {
        border: none;
        padding: 3px;
        text-align: left;
    }
    .certify {  
        font-size: 13px;
        margin-top: 15px;
        text-align: justify;
    }

    @media print {
    .print-wrapper {
        padding: 0;
        gap: 0;
    }

    .dtr-copy {
        page-break-inside: avoid;
    }

    .td-small {
        width: 10px;
    }


}

.print-area-hidden {
    visibility: hidden;
    position: absolute;
    top: -9999px;
    left: -9999px;
}

/* Dashboard card reordering */
.dashboard-card-column .dashboard-card-item {
    margin-bottom: 1rem;
}

.dashboard-sortable .card {
    position: relative;
}

.card-drag-handle {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 2;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: 1px solid #d8dee6;
    background: #fff;
    color: #6c757d;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: grab;
}

.card-drag-handle:active {
    cursor: grabbing;
}

.sortable-ghost {
    opacity: 0.5;
}

.sortable-chosen {
    transform: scale(0.995);
}

/* Expandable card body: default collapsed with max-height */
.dashboard-card-expandable-body {
    max-height: 220px;
    overflow: hidden;
    transition: max-height 0.25s ease;
}
.dashboard-card-expandable.expanded .dashboard-card-expandable-body {
    max-height: 2000px;
}
.dashboard-card-expand-toggle {
    font-size: 0.875rem;
}

/* Holiday/Special tags: force readable contrast */
.event-tag {
    color: #fff !important;
    font-weight: 600;
}
.event-tag-special {
    background-color: #6f42c1 !important;
}
.event-tag-holiday {
    background-color: #dc3545 !important;
}

/* Salary password modal */
.salary-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 1050;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.salary-modal {
    width: 100%;
    max-width: 420px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    overflow: hidden;
}
.salary-modal-header {
    padding: 0.75rem 1rem;
    background: #225F8B;
    color: #fff;
    font-weight: 700;
}
.salary-modal-body {
    padding: 1rem;
}

@media print {
    body * {
        visibility: hidden !important;
    }

    #print-section, #print-section * {
        visibility: visible !important;
    }

    #print-section {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
    }
}

@media print {
    .p-dtr-table th,
    .p-dtr-table td {
        border-right: 1px solid #000 !important;
        border-left: 1px solid #000 !important;
        border-top: 1px solid #000 !important;
        border-bottom: 1px solid #000 !important;
    }

   
}


</style>
@endpush

<div class="main-content flex-grow-1 p-4" id="employee-dashboard">
    
    <!-- Company Info -->
    <!--<div class="company-information text-uppercase mb-4">
        <h2 class="fw-bold">{{ $companyInfo->name }}</h2>
        <h5 class="fw-medium">{{ $companyInfo->address }}</h5>
        <h5 class="fw-medium">{{ $companyInfo->type->name . ' • ' . $companyInfo->contact }}</h5>
    </div>
    <hr>-->

    <!-- Dashboard Header -->
    <div class="d-lg-flex justify-content-between align-items-center mb-4">
        <div class="section-title">
            <h1>Dashboard</h1>
            <p>Track and monitor your employment records.</p>
        </div>
    </div>

    <div class="row mb-5">

    <!-- LEFT SIDE: Announcement + Payslip -->
    <div class="col-12 col-lg-6 dashboard-card-column dashboard-sortable" id="dashboard-left-column">


          <!-- Latest Announcement Card -->
    <div class="dashboard-card-item" data-card-id="latest-announcement">
    <div class="card shadow mb-3">
    <div class="card-body">
            <button type="button" class="card-drag-handle" title="Drag to reorder">
                <i class="fa-solid fa-grip-vertical"></i>
            </button>
            <h4 class="fw-bold mb-3 bg-primary text-white p-2 rounded">Latest Announcement</h4>

            @if($announcements->count())
                <div class="announcement-slider">
                    @foreach($announcements as $announcement)
                        <div class="announcement-item">
                            <a href="{{ route('employee.announcements.view', $announcement->id) }}"
                            class="text-decoration-none">
                                <p class="fw-semibold text-clamp clamp-3 mb-2">{{ $announcement->title }}</p>
                                <p class="small text-muted mb-0">{{ $announcement->created_at->format('F d, Y') }}</p>
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-primary text-center text-uppercase fw-medium">
                    No Announcements Yet
                </div>
            @endif
        </div>
    </div>
    </div>

        <div class="dashboard-card-item" data-card-id="employment-details">
        <div class="card shadow mb-3">
        <div class="card-body">
            <button type="button" class="card-drag-handle" title="Drag to reorder">
                <i class="fa-solid fa-grip-vertical"></i>
            </button>

            <h5 class="fw-bold mb-3 bg-primary text-white p-2 rounded">Employment Details</h5>

            <div class="row mb-2">
                <div class="col-6">Date Hired:</div>
                <div class="col-6 text-end fw-bold">
                    {{ $dateHired == 'N/A'? 'N/A' : \Carbon\Carbon::parse($dateHired)->format('F d, Y') }}
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-6">Position:</div>
                <div class="col-6 text-end fw-bold">
                    {{ $positionName }}
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-6">Shift Schedule:</div>
                <div class="col-6 text-end fw-bold">
                    {{ $shiftName }}
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-6">Employment Type:</div>
                <div class="col-6 text-end fw-bold">
                    {{ $employmentTypeName }}
                </div>
            </div>

           

        </div>
    </div>
    </div>

    

        <!-- Latest Payslip Card -->
        @if($latestPayslip)
        <div class="dashboard-card-item" data-card-id="latest-payslip">
        <div class="card shadow mb-4">
            <div class="card-body">
                <button type="button" class="card-drag-handle" title="Drag to reorder">
                    <i class="fa-solid fa-grip-vertical"></i>
                </button>

               {{-- <h4 class="fw-bold mb-3">Latest Payslip</h4> --}}
                <!-- Header + Toggle Button -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0">
                    Payslip for 
                    {{ \Carbon\Carbon::parse($latestPayslip->payroll_date)->format('F d, Y') }}
                </h4>

                <button wire:click="requestSalaryReveal"
                        class="btn btn-primary btn-sm">
                    {{ $showSalary ? 'Hide Salary' : 'Show Salary' }}
                </button>
            </div>
            <hr>


                {{-- Cut-off period --}}
                <div class="row mb-2">
                    <div class="col-6">Period:</div>
                    <div class="col-6 text-end">
                        {{ $latestPayslip->cut_off_period }}
                    </div>
                </div>


                <!-- Payslip Details -->
            <div class="row mb-2">
                <div class="col-6">Gross Salary:</div>
                <div class="col-6 text-end">
                     ₱{{ $showSalary ? number_format($latestPayslip->gross_amount_earned, 2) : '****' }}
                </div>
            </div>


            <div class="row mb-2">
                <div class="col-6">Total Deductions:</div>
                <div class="col-6 text-end">
                    ₱{{ $showSalary ? number_format($latestPayslip->total_deductions, 2) : '****' }}
                </div>
            </div>
             
            <div class="row mb-3">
                <div class="col-6">Net Salary:</div>
                <div class="col-6 text-end fw-bold text-success">
                    ₱{{ $showSalary ? number_format($latestPayslip->net_amount, 2) : '****' }}
                </div>
            </div>

                <a href="{{ route('employee.payslip') }}" class="btn bg-success text-white w-100 text-uppercase fw-bold">
                    View Full Payslip
                </a>
            </div>
        </div>
        </div>
        @endif

      

    </div>

    <!-- RIGHT SIDE: Leave Credits -->
    <div class="col-12 col-lg-6 dashboard-card-column dashboard-sortable" id="dashboard-right-column">

        <div class="dashboard-card-item" data-card-id="leave-credits">
        <div class="card shadow mb-4">
            <div class="card-body">
                <button type="button" class="card-drag-handle" title="Drag to reorder">
                    <i class="fa-solid fa-grip-vertical"></i>
                </button>
                <h4 class="fw-bold mb-3 bg-primary text-white p-2 rounded">
                    Leave Credits — {{ $currentMonth }} {{ now()->year }}
                </h4>

                @foreach($leaveBalances as $leave)
                    <div class="row mb-2">
                        <div class="col-7">
                            {{ $leave['name'] }} ({{ $leave['code'] }})
                        </div>
                        <div class="col-5 text-end fw-bold">
                            {{ $leave['balance'] }} days
                        </div>
                    </div>
                @endforeach

            </div>
        </div>
        </div>
        <div class="dashboard-card-item" data-card-id="upcoming-events">
        <div class="card shadow mb-4">
            <div class="card-body">
                <button type="button" class="card-drag-handle" title="Drag to reorder">
                    <i class="fa-solid fa-grip-vertical"></i>
                </button>
                <h4 class="fw-bold mb-3 bg-primary text-white p-2 rounded">
                    Upcoming Holidays & Special Events
                </h4>
                <div class="dashboard-card-expandable" data-max-lines="5">
                    <div class="dashboard-card-expandable-body">
                @if(!empty($upcomingEvents) && count($upcomingEvents) > 0)
                    <ul class="list-unstyled mb-0 fw-semibold" style="font-size: 13px;">
                        @foreach($upcomingEvents as $event)
                            <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <span>
                                    {{ $event['name'] }}
                                    <span class="badge event-tag {{ $event['is_special_event'] ? 'event-tag-special' : 'event-tag-holiday' }} ms-1">
                                        {{ $event['tag_label'] ?? ($event['is_special_event'] ? 'Special Event' : 'Holiday') }}
                                    </span>
                                    <small class="text-muted fw-normal d-block">{{ $event['type_label'] ?? $event['type'] }}</small>
                                </span>
                                <span class="text-muted small text-end">
                                    {{ $event['date_label'] }}<br>
                                    @if($event['days_away'] === 0)
                                        <strong>Today</strong>
                                    @elseif($event['days_away'] === 1)
                                        In 1 day
                                    @else
                                        In {{ $event['days_away'] }} days
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mb-0 small">No upcoming holidays or events.</p>
                @endif
                    </div>
                    <button type="button" class="btn btn-link btn-sm p-0 mt-1 dashboard-card-expand-toggle" style="display:none;">Show more</button>
                </div>
            </div>
        </div>
        </div>

        {{-- Team Timelogs card --}}
        <div class="dashboard-card-item" data-card-id="team-timelogs">
        <div class="card shadow mb-4">
            <div class="card-body">
                <button type="button" class="card-drag-handle" title="Drag to reorder">
                    <i class="fa-solid fa-grip-vertical"></i>
                </button>
                <h4 class="fw-bold mb-3 bg-primary text-white p-2 rounded">Team Timelogs — {{ now()->format('M d, Y') }}</h4>
                <div class="dashboard-card-expandable" data-max-lines="8">
                    <div class="dashboard-card-expandable-body">
                @if(!empty($teamTimelogs))
                    <ul class="list-unstyled mb-0" style="font-size: 13px;">
                        @foreach($teamTimelogs as $member)
                            <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <span class="text-truncate me-2" title="{{ $member['name'] }}">{{ $member['name'] }}</span>
                                <span class="text-end small {{ $member['status_type'] === 'leave' ? 'text-warning' : ($member['status_type'] === 'offset' ? 'text-info' : 'text-dark') }}">
                                    {{ $member['status'] }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mb-0 small">No team members or you are not assigned to a section.</p>
                @endif
                    </div>
                    <button type="button" class="btn btn-link btn-sm p-0 mt-1 dashboard-card-expand-toggle" style="display:none;">Show more</button>
                </div>
            </div>
        </div>
        </div>
       <div class="dashboard-card-item" data-card-id="today-timelogs">
       <div class="card shadow mb-4">
            <div class="card-body">
                <button type="button" class="card-drag-handle" title="Drag to reorder">
                    <i class="fa-solid fa-grip-vertical"></i>
                </button>
                <h4 class="fw-bold mb-3 bg-primary text-white p-2 rounded">
                    TimeLogs for {{ now()->format('F d, Y') }}
                </h4>

                @php
                    $showLunch = filter_var(config('app.lunch_tracking', true), FILTER_VALIDATE_BOOLEAN);
                @endphp
                <div class="row">
                    <!-- First Half -->
                    <div class="col-md-6">
                        <h6 class="fw-bold">First Half</h6>
                        <p>Clock-in: {{ $latestLogs['clock_in']->formatted_time ?? 'N/A' }}</p>
                        @if($showLunch)
                            <p>Lunch-out: {{ $latestLogs['break_out']->formatted_time ?? 'N/A' }}</p>
                        @endif
                    </div>

                    <!-- Second Half -->
                    <div class="col-md-6">
                        <h6 class="fw-bold">Second Half</h6>
                        @if($showLunch)
                            <p>Lunch-in: {{ $latestLogs['break_in']->formatted_time ?? 'N/A' }}</p>
                        @endif
                        <p>Clock-out: {{ $latestLogs['clock_out']->formatted_time ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <div class="dashboard-card-item" data-card-id="daily-time-record">
        <div class="card shadow mb-4">
    <div class="card-body">
        <button type="button" class="card-drag-handle" title="Drag to reorder">
            <i class="fa-solid fa-grip-vertical"></i>
        </button>
        <h4 class="fw-bold mb-3 bg-primary text-white p-2 rounded">
            Daily Time Record
        </h4>

        @php
            $today = now()->format('Y-m-d');
            $visibleDTR = [];

            // Keep only current and past days for the month
            foreach ($logs['dtr']['logs'] as $date => $day) {
                if ($date <= $today) {
                    $visibleDTR[$date] = $day;
                }
            }

            // Sort by date ascending
            ksort($visibleDTR);

            // Show only the last 5 days (including today) to keep the section compact
            if (count($visibleDTR) > 5) {
                $visibleDTR = array_slice($visibleDTR, -5, 5, true);
            }
        @endphp

        @php
            $showLunch = filter_var(config('app.lunch_tracking', true), FILTER_VALIDATE_BOOLEAN);
        @endphp
        <div class="dashboard-card-expandable" data-max-lines="6">
            <div class="dashboard-card-expandable-body">
        <div class="table-responsive">
            <table class="dtr-table">
                <thead>
                    <tr>
                        <th>Days</th>
                        <th colspan="{{ $showLunch ? '2' : '1' }}">First Half</th>
                        <th colspan="{{ $showLunch ? '2' : '1' }}">Second Half</th>
                        <th colspan="2">OVERTIME</th>
                        <th colspan="2">Undertime</th>
                        <th>Remark</th>
                    </tr>
                    <tr>
                        <th></th>
                        <th>In</th>
                        @if($showLunch)
                            <th>Out</th>
                        @endif
                        @if($showLunch)
                            <th>In</th>
                        @endif
                        <th>Out</th>
                        <th>Hours</th>
                        <th>Mins</th>
                        <th>Hours</th>
                        <th>Mins</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($visibleDTR as $key => $day)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($key)->format('d D') }}</td>

                            <!-- First Half -->
                            <td>{{ isset($day['clock_in']) ? \Carbon\Carbon::parse($day['clock_in'])->format('g:i A') : ' ' }}</td>
                            @if($showLunch)
                                <td>{{ isset($day['lunch_in']) ? \Carbon\Carbon::parse($day['lunch_in'])->format('g:i A') : ' ' }}</td>
                            @endif

                            <!-- Second Half -->
                            @if($showLunch)
                                <td>{{ isset($day['lunch_out']) ? \Carbon\Carbon::parse($day['lunch_out'])->format('g:i A') : ' ' }}</td>
                            @endif
                            <td>{{ isset($day['clock_out']) ? \Carbon\Carbon::parse($day['clock_out'])->format('g:i A') : ' ' }}</td>

                            <!-- Overtime -->
                            <td>{{ $day['aut']['overtime']['hours'] ?? 0 }}</td>
                            <td>{{ $day['aut']['overtime']['minutes'] ?? 0 }}</td>

                            <!-- AUT -->
                            <td>{{ $day['aut']['total_hours'] ?? 0 }}</td>
                            <td>{{ $day['aut']['total_minutes'] ?? 0 }}</td>

                            <td>
                                @if(isset($day['remarks']) && is_array($day['remarks']))
                                    {{ implode(', ', $day['remarks']) }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
            </div>
            <button type="button" class="btn btn-link btn-sm p-0 mt-1 dashboard-card-expand-toggle" style="display:none;">Show more</button>
        </div>
    </div>
</div>
        </div>

        {{-- Work anniversaries this month (sortable card) --}}
        <div class="dashboard-card-item" data-card-id="work-anniversaries">
        <div class="card shadow mb-4">
            <div class="card-body">
                <button type="button" class="card-drag-handle" title="Drag to reorder">
                    <i class="fa-solid fa-grip-vertical"></i>
                </button>
                <h4 class="fw-bold mb-2 bg-primary text-white p-2 rounded">Work anniversaries & welcome — {{ now()->format('F Y') }}</h4>
                <p class="small text-muted mb-2">Including new hires and interns this month.</p>
                <div class="dashboard-card-expandable" data-max-lines="5">
                    <div class="dashboard-card-expandable-body">
                @if(!empty($workAnniversariesThisMonth) && count($workAnniversariesThisMonth) > 0)
                    <ul class="list-unstyled mb-0 fw-semibold" style="font-size: 13px;">
                        @foreach($workAnniversariesThisMonth as $emp)
                            <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <span>
                                    {{ $emp['name'] }}
                                    @if(!empty($emp['type_label']))
                                        <span class="badge bg-success ms-1">{{ $emp['type_label'] }}</span>
                                    @endif
                                    <small class="text-muted fw-normal d-block">{{ $emp['position'] }}</small>
                                </span>
                                <span class="text-muted small">
                                    {{ $emp['date'] }}
                                    @if(!empty($emp['type_label']))
                                        · {{ $emp['type_label'] }}
                                    @elseif($emp['years'] > 0)
                                        · {{ $emp['years'] }} {{ $emp['years'] === 1 ? 'year' : 'years' }} anniversary
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mb-0 small">No work anniversaries or new hires this month.</p>
                @endif
                    </div>
                    <button type="button" class="btn btn-link btn-sm p-0 mt-1 dashboard-card-expand-toggle" style="display:none;">Show more</button>
                </div>
            </div>
        </div>
        </div>

        {{-- Birthdays this month (sortable card) --}}
        <div class="dashboard-card-item" data-card-id="birthdays">
        <div class="card shadow mb-4">
            <div class="card-body">
                <button type="button" class="card-drag-handle" title="Drag to reorder">
                    <i class="fa-solid fa-grip-vertical"></i>
                </button>
                <h4 class="fw-bold mb-3 bg-primary text-white p-2 rounded">Birthdays — {{ now()->format('F Y') }}</h4>
                <div class="dashboard-card-expandable" data-max-lines="5">
                    <div class="dashboard-card-expandable-body">
                @if(!empty($birthdaysThisMonth) && count($birthdaysThisMonth) > 0)
                    <ul class="list-unstyled mb-0 fw-semibold" style="font-size: 13px;">
                        @foreach($birthdaysThisMonth as $emp)
                            <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <span>{{ $emp['name'] }} <small class="text-muted fw-normal d-block">{{ $emp['position'] }}</small></span>
                                <span class="text-muted small">{{ $emp['date'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mb-0 small">No birthdays this month.</p>
                @endif
                    </div>
                    <button type="button" class="btn btn-link btn-sm p-0 mt-1 dashboard-card-expand-toggle" style="display:none;">Show more</button>
                </div>
            </div>
        </div>
        </div>

    </div>

    </div>

    @if($showSalaryPasswordPrompt && !$showSalary)
        <div class="salary-modal-backdrop" wire:key="salary-password-modal">
            <div class="salary-modal">
                <div class="salary-modal-header">Confirm Password</div>
                <div class="salary-modal-body">
                    <div class="small text-muted mb-2">Enter your password to view salary details.</div>
                    <input
                        type="password"
                        wire:model.defer="salaryPassword"
                        wire:keydown.enter="verifySalaryPassword"
                        class="form-control mb-2"
                        placeholder="Password"
                        autocomplete="current-password"
                        autofocus
                    >
                    @error('salaryPassword')
                        <div class="text-danger small mb-2">{{ $message }}</div>
                    @enderror
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="cancelSalaryPassword">Cancel</button>
                        <button type="button" class="btn btn-success btn-sm" wire:click="verifySalaryPassword">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function() {
    const serverOrder = @json($dashboardCardOrder);
    const storageKey = 'employeeDashboardCardOrderV2';
    const defaultLeft = ['latest-announcement', 'employment-details', 'latest-payslip'];
    const defaultRight = ['leave-credits', 'upcoming-events', 'team-timelogs', 'today-timelogs', 'daily-time-record', 'work-anniversaries', 'birthdays'];
    let dashboardObserver = null;
    let expandInitTimer = null;

    function initEmployeeDashboardCardSort() {
        const leftColumn = document.getElementById('dashboard-left-column');
        const rightColumn = document.getElementById('dashboard-right-column');
        if (!leftColumn || !rightColumn || typeof Sortable === 'undefined') return;

        function applyOrder(leftOrder, rightOrder) {
            const allCards = document.querySelectorAll('.dashboard-card-item');
            const map = {};
            allCards.forEach((item) => { map[item.dataset.cardId] = item; });
            (leftOrder || []).forEach((id) => {
                const item = map[id];
                if (item) leftColumn.appendChild(item);
            });
            (rightOrder || []).forEach((id) => {
                const item = map[id];
                if (item) rightColumn.appendChild(item);
            });
        }

        function getOrder() {
            const left = Array.from(leftColumn.querySelectorAll('.dashboard-card-item')).map((el) => el.dataset.cardId);
            const right = Array.from(rightColumn.querySelectorAll('.dashboard-card-item')).map((el) => el.dataset.cardId);
            return { left, right };
        }

        function saveOrder() {
            const { left, right } = getOrder();
            try {
                localStorage.setItem(storageKey, JSON.stringify({ left, right }));
            } catch (e) {
                // ignore storage issues
            }
            // Keep drag interactions smooth: persist client-side immediately.
            // (DB save can be reintroduced later if needed, but this avoids UI snap-back.)
        }

        var allCards = document.querySelectorAll('.dashboard-card-item');
        var cardMap = {};
        allCards.forEach(function(item) { cardMap[item.dataset.cardId] = item; });
        var clientOrder = null;
        try {
            clientOrder = JSON.parse(localStorage.getItem(storageKey) || 'null');
        } catch (e) {
            clientOrder = null;
        }

        if (clientOrder && clientOrder.left && clientOrder.right) {
            applyOrder(clientOrder.left, clientOrder.right);
        } else if (serverOrder && serverOrder.left && serverOrder.right) {
            applyOrder(serverOrder.left, serverOrder.right);
        } else {
            var leftIds = defaultLeft.filter(function(id) { return cardMap[id]; });
            var rightIds = defaultRight.filter(function(id) { return cardMap[id]; });
            var otherIds = Array.from(allCards).map(function(el) { return el.dataset.cardId; })
                .filter(function(id) { return defaultLeft.indexOf(id) === -1 && defaultRight.indexOf(id) === -1; });
            applyOrder(leftIds, rightIds.concat(otherIds));
        }

        const sortableOptions = {
            group: 'employee-dashboard-cards',
            animation: 150,
            handle: '.card-drag-handle',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            forceFallback: false,
            fallbackOnBody: true,
            onEnd: saveOrder,
        };

        // Re-init safe: apply order every time, but instantiate Sortable once per column.
        if (!leftColumn._sortableInstance) {
            leftColumn._sortableInstance = new Sortable(leftColumn, sortableOptions);
        }
        if (!rightColumn._sortableInstance) {
            rightColumn._sortableInstance = new Sortable(rightColumn, sortableOptions);
        }
    }

    function initExpandableCards() {
        var root = document.getElementById('employee-dashboard');
        var scope = root || document;
        scope.querySelectorAll('.dashboard-card-expandable').forEach(function(wrap) {
            var body = wrap.querySelector('.dashboard-card-expandable-body');
            var btn = wrap.querySelector('.dashboard-card-expand-toggle');
            if (!body || !btn) return;

            var maxH = parseInt(wrap.dataset.maxLines || '5', 10) * 32;
            body.style.maxHeight = 'none';
            var needToggle = body.scrollHeight > maxH;

            if (!needToggle) {
                wrap.classList.remove('expanded');
                body.style.maxHeight = 'none';
                btn.style.display = 'none';
                btn.onclick = null;
                return;
            }

            body.style.maxHeight = wrap.classList.contains('expanded') ? '2000px' : maxH + 'px';
            btn.style.display = 'inline-block';
            btn.textContent = wrap.classList.contains('expanded') ? 'Show less' : 'Show more';
            btn.onclick = function() {
                wrap.classList.toggle('expanded');
                btn.textContent = wrap.classList.contains('expanded') ? 'Show less' : 'Show more';
                body.style.maxHeight = wrap.classList.contains('expanded') ? '2000px' : maxH + 'px';
            };
        });
    }

    function init() {
        initEmployeeDashboardCardSort();
        setTimeout(function() { initExpandableCards(); }, 50);
    }

    function watchDashboardMutations() {
        const root = document.getElementById('employee-dashboard');
        if (!root) return;
        if (dashboardObserver) {
            dashboardObserver.disconnect();
        }

        dashboardObserver = new MutationObserver(function() {
            clearTimeout(expandInitTimer);
            expandInitTimer = setTimeout(function() {
                initExpandableCards();
            }, 60);
        });

        dashboardObserver.observe(root, { childList: true, subtree: true });
    }

    document.addEventListener('DOMContentLoaded', init);
    document.addEventListener('livewire:navigated', function() {
        setTimeout(function() {
            initEmployeeDashboardCardSort();
            initExpandableCards();
            watchDashboardMutations();
        }, 50);
    });
    document.addEventListener('DOMContentLoaded', watchDashboardMutations);
})();
</script>
