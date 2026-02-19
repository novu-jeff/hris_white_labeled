<div>
    <div class="modal fade" wire:ignore.self id="showModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5 text-uppercase fw-bold" id="staticBackdropLabel">View Payslip Download Request</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 col-md-6 mb-4">
                            <label class="mb-2" for="employee_no">Employee No.</label>
                            <input type="text" id="employee_no" class="form-control restricted"
                                value="{{ $view_records->employee->employee_no ?? '' }}" readonly>
                        </div>

                        <div class="col-12 col-md-6 mb-4">
                            <label class="mb-2" for="employee_name">Employee Name</label>
                            <input type="text" id="employee_name" class="form-control restricted"
                                value="{{ $view_records->employee->firstname ?? '' }} {{ $view_records->employee->lastname ?? '' }}" readonly>
                        </div>

                        <div class="col-12 mb-4"><hr></div>

                        <div class="col-12 col-md-6 mb-4">
                            <label class="mb-2" for="payroll_date">Payroll Date</label>
                            @php
                                $payrollDate = $view_records->payroll->payroll_date ?? null;
                                $formattedDate = $payrollDate ? \Carbon\Carbon::parse($payrollDate)->format('F d, Y') : '';
                            @endphp

                            <input type="text" id="payroll_date" class="form-control restricted" value="{{ $formattedDate }}" readonly>
                        </div>

                        <div class="col-12 col-md-6 mb-4">
                            @php
                                $cutOffPeriod = $view_records->payroll->cut_off_period ?? '';
                                $startDate = $endDate = '';
                                if (strpos($cutOffPeriod, ' to ') !== false) {
                                    [$start, $end] = explode(' to ', $cutOffPeriod);
                                    try {
                                        $startDate = \Carbon\Carbon::parse($start)->format('F d, Y');
                                        $endDate = \Carbon\Carbon::parse($end)->format('F d, Y');
                                    } catch (\Exception $e) {
                                        $startDate = $endDate = '';
                                    }
                                }
                            @endphp
                            <label class="mb-2" for="cut_off_period">Cut Off Period</label>
                            <input type="text" id="cut_off_period" class="form-control restricted"
                                value="{{ $startDate && $endDate ? $startDate . ' - ' . $endDate : '' }}" readonly>
                        </div>

                        <div class="col-12 col-md-12 mb-4">
                            <label class="mb-2" for="date_requested">Date Requested</label>
                            <input type="text" id="date_requested" class="form-control restricted"
                                value="{{ format_date($view_records->created_at ?? '', 'day_date_time_string') }}" readonly>
                        </div>
                    </div>

                </div>
                @if (isset($view_records->status) && $view_records->status === 'pending')
                    <div class="modal-footer">
                        <button wire:click="disapproved" class="btn btn-danger text-uppercase fw-medium">Disapprove</button>
                        <button wire:click="approved" class="btn btn-primary text-uppercase fw-medium">Approve</button>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="card border-0 mt-3">
        <div class="card-body p-0">
            <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a href="{{route('ess.payslip-request', ['status' => 'pending'])}}" class="nav-link text-uppercase fw-medium {{$status === 'pending' ? 'active' : ''}}"  role="tab" aria-controls="pills-home" aria-selected="true">Pending</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{route('ess.payslip-request', ['status' => 'granted'])}}" class="nav-link text-uppercase fw-medium {{$status === 'granted' ? 'active' : ''}}" role="tab" aria-controls="pills-profile" aria-selected="false">Granted</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{route('ess.payslip-request', ['status' => 'disapproved'])}}" class="nav-link text-uppercase fw-medium {{$status === 'disapproved' ? 'active' : ''}}" role="tab" aria-controls="pills-profile" aria-selected="false">Disapproved</a>
                </li>
            </ul>
            <div class="tab-content mt-5" id="pills-tabContent">
                <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab" tabindex="0">
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
                        <table class="table table-striped table-bordered w-100">
                            <thead>
                                <tr>
                                    <th>Employee No</th>
                                    <th>Employee Name</th>
                                    <th>Payroll ID</th>
                                    <th>Date Requested</th>
                                    <th style="max-width: 200px;">Action</th>
                                </tr>
                            </thead>                
                            <tbody>
                                @forelse($records as $record)
                                    <tr data-id="{{$record->id}}">
                                        <td>{{$record->employee_no}}</td>
                                        <td>{{$record->employee->firstname . ' ' . $record->employee->lastname}}</td>
                                        <td>
                                            <a target="_blank" href="{{ route('payroll.process', ['type' => 'salary', 'payroll_id' => $record->payroll_id]) }}">
                                                {{'#'.$record->payroll_id}}
                                            </a>
                                        </td>
                                        <td>{{format_date($record->created_at, 'date_string')}}</td>
                                        <td>
                                            <button type="button" wire:click="view({{$record->id}})" class="btn btn-primary mx-1">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                            <button wire:click="remove(true, {{$record->id}})" class="btn btn-danger mx-1">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center fw-bold py-3">No data was found</td>
                                    </tr> 
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $records->links(data: ['scrollTo' => false]) }}
                    </div>
                </div>
            </div>
        </div>
    </div>  
</div>

