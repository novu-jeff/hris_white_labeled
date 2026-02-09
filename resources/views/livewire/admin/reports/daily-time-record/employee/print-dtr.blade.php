{{-- TWO COPIES (LEFT + RIGHT) --}}

@for ($copy = 1; $copy <= 2; $copy++)
<div class="dtr-copy">

    {{-- HEADER --}}
    <div class="center">
         
       
            <img src="{{ asset('/img/' . $provider['client_logo'])}}">           
       
       
        <h1>A.W.A. TIME RECORD</h1>
        <div>OFFICE OF THE PRESIDENTIAL ADVISER ON PEACE, RECONCILIATION AND UNITY</div>
        <div>FOR THE MONTH OF <strong>{{ strtoupper(\Carbon\Carbon::parse($dtrDate)->format('F Y')) }}</strong></div>
    </div>

    <table class="info-table">
            <tr><td><strong>Name:</strong></td><td style="text-align: left">{{ $logs['employee_account']['firstname'] . ' ' . $logs['employee_account']['middlename'] . ' ' . $logs['employee_account']['lastname']}}</td></tr>
            <tr><td><strong>ID No:</strong></td><td style="text-align: left">{{ $logs['employee_account']['employee_no'] }}</td></tr>
            <tr><td><strong>Position:</strong></td><td style="text-align: left"> {{ $logs['employee_account']['position'] }}</td></tr>
            <tr><td><strong>Unit:</strong></td><td style="text-align: left"> {{ $logs['employee_account']['section'] }}</td></tr>
        </table>

   

    @php
        $showLunch = filter_var(config('app.lunch_tracking', true), FILTER_VALIDATE_BOOLEAN);
    @endphp
    {{-- TABLE --}}
    <table class="dtr-table">
        <thead>
            <tr>
                <th rowspan="2">DAY</th>
                <th colspan="{{ $showLunch ? '2' : '1' }}">A.M.</th>
                <th colspan="{{ $showLunch ? '2' : '1' }}">P.M.</th>
            </tr>
            <tr>
                <th>Arrival</th>
                @if($showLunch)
                    <th>Departure</th>
                @endif
                @if($showLunch)
                    <th>Arrival</th>
                @endif
                <th>Departure</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($logs['dtr']['logs'] as $key => $day)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($key)->format('d D') }}
                        @if ($day['clock_in'] !== null && $day['origin'] === 'web')
                            <div class="shaded-box">|</div>
                        @endif</td>
                    <td> @isset($day['clock_in'])
                            {{ \Carbon\Carbon::parse($day['clock_in'])->format('g:i A') }}
                        @else
                            {{ ' ' }}
                        @endisset</td>
                    @if($showLunch)
                        <td>{{ isset($day['lunch_in']) ? \Carbon\Carbon::parse($day['lunch_in'])->format('g:i A') : ' ' }}</td>
                    @endif
                    @if($showLunch)
                        <td>{{ isset($day['lunch_out']) ? \Carbon\Carbon::parse($day['lunch_out'])->format('g:i A') : ' ' }}</td>
                    @endif
                    <td> {{ isset($day['clock_out']) ? \Carbon\Carbon::parse($day['clock_out'])->format('g:i A') : ' ' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- CERTIFICATION --}}
    <div class="certify mt-3">
        I, CERTIFY on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.
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
