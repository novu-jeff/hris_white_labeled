<div>
    <div class="clockinout">
        <div class="row">
            <!-- <div class="col-12 col-md-4 mb-5">
                <label for="user" class="mb-3">Manipulate time for testing</label>
                <input type="time" class="form-control" wire:model="manipulate_timestamp">
                <button wire:click="delete" class="btn btn-danger mt-3">Delete Record</button>
            </div> -->
            <div class="col-12 col-md-12 mb-3 mb-3">
                <div class="row capture-content g-3">
                    <div class="col-12 col-md-5 order-2 order-md-1">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="text-muted text-uppercase fw-bold" style="font-size: 12px;">Next action</div>
                                        <div class="fw-bold text-uppercase" style="font-size: 22px;">
                                            {{$status}}
                                        </div>
                                        <div class="text-muted fst-italic mt-1" style="font-size: 12px;">
                                            We’ll capture your camera photo when you proceed.
                                        </div>

                                        <button type="button"
                                            data-status="{{ $status }}"
                                            class="clock-process btn btn-lg w-100 text-uppercase fw-bold px-4 py-3 mt-3
                                                {{ in_array($status, ['Clock In', 'Clock Out']) ? 'btn-primary' : '' }}
                                                {{ $status === 'Done' ? 'btn-danger' : '' }}
                                            ">
                                            <span>{{ $status === 'Done' ? 'Done' : 'Capture & Proceed' }}</span>
                                        </button>
                                    </div>
                                </div>
                                @php
                                    $showLunch = filter_var(config('app.lunch_tracking', true), FILTER_VALIDATE_BOOLEAN);
                                @endphp
                                @if ($showLunch && in_array($status, ['Lunch Out']))
                                    <div class="text-center mt-3">
                                        <button style="border-radius: 15px" class="clock-process-forced btn btn-primary border-3 w-100 py-3 text-uppercase fw-bold">
                                            Clock Out
                                        </button>
                                    </div>     
                                @endif       
                            </div>
                            <div class="col-12 mb-3">
                                <div class="card border-3 bg-dark text-white w-100" wire:click="showLogs" wire:target="showLogs">
                                    <div class="card-body d-flex align-items-center justify-content-center">
                                        <div class="text-center">
                                            <div class="d-flex justify-content-center">
                                                <span wire:loading.remove wire:target="showLogs">
                                                    <i class="fa-regular fa-calendar-check"></i>
                                                </span>
                                                <span wire:loading wire:target="showLogs">
                                                    <i class="fa-solid fa-spinner fa-spin"></i>
                                                </span>
                                            </div>
                                            <div class="fw-bold text-uppercase mt-1">
                                                <span wire:loading.remove wire:target="showLogs">Clock Logs</span>
                                                <span wire:loading wire:target="showLogs">Please Wait...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-7 order-1 order-md-2">
                        <div class="camera d-flex justify-content-center align-items-center w-100">
                            <video id="video" autoplay muted playsinline></video>
                            <canvas id="canvas"></canvas>
                            <div class="alert-container">
                                
                            </div>
                            <div class="watermark">
                                <img src="{{ asset('/img/' . $provider['client_logo']) }}">            
                            </div>
                            
                        </div>
                        <div class="text-muted text-center text-muted text-uppercase mt-3 fst-italic">
                            <small>Please ensure your face is clearly visible before proceeding.</small>
                        </div>
                    </div>
                </div>
                <div class="row capture-preview">
                    <div class="col-12">
                        <img src="" alt="" srcset="">
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                            <small class="text-muted">{{ \Carbon\Carbon::now()->format('F Y') }}</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    @if (!empty($logs))
                        <div class="logs-container">
                            @foreach($logs as $item)
                                @php
                                    $dateKey = str_replace('/', '-', $item['date']);
                                    $logList = $item['logs'] ?? [];
                                    $accomplishment = collect($logList)->firstWhere('accomplishment');
                                    $hasImage = collect($logList)->contains(fn($log) => !empty($log['captured_image']));
                                    $showLunch = filter_var(config('app.lunch_tracking', true), FILTER_VALIDATE_BOOLEAN);
                                    $outIndex = $showLunch ? 3 : 1;
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
                                                    @if(isset($logList[$outIndex]['time']))
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
                                                                    @php
                                                                        $lunchOutUrl = Storage::disk('s3')->temporaryUrl(
                                                                            'timelogs/' . $logList[1]['captured_image'],
                                                                            now()->addMinutes(60)
                                                                        );
                                                                    @endphp
                                                                    <a data-fancybox="gallery-{{ $dateKey }}" data-src="{{ $lunchOutUrl }}" class="image-link">
                                                                        <img src="{{ $lunchOutUrl }}" alt="Lunch Out" class="log-image">
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
                                                            @if(isset($logList[$outIndex]['time']))
                                                                {{ \Carbon\Carbon::parse($logList[$outIndex]['time'])->format('h:i A') }}
                                                            @else
                                                                <span class="text-muted">Not recorded</span>
                                                            @endif
                                                        </div>
                                                        @if (!empty($logList[$outIndex]['captured_image']))
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
                                                <div class="accomplishment-card mt-3">
                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                        <i class="fa-solid fa-file-pdf text-danger"></i>
                                                        <span class="fw-semibold">Accomplishment Report</span>
                                                    </div>
                                                    <a href="{{ Storage::url('accomplishments/' . $accomplishment['accomplishment']) }}" download class="accomplishment-link">
                                                        <i class="fa-solid fa-download me-2"></i>
                                                        {{ $accomplishment['accomplishment'] }}
                                                    </a>
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
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }

            .logs-modal-content .modal-title {
                font-size: 1rem;
            }

            .log-day-header {
                padding: 0.75rem;
            }

            .log-day-body {
                padding: 0.75rem;
            }
        }
    </style>



    <div class="modal fade" wire:ignore.self id="clockInModal" tabindex="-1" aria-labelledby="clockInModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <form wire:submit.prevent="{{ $requires_accomplishment ? 'saveAccomplishment' : 'triggerClock' }}" enctype="multipart/form-data">

                <div class="modal-header border-0 px-3 pt-3 pb-2">
                    <h5 class="modal-title text-uppercase fw-bold" id="clockInModalLabel">Captured Image Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body px-3">
                    {{-- Captured image preview --}}
                    <div class="mb-3" wire:ignore>
                        <img id="clockInPreviewImage" src="" alt="Captured Image" class="img-fluid rounded shadow clock-preview-img">
                    </div>

                    {{-- Capture details (date/time) --}}
                    <div class="card border-0 bg-light mb-3">
                        <div class="card-body py-3">
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="text-muted text-uppercase fw-bold" style="font-size: 12px;">Captured</div>
                                    <div class="fw-semibold">
                                        {{ $captured_at ? \Carbon\Carbon::parse($captured_at)->format('M d, Y • h:i A') : '—' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($requires_accomplishment)
                        {{-- Accomplishment file input --}}
                        <div class="mb-3">
                            <label for="accomplishmentFile" class="text-start">Accomplishment Report (PDF)</label>
                            <input
                                type="file"
                                wire:model="upload_accomplishment"
                                id="accomplishmentFile"
                                class="form-control"
                                accept="application/pdf"
                            />

                            @error('upload_accomplishment')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror


                            {{-- Temporary preview link --}}
                            @if($upload_accomplishment)
                                <p class="mt-2">
                                    Selected File: 
                                    
                                      <strong>{{ $upload_accomplishment->getClientOriginalName() }}</strong>
                                  
                                </p>
                            @endif
                        </div>
                    @endif
                </div>

                <div wire:ignore class="modal-footer border-0 d-flex gap-3 justify-content-center align-items-center flex-wrap px-3 pb-3">
                    <button type="button" class="retakeButton btn btn-outline-danger py-3 px-5 text-uppercase fw-bold">
                        Retake
                    </button>

                    <button type="submit"
                        class="btn btn-primary py-3 px-5 text-uppercase fw-bold d-flex align-items-center gap-2"
                        wire:target="{{ $requires_accomplishment ? 'saveAccomplishment' : 'triggerClock' }}"
                        wire:loading.attr="disabled">
                        <span>Proceed</span>
                        <span wire:loading wire:target="{{ $requires_accomplishment ? 'saveAccomplishment' : 'triggerClock' }}">
                            <i class="fa-solid fa-spinner fa-spin"></i>
                        </span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

</div>

<script type="module">
    $(function() {
        initializeClockFace();
    })
</script>
