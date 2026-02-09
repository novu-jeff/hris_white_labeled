<div>
    @php
        $monthNumeric = \Carbon\Carbon::parse($records['current']['month'].' 1')->format('m');
        $currentDate = \Carbon\Carbon::create($records['current']['year'], $monthNumeric, $records['current']['day'])->toDateString();
    @endphp
    <div class="text-center my-5">
        <h2 class="mb-0 text-uppercase fw-bold">{{$records['current']['day_of_week'] . ', ' . $records['current']['month'] . ' ' . $records['current']['day'] . ', ' . $records['current']['year']}}</h2>
    </div>
    <div class="timekeeping d-lg-flex justify-content-between align-items-center mb-5">
        <div class="d-md-flex justify-content-between gap-3">
            <a href="{{ route('timekeeping.correction', ['year' => $records['previous']['year'], 'month' => $records['previous']['month'], 'day' => $records['previous']['day']]) }}" class="btn btn-info px-5 py-3 text-uppercase fw-bold">
                Previous Day
            </a>
            <a href="{{ route('timekeeping.correction', ['year' => $records['next']['year'], 'month' => $records['next']['month'], 'day' => $records['next']['day']]) }}" class="d-block d-lg-none btn btn-primary px-5 py-3 text-uppercase fw-bold">
                Next Day
            </a>
        </div>
        <div class="d-md-flex justify-content-center gap-3 mt-4">
            <input type="date" id="datePicker" class="form-control" style="width: 300px" value="{{$currentDate}}">
            <select id="workSetup" class="form-select" style="width: 300px">
                <option value=""> - CHOOSE WORK SETUP -</option>
                <option value="all" {{empty($setup) ?? '' }}>All</option>
                <option value="onsite" {{$setup == 'onsite' ? 'selected' : '' }}>On-Site</option>
                <option value="wfh" {{$setup == 'wfh' ? 'selected' : '' }}>Work From Home</option>
            </select>
        </div>
        <a href="{{ route('timekeeping.correction', ['year' => $records['next']['year'], 'month' => $records['next']['month'], 'day' => $records['next']['day']]) }}" class="d-none d-lg-block btn btn-primary px-5 py-3 text-uppercase fw-bold">
            Next Day
        </a>
    </div>
    <div class="row mb-4">
        <div class="col-md-6 d-flex align-items-center gap-2">
            <label for="entries" class="form-label mb-0">Show entries:</label>
            <select id="entries" wire:model.live="entries" class="form-select w-auto">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="30">30</option>
                <option value="40">40</option>
                <option value="50">50</option>
                <option value="60">60</option>
                <option value="70">70</option>
                <option value="80">80</option>
                <option value="90">90</option>
                <option value="100">100</option>
            </select>
        </div>
        <div class="col-md-6 text-end d-flex justify-content-end align-items-center gap-2">
            <label for="search" class="form-label mb-0">Search:</label>
            <input id="search" wire:model.live="search" type="text" class="form-control w-50" placeholder="Search something...">
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-striped w-100" id="logs-table">
            <thead>
                <tr>
                    <th>Employee No</th>
                    <th>BSD No.</th>
                    <th>Employee Name</th>
                    <th>Actions</th> 
                </tr>
            </thead>
            <tbody>
                @forelse ($timelogs as $key => $item)
                    @php
                        // Check if the condition is met (avoid repeating the logic)
                        $highlightBG = ($item['origin'] == 'web' && $setup == 'wfh') || ($item['origin'] == 'biometrics' && $setup == 'onsite') ? '#014959' : null;
                        $highlightColor = ($item['origin'] == 'web' && $setup == 'wfh') || ($item['origin'] == 'biometrics' && $setup == 'onsite') ? '#fff' : null;
                    @endphp
                        <tr class="fw-bold" style="background-color: {{ $highlightBG }}; color: {{ $highlightColor }}">
                            <!-- Display Employee No, BSD No., and Employee Name -->
                            <td style="color:{{$highlightColor}};background-color: {{ $highlightBG }}">{{ $item['employee']->employee_no ?? '' }}</td>
                            <td style="color:{{$highlightColor}};background-color: {{ $highlightBG }}">{{ $item['bsd_no'] ?? '' }}</td>
                            <td style="color:{{$highlightColor}};background-color: {{ $highlightBG }}">
                                {{ optional(optional($item['employee'])->personal)->firstname . ' ' . optional(optional($item['employee'])->personal)->lastname ?? '' }}
                            </td>                    
                            <td style="color:{{$highlightColor}};background-color: {{ $highlightBG }}">
                                <button wire:click="findLogs({{$item['bsd_no'] ?? ''}})" class="btn btn-primary px-3 text-uppercase fw-medium">View</button>
                            </td>
                        </tr>   
                        @if($view_log && $item['bsd_no'] == $viewLogBsdNo)
                        @php
                            // Extract clock-in and clock-out times dynamically
                            $clockInAM = isset($view_log['logs'][0]['time']) ? Carbon\Carbon::parse($view_log['logs'][0]['time'])->format('h:i A') : 'N/A';
                            $breakOut = isset($view_log['logs'][1]['time']) ? Carbon\Carbon::parse($view_log['logs'][1]['time'])->format('h:i A') : 'N/A';
                            $breakIn = isset($view_log['logs'][2]['time']) ? Carbon\Carbon::parse($view_log['logs'][2]['time'])->format('h:i A') : 'N/A';
                            $clockOutPM = isset($view_log['logs'][3]['time']) ? Carbon\Carbon::parse($view_log['logs'][3]['time'])->format('h:i A') : 'N/A';

                          
                            $hasLocation = false;
                            $hasImage = false;
                        
                            // Check if any of the logs have a captured location or image
                            for ($i = 0; $i < 4; $i++) {
                                if (!empty($item['logs'][$i]['captured_location'])) {
                                    $hasLocation = true;
                                }
                                if (!empty($item['logs'][$i]['captured_image'])) {
                                    $hasImage = true;
                                }
                            }

                            $accomplishment = collect($item['logs'])->firstWhere('accomplishment');
                        
                        @endphp
                    
                        <tr class="child-row">
                            <td colspan="100%">
                                <div class="mb-3">
                                    <hr>
                                    <div class="row">
                                        <div class="col-12 col-md-5 mb-3">
                                            <strong>Employee Information:</strong>
                                            <ul class="my-3">
                                                <li>Employee No: <strong><u>{{ $view_log['employee']->employee_no ?? 'N/A' }}</u></strong></li>
                                                <li>Employee Name: <strong><u>{{ $view_log['employee']->personal->firstname ?? 'N/A' }} {{ $view_log['employee']->personal->lastname ?? 'N/A' }}</u></strong></li>
                                                <li>Biometrics ID: <strong><u>{{ $view_log['bsd_no'] ?? 'N/A' }}</u></strong></li>
                                            </ul>
                                        </div>
                    
                                        <div class="col-12 col-md-12">
                                            <hr>
                                            <strong>Employee Clock In & Out</strong>
                                            <div class="d-flex justify-content-end">
                                                <a href="{{route('timekeeping.correction-apply', ['bsd_no' => $view_log['bsd_no'], 'date' => $currentDate])}}" type="button" class="btn btn-danger px-3 text-uppercase fw-medium" id="applyCorrectionLink">Apply Correction</a>
                                            </div>
                                            <table class="table-auto my-3 border-collapse border border-gray-300 w-full">
                                                <thead class="bg-gray-200">
                                                    <tr>
                                                        <th class="border text-uppercase text-center px-4 py-2">Clock In</th>
                                                        <th class="border text-uppercase text-center px-4 py-2">Lunch Out</th>
                                                        <th class="border text-uppercase text-center px-4 py-2">Lunch In</th>
                                                        <th class="border text-uppercase text-center px-4 py-2">Clock Out</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="border text-uppercase text-center px-4 py-2"><strong><u>{{ $clockInAM }}</u></strong></td>
                                                        <td class="border text-uppercase text-center px-4 py-2"><strong><u>{{ $breakOut }}</u></strong></td>
                                                        <td class="border text-uppercase text-center px-4 py-2"><strong><u>{{ $breakIn }}</u></strong></td>
                                                        <td class="border text-uppercase text-center px-4 py-2"><strong><u>{{ $clockOutPM }}</u></strong></td>
                                                    </tr>
                                                    @if($hasLocation)
                                                        <tr>
                                                            @for ($i = 0; $i < 4; $i++)
                                                                <td class="text-center py-1">
                                                                    {{ isset($item['logs'][$i]['captured_location']) ? $item['logs'][$i]['captured_location'] : 'N/A' }}
                                                                </td>
                                                            @endfor
                                                        </tr>
                                                    @endif
                                                    @if ($hasImage)
                                                        <tr>
                                                            @for ($i = 0; $i < 4; $i++)
                                                                <td class="text-center py-1">
                                                                    @if (!empty($item['logs'][$i]['captured_image']))
                                                                        <img src="{{ Storage::url('timelogs/' . $item['logs'][$i]['captured_image']) }}" 
                                                                            alt="logs" style="width: 100%; height: 100px; object-fit: cover">
                                                                    @else
                                                                        No Image
                                                                    @endif
                                                                </td>
                                                            @endfor
                                                        </tr>
                                                    @endif
                                                    @if(!empty($accomplishment))
                                                        <tr>
                                                            <td colspan="12">
                                                                <div class="text-start mt-2 pb-3 px-3">
                                                                    <p class="mb-2 fw-bold">Accomplishment Report:</p>
                                                                    <a href="{{ $accomplishment['accomplishment'] }}" target="_blank" rel="noopener">
                                                                        <small>{{ $accomplishment['accomplishment'] }}</small>
                                                                    </a>
                                                                </div>
                                                            </td>    
                                                        </tr>  
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif                    
                @empty
                    <tr>
                        <td colspan="12" class="text-center fw-bold py-3">No data was found</td>
                    </tr>            
                @endforelse
            </tbody>        
        </table>
    </div>
    <div class="mt-4">
        {{ $timelogs->links(data: ['scrollTo' => false]) }}
    </div>
</div>
@section('script')
    <script>
        $(function() {

            $('#datePicker').on('change', function () {
                const date = $(this).val(); 
                
                // Split the date into year, month, and day
                const [year, month, day] = date.split('-');
                
                // Build the URL with the selected month, day, and year
                const url = '{{ route("timekeeping.correction", ["year" => "__year__", "month" => "__month__", "day" => "__day__"]) }}'
                            .replace('__year__', year)
                            .replace('__month__', month)
                            .replace('__day__', day);

                // Redirect the user to the new URL
                location.href = url;
            });

            $('#workSetup').on('change', function () {
                const selectedSetup = $(this).val();
                const url = new URL(window.location.href);

                // Add or update the 'setup' query parameter in the URL
                if (selectedSetup) {
                    url.searchParams.set('setup', selectedSetup);
                } else {
                    url.searchParams.delete('setup');
                }

                // Redirect to the updated URL
                window.location.href = url.toString();
            });

        });
    </script>
@endsection