@section('style')
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
        display: block;
    }

    .dtr {
        width: 800px;
        margin: 10px 0 50px 0;
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

    .dtr-copy img {
    position: static !important;
    display: block;
    margin: 0 auto 10px auto;
    height: 70px !important;
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
        font-size: 11px !important;
    }

    .dtr-table th, .dtr-table td {
        border: 1px solid black;
        text-align: center;
        padding: 4px;
        font-size: 11px; 
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
        font-size: 13px;
        font-weight: 600 ;
        margin-bottom: 0px !important;
        text-transform: uppercase;
        color: #000000c5;
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

     /* force two equal columns */
    .dtr-copy {
        width: 48% !important;
        display: inline-block !important;
        vertical-align: top !important;
        page-break-inside: avoid !important;
    }

    /* keep remarks column visible & fixed width */
    th.remarks-col,
    td.remarks-col {
        min-width: 70px !important;
        max-width: 70px !important;
        width: 70px !important;
        display: table-cell !important;
        visibility: visible !important;
        white-space: normal !important;
    }

    /* prevent bootstrap and flex from squeezing tables */
    .p-dtr-table {
        table-layout: fixed !important;
    }

    /* force page to scale the content instead of cutting it */
    body {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        transform: scale(0.83);        /* ← adjust until everything fits */
        transform-origin: top left;
    }

    /* wrapper resets so scaling works full width */
    .print-wrapper {
        width: 100% !important;
        display: flex !important;
        justify-content: space-between !important;
        gap: 0 !important;
        padding: 0 !important;
    }

    /* each DTR copy */
    .dtr-copy {
        width: 49% !important;             /* slightly wider than before */
        min-width: 49% !important;
        max-width: 49% !important;
        page-break-inside: avoid !important;
    }

    /* force remarks column size */
    .remarks-col {
        width: 70px !important;
        min-width: 70px !important;
        max-width: 70px !important;
        white-space: normal !important;
    }

    /* prevent table from collapsing */
    .p-dtr-table {
        table-layout: fixed !important;
        border-collapse: collapse !important;
    }

    .p-dtr-table th,
    .p-dtr-table td {
        padding: 2px !important;
        font-size: 11px !important;
    }

   
}


</style>
@endsection
<div class="mahcon">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mt-5 mb-4">
            <div class="section-title">
                <h1>Daily Time Record <span  class="text-primary">{{ $employee_no }}</span></h1>
            </div>
           
        </div>
        <div class="mt-3">
            @if($logs)
                <div class="py-3 d-flex justify-content-between gap-3 align-items-center">
                    <div class="d-flex gap-3">
                        <div class="d-flex align-items-center">
                            <button 
                                class="btn btn-sm btn-outline-primary" 
                                wire:click="changeMonth('control', '-1')"
                                wire:loading.attr="disabled" 
                                wire:loading.class="btn-secondary">
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>
                            
                            <div class="mx-3" id="monthYear">
                                {{ \Carbon\Carbon::parse($dtrDate)->format('F, Y') }}
                            </div>
                            
                            <button 
                                class="btn btn-sm btn-outline-primary" 
                                wire:click="changeMonth('control', '1')"
                                wire:loading.attr="disabled" 
                                wire:loading.class="btn-secondary"
                                @disabled($dtrDate == now()->format('F, Y'))>
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                        <div>
                            <input type="month" wire:change="changeMonth('date')" wire:model="monthDate" class="form-control">
                        </div>
                    </div>
                   
                    <div class="d-flex justify-content-end mt-3 gap-2">
                        <button 
                            type="button" 
                            class="btn btn-info text-white" 
                            wire:click="showLogs"
                            wire:loading.attr="disabled"
                            wire:loading.class="btn-secondary">
                            <i class="fa-solid fa-images me-2"></i>
                            View Clock-In Images
                        </button>
                        <button class="btn btn-primary save-as-pdf">
                            <i class="fa-solid fa-print me-2"></i>
                            Print DTR
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <div class="container">
        @if($logs)
            @if(!$hasLeaveCard && config('app.product') === 'government')
                <div class="warning mt-5 mb-3">
                    <div class="alert alert-info fw-bold text-center" role="alert">
                        <p class="m-0 text-uppercase">No Leave Card Detected</p>
                        <small class="text-uppercase" style="font-size: 12px;">
                            <a href="{{route('leave.show', ['leave' => 1, 'employee' => $employee_no])}}">Click here to add</a>
                        </small>
                    </div>
                </div>
            @endif
            <div class="print-container mt-4 ssss">
                 @php
                    $isAdmin = false;
                @endphp
                @include('livewire.admin.reports.daily-time-record.employee.dtr-table')
            </div>
        @else
            <div class="alert alert-danger" role="alert">
                @if (!empty($errors))
                    <ul class="m-0">
                        @foreach ($errors as $error)
                            <li class="text-uppercase">{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    </div>
      <div id="print-section" class="print-wrapper print-area-hidden">
         @if($logs)
        @include('livewire.admin.reports.daily-time-record.employee.print-dtr-table')
         @else
            <div class="alert alert-danger" role="alert">
                @if (!empty($errors))
                    <ul class="m-0">
                        @foreach ($errors as $error)
                            <li class="text-uppercase">{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    </div>

    <!-- Logs Modal -->
    <div class="modal fade" wire:ignore.self id="logs_modal" tabindex="-1" aria-labelledby="logsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable logs-modal-dialog modal-fullscreen-md-down">
            <div class="modal-content logs-modal-content">
                <div class="modal-header border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <div class="logs-modal-icon">
                            <i class="fa-solid fa-images"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="logsModalLabel">
                                Clock-In Images
                            </h5>
                            <small class="text-muted">{{ \Carbon\Carbon::parse($dtrDate)->format('F Y') }}</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    @if (!empty($modalLogs))
                        <div class="logs-container">
                            @foreach($modalLogs as $item)
                                @php
                                    $dateKey = str_replace('/', '-', $item['date']);
                                    $logList = $item['logs'] ?? [];
                                    $accomplishment = collect($logList)->firstWhere('accomplishment');
                                    $hasImage = collect($logList)->contains(fn($log) => !empty($log['captured_image']));
                                    $showLunch = filter_var(config('app.lunch_tracking', true), FILTER_VALIDATE_BOOLEAN);
                                    $filledIndexes = collect($logList)->keys()->filter(fn($i) => !empty($logList[$i]['time'] ?? null))->values();
                                    $outIndex = $filledIndexes->count() >= 2 ? $filledIndexes->last() : null;
                                    $dateFormatted = \Carbon\Carbon::createFromFormat('j/n/Y', $item['date']);
                                @endphp

                                <div class="log-day-card" data-date="{{ $dateKey }}">
                                    <div class="log-day-header" data-bs-toggle="collapse" data-bs-target="#collapse{{ $dateKey }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                                        <div class="d-flex align-items-center justify-content-between w-100">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="log-day-badge">
                                                    <i class="fa-solid fa-calendar-day"></i>
                                                </div>
                                                <div>
                                                    <div class="log-day-title">{{ $dateFormatted->format('l') }}</div>
                                                    <div class="log-day-date">{{ $dateFormatted->format('F d, Y') }}</div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="log-times-preview">
                                                    @if(isset($logList[0]['time']))
                                                        <span class="time-badge time-in">
                                                            <i class="fa-solid fa-arrow-right-to-bracket me-1"></i>
                                                            {{ \Carbon\Carbon::parse($logList[0]['time'])->format('h:i A') }}
                                                        </span>
                                                    @endif
                                                    @if($outIndex !== null && isset($logList[$outIndex]['time']))
                                                        <span class="time-badge time-out">
                                                            <i class="fa-solid fa-arrow-right-from-bracket me-1"></i>
                                                            {{ \Carbon\Carbon::parse($logList[$outIndex]['time'])->format('h:i A') }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <i class="fa-solid fa-chevron-down log-chevron"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="collapse{{ $dateKey }}" class="collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent=".logs-container">
                                        <div class="log-day-body">
                                            <div class="row g-3">
                                                <!-- Clock In -->
                                                <div class="col-12 {{ $showLunch ? 'col-md-6 col-lg-3' : 'col-md-6' }}">
                                                    <div class="time-log-card">
                                                        <div class="time-log-header">
                                                            <i class="fa-solid fa-arrow-right-to-bracket text-success"></i>
                                                            <span class="fw-semibold">Clock In</span>
                                                        </div>
                                                        <div class="time-log-time">
                                                            @if(isset($logList[0]['time']))
                                                                {{ \Carbon\Carbon::parse($logList[0]['time'])->format('h:i A') }}
                                                            @else
                                                                <span class="text-muted">Not recorded</span>
                                                            @endif
                                                        </div>
                                                        @if (!empty($logList[0]['captured_image']))
                                                            <div class="time-log-image">
                                                                @php
                                                                    if (env('USE_S3_STORAGE', false)) {
                                                                        $clockInUrl = Storage::disk('s3')->temporaryUrl(
                                                                            'timelogs/' . $logList[0]['captured_image'],
                                                                            now()->addMinutes(60)
                                                                        );
                                                                    } else {
                                                                        $clockInUrl = Storage::url('timelogs/' . $logList[0]['captured_image']);
                                                                    }
                                                                @endphp
                                                                <a data-fancybox="gallery-{{ $dateKey }}" data-src="{{ $clockInUrl }}" class="image-link">
                                                                    <img src="{{ $clockInUrl }}" alt="Clock In" class="log-image">
                                                                    <div class="image-overlay">
                                                                        <i class="fa-solid fa-expand"></i>
                                                                    </div>
                                                                </a>
                                                            </div>
                                                        @else
                                                            <div class="time-log-image no-image">
                                                                <div class="no-image-placeholder">
                                                                    <i class="fa-solid fa-image"></i>
                                                                    <span>No Image</span>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                @if($showLunch)
                                                    <!-- Lunch Out -->
                                                    <div class="col-12 col-md-6 col-lg-3">
                                                        <div class="time-log-card">
                                                            <div class="time-log-header">
                                                                <i class="fa-solid fa-utensils text-warning"></i>
                                                                <span class="fw-semibold">Lunch Out</span>
                                                            </div>
                                                            <div class="time-log-time">
                                                                @if(isset($logList[1]['time']))
                                                                    {{ \Carbon\Carbon::parse($logList[1]['time'])->format('h:i A') }}
                                                                @else
                                                                    <span class="text-muted">Not recorded</span>
                                                                @endif
                                                            </div>
                                                            @if (!empty($logList[1]['captured_image']))
                                                                <div class="time-log-image">
                                                                    <a data-fancybox="gallery-{{ $dateKey }}" data-src="{{ Storage::url('timelogs/' . $logList[1]['captured_image']) }}" class="image-link">
                                                                        <img src="{{ Storage::url('timelogs/' . $logList[1]['captured_image']) }}" alt="Lunch Out" class="log-image">
                                                                        <div class="image-overlay">
                                                                            <i class="fa-solid fa-expand"></i>
                                                                        </div>
                                                                    </a>
                                                                </div>
                                                            @else
                                                                <div class="time-log-image no-image">
                                                                    <div class="no-image-placeholder">
                                                                        <i class="fa-solid fa-image"></i>
                                                                        <span>No Image</span>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <!-- Lunch In -->
                                                    <div class="col-12 col-md-6 col-lg-3">
                                                        <div class="time-log-card">
                                                            <div class="time-log-header">
                                                                <i class="fa-solid fa-utensils text-info"></i>
                                                                <span class="fw-semibold">Lunch In</span>
                                                            </div>
                                                            <div class="time-log-time">
                                                                @if(isset($logList[2]['time']))
                                                                    {{ \Carbon\Carbon::parse($logList[2]['time'])->format('h:i A') }}
                                                                @else
                                                                    <span class="text-muted">Not recorded</span>
                                                                @endif
                                                            </div>
                                                            @if (!empty($logList[2]['captured_image']))
                                                                <div class="time-log-image">
                                                                    <a data-fancybox="gallery-{{ $dateKey }}" data-src="{{ Storage::url('timelogs/' . $logList[2]['captured_image']) }}" class="image-link">
                                                                        <img src="{{ Storage::url('timelogs/' . $logList[2]['captured_image']) }}" alt="Lunch In" class="log-image">
                                                                        <div class="image-overlay">
                                                                            <i class="fa-solid fa-expand"></i>
                                                                        </div>
                                                                    </a>
                                                                </div>
                                                            @else
                                                                <div class="time-log-image no-image">
                                                                    <div class="no-image-placeholder">
                                                                        <i class="fa-solid fa-image"></i>
                                                                        <span>No Image</span>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif

                                                <!-- Clock Out -->
                                                <div class="col-12 {{ $showLunch ? 'col-md-6 col-lg-3' : 'col-md-6' }}">
                                                    <div class="time-log-card">
                                                        <div class="time-log-header">
                                                            <i class="fa-solid fa-arrow-right-from-bracket text-danger"></i>
                                                            <span class="fw-semibold">Clock Out</span>
                                                        </div>
                                                        <div class="time-log-time">
                                                            @if($outIndex !== null && isset($logList[$outIndex]['time']))
                                                                {{ \Carbon\Carbon::parse($logList[$outIndex]['time'])->format('h:i A') }}
                                                            @else
                                                                <span class="text-muted">Not recorded</span>
                                                            @endif
                                                        </div>
                                                        @if ($outIndex !== null && !empty($logList[$outIndex]['captured_image']))
                                                            <div class="time-log-image">
                                                                <a data-fancybox="gallery-{{ $dateKey }}" data-src="{{ Storage::url('timelogs/' . $logList[$outIndex]['captured_image']) }}" class="image-link">
                                                                    <img src="{{ Storage::url('timelogs/' . $logList[$outIndex]['captured_image']) }}" alt="Clock Out" class="log-image">
                                                                    <div class="image-overlay">
                                                                        <i class="fa-solid fa-expand"></i>
                                                                    </div>
                                                                </a>
                                                            </div>
                                                        @else
                                                            <div class="time-log-image no-image">
                                                                <div class="no-image-placeholder">
                                                                    <i class="fa-solid fa-image"></i>
                                                                    <span>No Image</span>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            @if(!empty($accomplishment))
                                                <div class="accomplishment-card mt-2 mb-2">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <i class="fa-solid fa-link text-primary"></i>
                                                        <span class="fw-semibold">Accomplishment Report</span>
                                                    </div>
                                                    @php
                                                        $accVal = $accomplishment['accomplishment'] ?? '';
                                                        $isUrl = is_string($accVal) && (str_starts_with($accVal, 'http://') || str_starts_with($accVal, 'https://'));
                                                    @endphp
                                                    @if($isUrl)
                                                        <a href="{{ $accVal }}" target="_blank" rel="noopener" class="accomplishment-link">
                                                            <i class="fa-solid fa-external-link-alt me-2"></i>Open link
                                                        </a>
                                                    @else
                                                        <a href="{{ Storage::url('accomplishments/' . $accVal) }}" download class="accomplishment-link">
                                                            <i class="fa-solid fa-download me-2"></i>{{ $accVal }}
                                                        </a>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fa-solid fa-images"></i>
                            </div>
                            <h6 class="empty-state-title">No Clock Logs Found</h6>
                            <p class="empty-state-text">No clock logs are available for this period.</p>
                        </div>
                    @endif
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="fa-solid fa-times me-2"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Modern Logs Modal Styles */
        .logs-modal-dialog {
            max-width: 800px;
            margin: 1.75rem auto;
        }

        @media (min-width: 992px) {
            .logs-modal-dialog {
                max-width: 800px;
            }
        }

        .logs-modal-content {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }

        .logs-modal-content .modal-header {
            background: linear-gradient(135deg, #225f8b 0%, #005668 100%);
            color: white;
            padding: 1.25rem 1.5rem;
            border: none;
        }

        .logs-modal-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .logs-modal-content .modal-title {
            color: white;
            font-size: 1.1rem;
        }

        .logs-modal-content .modal-header .text-muted {
            color: rgba(255, 255, 255, 0.8) !important;
            font-size: 0.875rem;
        }

        .logs-modal-content .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.9;
        }

        .logs-container {
            padding: 1rem;
        }

        .log-day-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            margin-bottom: 1rem;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .log-day-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #225f8b;
        }

        .log-day-header {
            padding: 1.25rem 1.5rem;
            background: var(--bs-light, #f8f9fa);
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .log-day-header:hover {
            background: #e9ecef;
        }

        .log-day-header[aria-expanded="true"] .log-chevron {
            transform: rotate(180deg);
        }

        .log-day-badge {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #225f8b 0%, #005668 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }

        .log-day-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--bs-dark, #212529);
            margin-bottom: 0.25rem;
        }

        .log-day-date {
            font-size: 0.875rem;
            color: #6c757d;
        }

        .log-times-preview {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .time-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }

        .time-badge.time-in {
            background: rgba(25, 135, 84, 0.15);
            color: #198754;
        }

        .time-badge.time-out {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }

        .log-chevron {
            color: #6c757d;
            transition: transform 0.3s ease;
            font-size: 0.875rem;
        }

        .log-day-body {
            padding: 1.5rem;
            background: white;
        }

        .time-log-card {
            background: var(--bs-light, #f8f9fa);
            border-radius: 10px;
            padding: 1rem;
            height: 100%;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }

        .time-log-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #225f8b;
        }

        .time-log-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
            color: #495057;
        }

        .time-log-header i {
            font-size: 1rem;
        }

        .time-log-time {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--bs-dark, #212529);
            margin-bottom: 0.75rem;
        }

        .time-log-image {
            position: relative;
            width: 100%;
            padding-top: 75%;
            border-radius: 8px;
            overflow: hidden;
            background: #e9ecef;
        }

        .time-log-image .image-link {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: block;
            cursor: pointer;
        }

        .log-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .image-link:hover .log-image {
            transform: scale(1.05);
        }

        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .image-link:hover .image-overlay {
            opacity: 1;
        }

        .image-overlay i {
            color: white;
            font-size: 1.5rem;
        }

        .no-image-placeholder {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: #adb5bd;
        }

        .no-image-placeholder i {
            font-size: 2rem;
            display: block;
            margin-bottom: 0.5rem;
        }

        .no-image-placeholder span {
            font-size: 0.875rem;
        }

        .accomplishment-card {
            background: rgba(144, 4, 209, 0.1);
            border: 1px solid #9004d1;
            border-radius: 10px;
            padding: 1rem;
        }

        .accomplishment-link {
            color: #9004d1;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: color 0.2s ease;
        }

        .accomplishment-link:hover {
            color: #6d03a0;
            text-decoration: underline;
        }

        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
        }

        .empty-state-icon {
            width: 80px;
            height: 80px;
            background: #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: #adb5bd;
            font-size: 2rem;
        }

        .empty-state-title {
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .empty-state-text {
            color: #6c757d;
            margin: 0;
        }

        .logs-modal-content .modal-footer {
            padding: 1rem 1.5rem;
            background: var(--bs-light, #f8f9fa);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .logs-container {
                padding: 0.75rem;
            }

            .log-day-header {
                padding: 1rem;
            }

            .log-day-body {
                padding: 1rem;
            }

            .log-day-badge {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }

            .log-day-title {
                font-size: 0.9375rem;
            }

            .log-day-date {
                font-size: 0.8125rem;
            }

            .log-times-preview {
                flex-direction: column;
                gap: 0.25rem;
            }

            .time-log-card {
                padding: 0.75rem;
            }

            .time-log-time {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 576px) {
            .logs-modal-content .modal-header {
                padding: 1rem;
            }

            .logs-modal-icon {
                width: 36px;
                height: 36px;
                font-size: 1rem;
            }

            .logs-modal-content .modal-title {
                font-size: 1rem;
            }

            .log-day-header {
                padding: 0.875rem;
            }

            .log-day-body {
                padding: 0.875rem;
            }
        }
    </style>

<script>
function printDTR() {
    window.print();
}

// Listen for Livewire event to show modal
document.addEventListener('livewire:init', () => {
    Livewire.on('showModal', (data) => {
        if (data.modal === 'logs_modal') {
            const modalElement = document.getElementById('logs_modal');
            if (modalElement) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
                modal.show();
            }
        }
    });
});

// Fallback for Livewire 2.x
if (typeof Livewire === 'undefined' || !window.Livewire) {
    window.addEventListener('livewire:load', () => {
        Livewire.on('showModal', (data) => {
            if (data.modal === 'logs_modal') {
                const modalElement = document.getElementById('logs_modal');
                if (modalElement) {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
                    modal.show();
                }
            }
        });
    });
}

// Initialize Fancybox when modal is shown
document.addEventListener('DOMContentLoaded', function() {
    const logsModal = document.getElementById('logs_modal');
    if (logsModal) {
        logsModal.addEventListener('shown.bs.modal', function() {
            // Re-initialize Fancybox for images in the modal
            if (typeof Fancybox !== 'undefined') {
                Fancybox.bind('[data-fancybox]', {
                    Toolbar: {
                        display: {
                            left: ['infobar'],
                            middle: [],
                            right: ['slideshow', 'download', 'thumbs', 'close'],
                        },
                    },
                    Thumbs: {
                        autoStart: false,
                    },
                });
            }

            // Handle collapse events for chevron rotation
            const collapseElements = logsModal.querySelectorAll('.collapse');
            collapseElements.forEach(function(collapse) {
                collapse.addEventListener('show.bs.collapse', function() {
                    const header = this.previousElementSibling;
                    if (header) {
                        header.setAttribute('aria-expanded', 'true');
                    }
                });
                collapse.addEventListener('hide.bs.collapse', function() {
                    const header = this.previousElementSibling;
                    if (header) {
                        header.setAttribute('aria-expanded', 'false');
                    }
                });
            });
        });
    }
});
</script>
</div>
