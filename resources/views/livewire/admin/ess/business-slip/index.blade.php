<div>
    <div class="modal fade" wire:ignore.self id="showModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5 text-uppercase fw-bold" id="staticBackdropLabel">View OBS Application</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 col-md-4 mb-4">
                            <label class="mb-2" for="employee_no">Employee No.</label>
                            <input type="text" id="employee_no" class="form-control restricted" value="{{ isset($view_records) ? ($view_records->employee_no) : '' }}" readonly>
                        </div>
                        <div class="col-12 col-md-4 mb-4">
                            <label class="mb-2" for="employee_name">Employee Name</label>
                            <input type="text" id="employee_name" class="form-control restricted" value="{{ $view_records?->employee?->personal ? ($view_records->employee->personal->firstname . ' ' . $view_records->employee->personal->lastname) : '' }}" readonly>
                        </div>
                        <div class="col-12 col-md-4 mb-4">
                            <label class="mb-2" for="date_filed">Date Filed</label>
                            <input type="date" id="date_filed" class="form-control restricted" value="{{ isset($view_records) ? $view_records->date_filed : '' }}" readonly>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-12 mb-4">
                            <label class="mb-2" for="section">Department</label>
                            <input type="text" id="section" class="form-control restricted" value="{{ isset($view_records->employment->section->name) ? ($view_records->employment->section->name . ' (' . $view_records->employment->section->code . ') ') : 'No Data Provided' }}" readonly>
                        </div>
                        <div class="col-12 col-md-6 mb-4">
                            <label class="mb-2" for="branch">Branch</label>
                            <input type="text" id="branch" class="form-control restricted" value="{{ isset($view_records->employment->section->branch->name) ? ($view_records->employment->section->branch->name . ' (' . $view_records->employment->section->branch->code . ') ') : 'No Data Provided' }}" readonly>
                        </div>
                        <div class="col-12 col-md-6 mb-4">
                            <label class="mb-2" for="department">Department</label>
                            <input type="text" id="department" class="form-control restricted" value="{{ isset($view_records->employment->section->department->name) ? ($view_records->employment->section->department->name . ' (' . $view_records->employment->section->department->code . ') ') : 'No Data Provided' }}" readonly>
                        </div>
                        <div class="col-12 col-md-12 mb-4">
                            <label class="mb-2" for="position">Position</label>
                            <input type="text" id="position" class="form-control restricted" value="{{ isset($view_records->employment->positions['name']) ? ($view_records->employment->positions['name'] . ' (' . $view_records->employment->positions['code'] . ') ') : 'No Data Provided' }}" readonly>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-12 mb-4">
                            <label class="mb-2" for="purpose">Purpose</label>
                            <textarea class="form-control restricted" rows="5" readonly>{{ isset($view_records) ? ($view_records->purpose) : ''}}</textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-md-4 mb-4">
                            <label class="mb-2" for="destination">Destination</label>
                            <input type="text" id="destination" class="form-control restricted" value="{{ isset($view_records) ? ($view_records->destination) : ''}}" readonly>
                        </div>
                        <div class="col-12 col-md-4 mb-4">
                            <label class="mb-2" for="departure_time">Departure Time</label>
                            <input type="text" id="departure_time" class="form-control restricted" 
                                   value="{{ isset($view_records) ? \Carbon\Carbon::createFromFormat('H:i:s', $view_records->departure_time)->format('g:i A') : '' }}" 
                                   readonly>
                        </div>
                        <div class="col-12 col-md-4 mb-4">
                            <label class="mb-2" for="arrival_time">Arrival Time</label>
                            <input type="text" id="arrival_time" class="form-control restricted" 
                                   value="{{ isset($view_records) ? \Carbon\Carbon::createFromFormat('H:i:s', $view_records->arrival_time)->format('g:i A') : '' }}" 
                                   readonly>
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
                    <a href="{{route('ess.obs', ['status' => 'pending'])}}" class="nav-link text-uppercase fw-medium {{$status === 'pending' ? 'active' : ''}}"  role="tab" aria-controls="pills-home" aria-selected="true">Pending</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{route('ess.obs', ['status' => 'granted'])}}" class="nav-link text-uppercase fw-medium {{$status === 'granted' ? 'active' : ''}}" role="tab" aria-controls="pills-profile" aria-selected="false">Granted</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{route('ess.obs', ['status' => 'disapproved'])}}" class="nav-link text-uppercase fw-medium {{$status === 'disapproved' ? 'active' : ''}}" role="tab" aria-controls="pills-profile" aria-selected="false">Disapproved</a>
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
                                    <th>Employee No.</th>
                                    <th>Employee Name</th>
                                    <th>Date Applied</th>
                                    <th style="max-width: 200px;">Action</th>
                                </tr>
                            </thead>                
                            <tbody>
                                @forelse($records as $record)
                                    <tr data-id="{{$record->id}}">
                                        <td>{{$record->employee_no}}</td>
                                        <td>{{$record->employee->personal->firstname . ' ' . $record->employee->personal->lastname}}</td>
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