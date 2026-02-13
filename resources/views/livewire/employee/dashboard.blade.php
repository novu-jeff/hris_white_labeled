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

<div class="main-content flex-grow-1 p-4" >
    
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
                <div class="col-6">Break Time Hours:</div>
                <div class="col-6 text-end fw-bold">
                    {{ $breaktime }}
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

                <button wire:click="toggleSalary"
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

                @if(!empty($upcomingEvents) && count($upcomingEvents) > 0)
                    <ul class="list-unstyled mb-0 fw-semibold" style="font-size: 13px;">
                        @foreach($upcomingEvents as $event)
                            <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <span>
                                    {{ strtoupper($event['name']) }}
                                    <span class="badge {{ $event['is_special_event'] ? 'bg-warning text-dark' : 'bg-danger' }} ms-1">
                                        {{ $event['is_special_event'] ? 'Special Event' : 'Holiday' }}
                                    </span>
                                    <small class="text-muted fw-normal d-block">{{ strtoupper($event['type']) }}</small>
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
</div>
        </div>

        {{-- Work anniversaries & birthdays this month --}}
        <div class="row">
            <div class="col-12 col-md-6 mb-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h4 class="fw-bold mb-2 bg-primary text-white p-2 rounded">Work anniversaries & welcome — {{ now()->format('F Y') }}</h4>
                        <p class="small text-muted mb-3">Including new hires and interns this month.</p>
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
                                        <span class="text-muted small">{{ $emp['date'] }} @if($emp['years'] > 0)({{ $emp['years'] }}y)@endif</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mb-0 small">No work anniversaries or new hires this month.</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 mb-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h4 class="fw-bold mb-3 bg-primary text-white p-2 rounded">Birthdays — {{ now()->format('F Y') }}</h4>
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
                </div>
            </div>
        </div>

    </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
function initEmployeeDashboardCardSort() {
    const leftColumn = document.getElementById('dashboard-left-column');
    const rightColumn = document.getElementById('dashboard-right-column');
    if (!leftColumn || !rightColumn || typeof Sortable === 'undefined') return;
    if (leftColumn.dataset.sortableReady === '1' && rightColumn.dataset.sortableReady === '1') return;

    const storageKey = 'employeeDashboardCardOrderV1';

    function applyOrder(container, order) {
        if (!Array.isArray(order)) return;
        const map = {};
        container.querySelectorAll('.dashboard-card-item').forEach((item) => {
            map[item.dataset.cardId] = item;
        });
        order.forEach((id) => {
            const item = map[id];
            if (item) container.appendChild(item);
        });
    }

    function saveOrder() {
        const left = Array.from(leftColumn.querySelectorAll('.dashboard-card-item')).map((el) => el.dataset.cardId);
        const right = Array.from(rightColumn.querySelectorAll('.dashboard-card-item')).map((el) => el.dataset.cardId);
        localStorage.setItem(storageKey, JSON.stringify({ left, right }));
    }

    try {
        const stored = JSON.parse(localStorage.getItem(storageKey) || '{}');
        applyOrder(leftColumn, stored.left);
        applyOrder(rightColumn, stored.right);
    } catch (e) {
        // Ignore invalid local storage content.
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

    new Sortable(leftColumn, sortableOptions);
    new Sortable(rightColumn, sortableOptions);
    leftColumn.dataset.sortableReady = '1';
    rightColumn.dataset.sortableReady = '1';
}

document.addEventListener('DOMContentLoaded', initEmployeeDashboardCardSort);
document.addEventListener('livewire:navigated', initEmployeeDashboardCardSort);
</script>
