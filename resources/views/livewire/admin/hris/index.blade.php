<div>
    <div class="modal fade" id="alert_employee" wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog custom-modal modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5 text-uppercase fw-bold" id="staticBackdropLabel">Upload Reports</h1>
                    <button type="button" class="btn-close" wire:click="close_upload_employee" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($resultMessage)
                        <ul class="nav nav-pills d-flex justify-content-center" wire:ignore id="myTab" role="tablist">
                            <?php foreach ($resultMessage as $index => $message): ?>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link text-uppercase <?= $index === 0 ? 'active' : ''; ?>" id="tab-<?= $index ?>" data-bs-toggle="tab" href="#content-<?= $index ?>" role="tab"><?= $message['section'] ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <hr>
                        <div class="tab-content" id="myTabContent">
                            @foreach ($resultMessage as $index => $message)
                                <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="content-{{ $index }}" role="tabpanel">
                                    <p class="text-uppercase"><strong>Inserted Records: ({{ $message['insertedCount'] }})</strong></p>
                                    <ul class="text-uppercase">
                                        @if (!empty($message['insertedList']))
                                            @foreach ($message['insertedList'] as $inserted)
                                                <li>
                                                    {{ $inserted['message'] }}
                                                    <span class="ms-2">
                                                        <a target="_blank" href="{{route('hris.show', ['employee_no' => $inserted['employee_no']])}}" class="text-decoration-underline text-primary">View</a>
                                                    </span>
                                                </li>
                                            @endforeach
                                        @else
                                            <li>No records inserted</li>
                                        @endif
                                    </ul>
                                    <p class="text-uppercase"><strong>Updated Records: ({{ $message['updatedCount'] }})</strong></p>
                                    <ul class="text-uppercase">
                                        @if (!empty($message['updatedList']))
                                            @foreach ($message['updatedList'] as $updated)
                                                <li>
                                                    {{ $updated['message'] }}
                                                    <span class="ms-2">
                                                        <a target="_blank" href="{{route('hris.show', ['employee_no' => $updated['employee_no'], 'form' => 'information'])}}" class="text-decoration-underline text-primary">View</a>
                                                    </span>
                                                </li>
                                            @endforeach
                                        @else
                                            <li>No records updated</li>
                                        @endif
                                    </ul>                                    
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" wire:ignore.self id="upload_employee" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog custom-modal modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5 text-uppercase fw-bold" id="staticBackdropLabel">Add Employee</h1>
                    <button type="button" class="btn-close" wire:loading.remove wire:target="file" wire:click="close_upload_employee" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4 px-4">
                    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button  wire:ignore.self class="nav-link active" id="upload-tab" data-bs-toggle="pill" data-bs-target="#upload-add" type="button" role="tab" aria-controls="upload" aria-selected="false">
                                File Upload
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="{{route('hris.manual')}}" class="nav-link" id="manual-tab" role="tab" aria-controls="manual" aria-selected="true">
                                Manual Adding
                            </a>
                        </li>
                    </ul>
                
                    <hr>
                    <!-- Pills Content -->
                    <div class="tab-content" id="pills-tabContent">
                        <!-- Upload Tab -->
                        <div class="tab-pane fade show active" wire:ignore.self id="upload-add" role="tabpanel" aria-labelledby="upload-tab">
                            <label class="mb-2" for="file">File Upload</label>
                            <input type="file" wire:model="file" id="file" class="form-control" wire:loading.attr="disabled" wire:target="upload_file">
                            <div class="mt-2 text-muted fw-bold text-uppercase d-flex justify-content-between align-items-center" style="font-size: 13px">
                                <small>Note: only files xlsx or xls are allowed.</small>
                                <small><a href="{{asset('templates/updated HRIS EMPLOYEE TEMPLATE.xlsx')}}" class="nav-link text-decoration-underline">Download Template</a></small>
                            </div>
                            <div wire:loading wire:target="file" class="mt-2 text-center text-muted">
                                <p>Please Wait... <i class="fa-solid fa-spinner fa-spin"></i></p>
                            </div>
                            <div class="mt-3">
                                @if($upload_preview)
                                    File Ready to import: <a href="{{$upload_preview}}">{{$upload_preview}}</a>
                                @endif
                            </div>
                            <div class="form-check my-3">
                                <input class="form-check-input" type="checkbox" wire:change="select_change('linkSchedule')" wire:model="isLinkSchedule" wire:loading.attr="disabled" wire:target="upload_file">
                                <label class="form-check-label">
                                    Link Shift and Schedule
                                </label>
                            </div>
                            @if($isLinkSchedule)
                                <hr class="my-4">
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <label class="mb-2">Link Shift Schedule</label>
                                        <select wire:model="shift_id" id="shift_id" class="form-select" wire:loading.attr="disabled" wire:target="upload_file">
                                            <option value=""> - CHOOSE - </option>
                                            @foreach($shifts as $shift)
                                                <option value="{{$shift->id}}">{{$shift->name . ' (' . $shift->shift_duration . ')'}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="mb-2">Link Employee Schedule</label>
                                        <select wire:model="schedule_id" id="schedule_id" class="form-select" wire:loading.attr="disabled" wire:target="upload_file">
                                            <option value=""> - CHOOSE - </option>
                                            @foreach($schedules as $schedule)
                                                <option value="{{$schedule->id}}">{{$schedule->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endif
                            @error('file') 
                                <span class="text-danger">{{ $message }}</span> 
                            @enderror
                            @if($upload_preview)
                                <div class="mt-4 d-flex justify-content-end">
                                    <button class="btn btn-primary px-5 py-3 text-uppercase fw-bold" 
                                            wire:click="upload_file"
                                            wire:loading.attr="disabled">
                                        <span wire:loading.remove>Upload File</span>
                                        <span wire:loading wire:target="upload_file">Importing <i class="fa-solid fa-spinner fa-spin"></i></span>
                                    </button>
                                </div>
                            @endif    
                            <div class="w-100 mt-5" wire:loading wire:target="upload_file">
                                <div class="alert alert-danger d-flex justify-content-center gap-3 align-items-center" role="alert">
                                    <i class="fa-solid fa-triangle-exclamation fs-5"></i>
                                    <div class="text-uppercase fw-bold">
                                        Please do not close the modal or refresh the page to prevent errors during the upload process.
                                    </div>
                                </div>  
                            </div> 
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="d-lg-flex justify-content-end text-center mb-5 gap-3">
        @if(config('app.product') === 'government')
        <a href="{{route('hris.staffing')}}" class="btn btn-outline-primary px-5 py-3 text-uppercase mb-3">View Staffing</a>
        @endif
        <button class="btn btn-primary px-5 py-3 text-uppercase mb-3" data-bs-toggle="modal" data-bs-target="#upload_employee">Add Employee</button>
    </div>

    <div>
        <div class="row mb-5">
            <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
                <li class="nav-item d-flex gap-3 my-3" role="presentation">
                    <a href="{{ route('hris.index') }}"
                    class="nav-link text-uppercase fw-bold {{ is_null($selectedType) ? 'active' : '' }}">
                        All
                    </a>
                </li>
                @foreach($employmentTypes as $employmentType)
                    <li class="nav-item d-flex gap-3 my-3" role="presentation">
                        <a href="{{ route('hris.index', ['employment_type' => $employmentType->id]) }}"
                        class="nav-link text-uppercase fw-bold {{ $selectedType == $employmentType->id ? 'active' : '' }}">
                            {{ $employmentType->name }}
                        </a>
                    </li>
                @endforeach
                <li class="nav-item d-flex gap-3 my-3" role="presentation">
                    <a href="{{ route('hris.index', ['employment_type' => 'unassigned']) }}"
                    class="nav-link text-uppercase fw-bold {{ $selectedType === 'unassigned' ? 'active' : '' }}">
                        Unassigned
                    </a>
                </li>
                <li class="nav-item ms-auto d-flex gap-3 my-3" role="presentation">
                    <a href="{{ route('hris.index', ['employment_type' => 'archived']) }}"
                    class="nav-link text-uppercase fw-bold {{ $selectedType === 'archived' ? 'active' : '' }}">
                        Archived
                    </a>
                </li>
            </ul>
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
                <input id="search" wire:model.live="search" type="text" class="form-control w-50" placeholder="Employee No. or Name">
            </div>
        </div>
        <div class="table-responsive mt-3">
            <table class="table table-striped table-bordered w-100">
                <thead>
                    <tr>
                        <th></th>
                        <th>Employee No</th>
                        <th>Employee Name</th>
                        <th>Date Hired</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $key => $item)
                  
                        <tr data-id="{{$item->employee_no}}">
                            <td class="text-center">
                                @php
                                    $fullname = optional($item->personal)->firstname && optional($item->personal)->lastname
                                        ? $item->personal->firstname . ' ' . $item->personal->lastname
                                        : null;

                                    // Profile path
                                    $profile = $item->personal->profile ?? null; 
                                    //dd($profile);
                                    $hasProfile = $profile && Storage::disk('public')->exists($profile);   
                                @endphp

                                @if(!$item->isTransferingEmp)
                                     @if($hasProfile)
                                        <img src="{{ asset('storage/' . $profile) }}"
                                            alt="Profile"
                                            style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">
                                    @else
                                        <img src="https://ui-avatars.com/api/?background=005668&color=ffffff&bold=true&name={{ urlencode($fullname ?: 'Unknown') }}"
                                            style="width: 50px; height: 50px; border-radius: 50%;">
                                    @endif
                                @else
                                    <span class="text-muted fst-italic">Loading...</span>
                                @endif
                            </td>
                            <td>{{$item->employee_no}}</td>
                            <td>
                                @if(!$item->isTransferingEmp)
                                    {!! $fullname ?? '<span class="text-muted fst-italic">No Name</span>' !!}
                                @else
                                    <span class="text-muted fst-italic">Loading...</span>
                                @endif
                            </td>
                            <td>
                                @if(!$item->isTransferingEmp)
                                    {{format_date($item->date_hired, 'day_date_string')}}
                                @else
                                    <span class="text-muted fst-italic">Loading...</span>
                                @endif
                            </td>
                            <td wire:ignore.self>
                                <div class="d-flex gap-2">
                                    @if($selectedType == 'archived')
                                        <button wire:click="restore('true', '{{$item->employee_no}}')" class="btn btn-info"
                                            title="Restore Archived Employee">
                                            <i class="fa-solid fa-retweet"></i>
                                        </button>
                                    @else
                                        <a target="_blank" href="{{route('download.view', ['show' => 'employee', 'employee_no' => $item->employee_no])}}" class="btn btn-primary"
                                            title="Download PDS">
                                            <i class="fa-solid fa-download"></i>
                                        </a>
                                        <a target="_blank" href="{{route('hris.show', ['employee_no' => $item->employee_no, 'form' => 'information'])}}" class="btn btn-primary"
                                            title="View Employee Records">
                                            <i class="fa-regular fa-folder-open"></i>
                                        </a>
                                        <a target="_blank" href="{{route('dtr.show', ['id' => $item->employee_no])}}" class="btn btn-info"
                                            title="View DTR">
                                            <i class="fa-solid fa-business-time"></i>
                                        </a>
                                        <a href="javascript:void(0)" wire:click="changeEmployeeNo('{{ $item->employee_no }}')" class="btn btn-info"
                                            title="Change Employee No.">
                                            <i class="fa-solid fa-person-walking-arrow-loop-left"></i>
                                        </a>
                                        @if(optional($item->account)->isLocked)
                                            <button wire:click="unlock('true', '{{ $item->employee_no }}')" class="btn btn-info"
                                                title="Unlock Employee Account">
                                                <i class="fa-solid fa-lock-open"></i>
                                            </button>
                                        @endif
                                        <button wire:click="remove('true', '{{$item->employee_no}}')" class="btn btn-danger"
                                            title="Remove Employee">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center fw-bold py-3">No data was found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">
                {{ $employees->links(data: ['scrollTo' => false]) }}
            </div>
        </div>     
    </div>

    @livewire('admin.hris.change-employee-no')

</div>
