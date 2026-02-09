<div class="payslip-page-novu">
    <style>
        .payslip-page-novu .btn-primary,
        .payslip-page-novu .btn-outline-primary {
            --bs-btn-bg: #005668;
            --bs-btn-border-color: #005668;
            --bs-btn-hover-bg: #004555;
            --bs-btn-hover-border-color: #004555;
        }
        .payslip-page-novu .btn-outline-primary {
            --bs-btn-color: #005668;
            --bs-btn-hover-color: #fff;
        }
        .payslip-page-novu #cuttOffPeriod {
            color: #005668;
            font-weight: 600;
        }
        .payslip-page-novu .payslip-period-nav .btn-nav {
            min-width: 40px;
            padding: 6px 10px;
            border-width: 2px;
            border-color: #005668;
            background-color: #ffffff;
            color: #005668;
            line-height: 1;
            font-weight: 700;
            font-size: 1.1rem;
        }
        .payslip-page-novu .payslip-period-nav .btn-nav:hover {
            background-color: #005668;
            color: #ffffff;
        }
        .payslip-page-novu .period-message {
            font-size: 0.875rem;
            color: #6c757d;
            margin-left: 8px;
        }
    </style>
    <div class="mt-5">

        @if($error)
            <div class="alert alert-danger text-center text-uppercase fw-bold my-4">{{$error}}</div>
        @else
            <div class="controls py-3 d-flex justify-content-between gap-3 align-items-center">
                <div class="d-flex gap-3 flex-wrap align-items-center">
                    <div class="d-flex align-items-center payslip-period-nav">
                        <button 
                            class="btn btn-outline-primary btn-nav" 
                            wire:click="changePeriod('control', '-1')"
                            wire:loading.attr="disabled" 
                            wire:loading.class="btn-secondary"
                            title="Previous period">
                            &#8249;
                        </button>
                        
                        <div class="mx-3" id="cuttOffPeriod">
                            @php
                                $cutOffRaw = $currentPeriod->cut_off_period ?? '';
                                $cutOffDisplay = $cutOffRaw;
                                if ($cutOffRaw !== '' && str_contains($cutOffRaw, ' to ')) {
                                    $parts = array_map('trim', explode(' to ', $cutOffRaw));
                                    try {
                                        $cutOffDisplay = \Carbon\Carbon::parse($parts[0] ?? '')->format('F j') . ' to ' . \Carbon\Carbon::parse($parts[1] ?? $parts[0])->format('F j, Y');
                                    } catch (\Throwable $e) {
                                        $cutOffDisplay = $cutOffRaw;
                                    }
                                } elseif ($cutOffRaw !== '') {
                                    try {
                                        $cutOffDisplay = \Carbon\Carbon::parse($cutOffRaw)->format('F j, Y');
                                    } catch (\Throwable $e) {}
                                }
                            @endphp
                            {{ $cutOffDisplay ?: \Carbon\Carbon::parse($currentPeriod->payroll_date)->format('F d, Y') }}
                        </div>
                                                
                        <button 
                            class="btn btn-outline-primary btn-nav" 
                            wire:click="changePeriod('control', '1')"
                            wire:loading.attr="disabled" 
                            wire:loading.class="btn-secondary"
                            title="Next period">
                            &#8250;
                        </button>
                        @if($periodMessage)
                            <span class="period-message">{{ $periodMessage }}</span>
                        @endif
                    </div>
                </div>
                @if($requestStatus && $requestStatus == 'pending')
                    <button class="btn btn-danger text-uppercase fw-bold px-4 py-3">Request already Submitted</button>
                @elseif($requestStatus && $requestStatus === 'approved')
                    <button type="button" class="btn btn-primary px-5 py-3 text-uppercase fw-bold" wire:click="download">
                        <span wire:loading.remove wire:target="download">
                            Download Payslip <i class="fa-solid fa-download ms-2"></i>
                        </span>
                        <span wire:loading wire:target="download">
                            Downloading <i class="fa-solid fa-spinner ms-2 fa-spin"></i>
                        </span>
                    </button>
                @else
                    <button type="button" class="btn btn-primary px-5 py-3 text-uppercase fw-bold" wire:click="request">
                        <span wire:loading.remove wire:target="request">
                            Request Download <i class="fa-solid fa-file-arrow-down ms-2"></i>
                        </span>
                        <span wire:loading wire:target="request">
                            Sending Request <i class="fa-solid fa-spinner ms-2 fa-spin"></i>
                        </span>
                    </button>
                @endif
            </div>
            <div class="w-100">
                @include('employee.payslip-main', [
                    'payslip' => $payroll
                ])
            </div>
        @endif
    </div>
</div>