<div>

    <div class="modal fade" wire:ignore.self id="showModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog custom-modal modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5 text-uppercase fw-bold" id="staticBackdropLabel">View Request Timelog</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 col-md-6 mb-4">
                            <label class="mb-2" for="employee_no">Employee No.</label>
                            <input type="text" id="employee_no" class="form-control restricted" value="{{ $view_records?->personal?->employee_no ?? '' }}" readonly>
                        </div>
                        <div class="col-12 col-md-6 mb-4">
                            <label class="mb-2" for="employee_name">Employee Name</label>
                            <input type="text" id="employee_name" class="form-control restricted" value="{{ $view_records?->personal ? ($view_records->personal->firstname . ' ' . $view_records->personal->lastname) : '' }}" readonly>
                        </div>
                        <div class="col-12 mb-4">
                            <hr>
                        </div>
                        <div class="col-12 col-md-12 mb-4">
                            <label class="mb-2" for="date">Request Timelog Date</label>
                            <input type="text" id="date" class="form-control restricted" value="{{ isset($view_records) && $view_records->date ? format_date($view_records->date, 'date_string') : '' }}" readonly>
                        </div>
                        
                        <div class="col-12 col-md-6 mb-4">
                            <label class="mb-2" for="clock_in">Clock In</label>
                            <input type="time" id="clock_in" class="form-control restricted" value="{{ $view_records?->clock_in ? \Carbon\Carbon::parse($view_records->clock_in)->format('H:i') : '' }}" readonly>
                        </div>
                        
                        <div class="col-12 col-md-6 mb-4">
                            <label class="mb-2" for="clock_out">Clock Out</label>
                            <input type="time" id="clock_out" class="form-control restricted" value="{{ $view_records?->clock_out ? \Carbon\Carbon::parse($view_records->clock_out)->format('H:i') : '' }}" readonly>
                        </div>
                        
                        <div class="col-12 col-md-12 mb-4">
                            <label class="mb-2" for="date_applied">Date Applied</label>
                            <input type="text" id="date_applied" class="form-control restricted" value="{{ $view_records?->created_at ? format_date($view_records->created_at, 'date_string') : '' }}" readonly>
                        </div>
                        
                        <div class="col-12 mb-4">
                            <label class="mb-2" for="reason">Reason</label>
                            <textarea id="reason" cols="30" rows="20" class="form-control restricted" readonly placeholder="Write something...">{{ $view_records?->reason ?? '' }}</textarea>
                        </div>
                        
                        @if($view_records && $view_records->attachments)
                            <div class="col-12 md-4">
                                <label class="mb-2">Attachments</label>
                                <div class="attachments">
                                    <ul class="list-unstyled text-uppercase">
                                        @foreach($view_records->attachments as $item)
                                            <li class="list-unstyled-item">
                                                <div class="d-flex align-items-center gap-2">
                                                    <a
                                                        href="{{ Storage::url($item['attachment']) }}" 
                                                        target="_blank" rel="noopener noreferrer"
                                                    >
                                                        {{ basename($item['attachment']) }}
                                                    </a>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
                @if ($view_records && $view_records->status === 'pending')
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
                    <a href="{{route('ess.time-adjustments', ['status' => 'pending'])}}" class="nav-link text-uppercase fw-medium {{$status === 'pending' ? 'active' : ''}}"  role="tab" aria-controls="pills-home" aria-selected="true">Pending</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{route('ess.time-adjustments', ['status' => 'granted'])}}" class="nav-link text-uppercase fw-medium {{$status === 'granted' ? 'active' : ''}}" role="tab" aria-controls="pills-profile" aria-selected="false">Granted</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{route('ess.time-adjustments', ['status' => 'disapproved'])}}" class="nav-link text-uppercase fw-medium {{$status === 'disapproved' ? 'active' : ''}}" role="tab" aria-controls="pills-profile" aria-selected="false">Disapproved</a>
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
                                    <th>Date Applied</th>
                                    <th style="max-width: 200px;">Action</th>
                                </tr>
                            </thead>                
                            <tbody>
                                @forelse($records as $record)
                                    <tr data-id="{{$record->id}}">
                                        <td>{{$record->employee_no}}</td>
                                        <td>
                                            @php
                                                $emp = $record->employee?->personal ?? null;
                                            @endphp
                                            {{ $emp ? ($emp->firstname . ' ' . $emp->lastname) : 'N/A' }}
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

