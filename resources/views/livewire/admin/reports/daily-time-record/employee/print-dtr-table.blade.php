{{-- TWO COPIES (LEFT + RIGHT) --}}

@for ($copy = 1; $copy <= 2; $copy++)
<div class="dtr-copy">

     <div class="center">
         @if($product == 'government')
            <div>
                <img src="{{ asset('/img/' . $provider['client_logo']) }}" style="width: 80px; height: auto;">            
            </div>
        @else
            <div>
                <img src="{{ asset('/img/' . $provider['client_logo']) }}" style="width: 80px; height: auto;"> 
            </div>
        @endif
        <div>
            <h5>DAILY TIME RECORD</h1>
            <h5>{{$company}}</h1>
            <h5>For the month of <div class="underline" style="min-width: auto !important; padding: 0 15px 0 15px !important; text-transform: uppercase">{{ \Carbon\Carbon::parse($dtrDate)->format('F Y') }} </div>(FY)</h1>
        </div>
    </div>

     <table class="info-table">
            <tr><td style="text-align: left"><strong>Name:</strong></td><td style="text-align: left">{{ $logs['employee_account']['firstname'] . ' ' . $logs['employee_account']['middlename'] . ' ' . $logs['employee_account']['lastname']}}</td></tr>
            <tr><td style="text-align: left"><strong>Position:</strong></td><td style="text-align: left" class="underline"> {{ $logs['employee_account']['position'] }}</td></tr>
             <tr><td style="text-align: left"><strong>Official Time: </strong></td><td style="text-align: left; text-transform: capitalize;" class="underline">{{ $officialTime['shift_duration'] ?? 'Flexible' }}</td></tr>
            <tr><td style="text-align: left"><strong>Office/Department:</strong></td><td style="text-align: left" class="underline"> {{ $logs['employee_account']['section'] }}</td></tr>
        </table>


    <table class="p-dtr-table">
        <thead>
            <tr>
                <th>Days</th>
                <th colspan="{{ $showLunch ? '2' : '1' }}">First Half</th>
                <th colspan="{{ $showLunch ? '2' : '1' }}">Second Half</th>
                <th colspan="2">OVERTIME</th>
                <th colspan="2">Undertime</th>
               <th class="remarks-col">Remark</th>
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
            @foreach ($logs['dtr']['logs'] as $key => $day)
                <tr>
                    <td style="position: relative; ">
                        {{ \Carbon\Carbon::parse($key)->format('d D') }}
                        @if ($day['clock_in'] !== null && $day['origin'] === 'web')
                            <div class="shaded-box">|</div>
                        @endif
                    </td>
                    <!-- First Half -->
                    <td>
                        @isset($day['clock_in'])
                            {{ \Carbon\Carbon::parse($day['clock_in'])->format('g:i A') }}
                        @else
                            {{ ' ' }}
                        @endisset
                    </td>
                    @if($showLunch)
                        <td>{{ isset($day['lunch_in']) ? \Carbon\Carbon::parse($day['lunch_in'])->format('g:i A') : ' ' }}</td>
                    @endif

                    <!-- Second Half -->
                    @if($showLunch)
                        <td>{{ isset($day['lunch_out']) ? \Carbon\Carbon::parse($day['lunch_out'])->format('g:i A') : ' ' }}</td>
                    @endif
                    <td> {{ isset($day['clock_out']) ? \Carbon\Carbon::parse($day['clock_out'])->format('g:i A') : ' ' }}</td>

                    @php
                        // Flag to check if it's a future date
                        $isFuture = $day['isFuture'] ?? false;
                        $isBreakRequired = $day['is_break_required'] ?? false;
                    
                        // Allowed remarks
                        $allowedRemarks = ['absent', 'rest day', 'special hol', 'legal hol'];
                    
                        // Normalize and check remarks
                        $remarks = $day['remarks'] ?? null;
                        $isEmpty = false;
                    
                        // Check if any required clock times are missing or empty
                        $clockIn = $day['clock_in'] ?? null;
                        $lunchIn = $day['lunch_in'] ?? null;
                        $lunchOut = $day['lunch_out'] ?? null;
                        $clockOut = $day['clock_out'] ?? null;
                    
                        // If any clock times are empty or null, mark isEmpty = true

                        if($isBreakRequired){
                            if (empty($clockIn) && empty($lunchIn) && empty($lunchOut) && empty($clockOut)) {
                                $isEmpty = true;
                            }
                        } else {
                            if (empty($clockIn) && empty($clockOut)) {
                                $isEmpty = true;
                            }
                        }

                        // overtime
                        $overtimeMinutes = $day['aut']['overtime']['minutes']  ?? 0;
                        $otHour = floor($overtimeMinutes / 60);
                        $otMins = $overtimeMinutes % 60;
                        
                        // undertime only (separate from tardiness)
                        $undertimeMinutes = $day['aut']['undertime']['minutes'] ?? 0;
                        $underHours = floor($undertimeMinutes / 60);
                        $underMins = $undertimeMinutes % 60;
                    @endphp
                
                    
                    <!-- Overtime: Calculate Hours -->
                    <td>
                        @if(!$isFuture)
                            @if(!$isEmpty)
                                {{ $otHour }}
                            @endif
                        @endif
                    </td>
                    
                    <!-- Overtime: Calculate Minutes -->
                    <td>
                        @if(!$isFuture)
                            @if(!$isEmpty)
                                {{ $otMins }}
                            @endif
                        @endif
                    </td>
                    
                    <!-- Undertime Hours -->
                    <td>
                        @if(!$isFuture)
                            @if(!$isEmpty)
                                {{ $underHours }}
                            @endif
                        @endif
                    </td>
                    
                    <!-- Undertime Minutes -->
                    <td>
                        @if(!$isFuture)
                            @if(!$isEmpty)
                                {{ $underMins  }}
                            @endif
                        @endif
                    </td>
                    
                   <td class="remarks-col">
                        @if(isset($day['remarks']) && is_array($day['remarks']))
                            @foreach($day['remarks'] as $index => $remark)
                                <small>{{ $remark }}</small>
                                @php 
                                    $nextIndex = $index + 1;
                                    $totalRemarks = count($day['remarks']);
                                @endphp
                        
                                @if ($nextIndex < $totalRemarks)
                                    @if ($nextIndex % 2 == 0)
                                        <br> 
                                    @else
                                        <small>, </small>
                                    @endif
                                @endif
                            @endforeach
                        @else
                            <small> </small>
                        @endif
                        @if($isAdmin)
                            @if(
                                    isset($day['remarks'])
                                    && (
                                        in_array('Discrepancy', $day['remarks'])
                                        || in_array('Absent', $day['remarks'])
                                    )
                                )
                                <a href="{{ route('timekeeping.correction-apply', [
                                    'bsd_no' => $logs['employee_account']['bsd_no'] ?? null,
                                    'date' => \Carbon\Carbon::parse($key)->format('Y-m-d'),
                                ]) }}" class="btn btn-sm btn-danger btn-correction">
                                    Correction
                                </a>
                            @endif   
                        @endif
                    </td>                      
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="dtr-summary">
        <h5 class="text-center text-uppercase">Total Summary</h5>
        <div class="dtr-summary-container">
            <div class="dtr-summary-item">Days Worked = {{ $logs['dtr']['summary']['worked_days'] ?? '0' }}</div>
            <div class="dtr-summary-item">Tardiness =
                {{$logs['dtr']['summary']['tardiness'] ?? '0'}}
            </div>
            <div class="dtr-summary-item">Leave = {{ $logs['dtr']['summary']['leaves'] ?? '0' }}</div>
            <div class="dtr-summary-item">Absences = {{ $logs['dtr']['summary']['absences'] ?? '0' }}</div>
            <div class="dtr-summary-item">TA Freq. = {{$logs['dtr']['summary']['tardiness_freq']}} </div>
            <div class="dtr-summary-item">Rest Day = {{ $logs['dtr']['summary']['rest_days'] ?? '0' }}</div>
            <div class="dtr-summary-item">Overtime = {{ $logs['dtr']['summary']['overtime'] ?? '0' }}</div>
            <div class="dtr-summary-item">Undertime =
                {{ $logs['dtr']['summary']['undertime'] ?? '0' }}
            </div>
            <div class="dtr-summary-item">Special Hol. = {{ $logs['dtr']['summary']['special_hol'] ?? '0' }}</div>
            <div class="dtr-summary-item">Total Days of Work = {{ $logs['dtr']['summary']['total_days_of_work'] ?? '0' }}</div>
            <div class="dtr-summary-item">UT Freq. = {{$logs['dtr']['summary']['undertime_freq']}}</div>
            <div class="dtr-summary-item">Legal Hol. = {{ $logs['dtr']['summary']['legal_hol'] ?? '0' }}</div>
            <div class="dtr-summary-item">Less TA/UT  = {{$logs['dtr']['summary']['less_aut'] ?? '0'}} </div>
        </div>
    </div>
    <div class="sepe" style="margin-top: 40px;"></div>
    <div class="certify">
        I, CERTIFY on my honor that the above is a true and correct report of the hours of work
        performed, record of which was made daily at the time of arrival and departure from office.
    </div>
    <div class="signature">
        <h5>{{ $logs['employee_account']['firstname'] . ' ' . $logs['employee_account']['middlename'] . ' ' . $logs['employee_account']['lastname']}}</h5>
        <div class="sepe"></div>
        <h6>(Name and Signature of Employee)</h6>
        <div class="sepe" style="margin-top: 30px;"></div>
        <p>Verified as to prescribed office hours (In-Charge)</p>
    </div>
    <div class="remarks">
        <h6 style="text-transform: uppercase">Remarks:</h6>
        <p>{{ $logs['remarks'] ?? '' }}</p>   
    </div>


</div>

@endfor