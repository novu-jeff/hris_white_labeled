<div>
    <div class="mt-4 mb-5">
        <h3 class="text-uppercase fw-bold">For {{$leaveName}}</h3>
    </div>
    <div class="card border-0 mt-3">
        <div class="card-body p-0">
            <div class="row mb-4">
                <div class="col-md-6 d-flex align-items-center gap-2">
                    <label for="entries" class="form-label mb-0">Show entries:</label>
                    <select id="entries" wire:model.live="entries" class="form-select w-auto">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="30">30</option>
                        <option value="50">50</option>
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
                            @if($this->id == 1 || $this->id == 2)
                                <th>VL Credits</th>
                                <th>SL Credits</th>
                                <th>Updated as of</th>
                                <th>Actions</th>
                            @else
                                <th>Credits</th>
                                <th>Updated as of</th>
                                <th>Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $record)
                            <tr>
                                <td style="vertical-align: top; padding-top: 22px;">{{ $record->employee_no }}</td>
                                <td style="vertical-align: top; padding-top: 22px;">{{ optional($record->personal)->firstname . ' ' . optional($record->personal)->lastname }}
</td>
                                @if($this->id == 1 || $this->id == 2)
                                    <td style="vertical-align: top; padding-top: 12px;">
                                        <input
                                            type="number"
                                            wire:key="vl-credit-{{$record->employee_no}}"
                                            wire:model="vl_credits.{{$record->employee_no}}"
                                            class="form-control {{ $has_leave_card[$record->employee_no] === true ? 'restricted' : '' }}"
                                            {{ $has_leave_card[$record->employee_no] === true ? 'readonly' : '' }}>
                                        <div class="error-field">
                                            @error("vl_credits.{$record->employee_no}")
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <label class="mt-2 mb-2">Total: <span style="font-weight: 600; color:red">{{$total_vl_credits[$record->employee_no]}}</span></label>
                                    </td>
                                    <td style="vertical-align: top; padding-top: 12px;">
                                        <input
                                            type="number"
                                            wire:key="sl-credit-{{$record->employee_no}}"
                                            wire:model="sl_credits.{{$record->employee_no}}"
                                            class="form-control {{ $has_leave_card[$record->employee_no] === true ? 'restricted' : '' }}"
                                            {{ $has_leave_card[$record->employee_no] === true ? 'readonly' : '' }}>
                                        <div class="error-field">
                                            @error("sl_credits.{$record->employee_no}")
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <label class="mt-2 mb-2">Total: <span style="font-weight: 600; color:red">{{$total_sl_credits[$record->employee_no]}}</span></label>
                                    </td>
                                    <td style="vertical-align: top; padding-top: 12px;">
                                        <input
                                            type="month"
                                            wire:key="as_of-{{$record->employee_no}}"
                                            wire:model="as_of.{{$record->employee_no}}"
                                            class="form-control {{ $has_leave_card[$record->employee_no] === true ? 'restricted' : '' }}"
                                            max="{{ $currentMonth }}"
                                            {{ $has_leave_card[$record->employee_no] === true ? 'readonly' : '' }}>
                                        <div class="error-field">
                                            @error("as_of.{$record->employee_no}")
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </td>
                                    <td style="vertical-align: top; padding-top: 12px;">
                                        <div style="white-space: normal !important;" class="py-2">
                                            @if($has_leave_card[$record->employee_no])
                                                <div class="d-flex justify-content-center align-items-center gap-2">
                                                    <button
                                                        type="button"
                                                        class="btn btn-success"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#manualAddModal"
                                                        wire:click="openManualAdd('{{ $record->employee_no }}')"
                                                        title="Manual add credits">
                                                        <i class="fa-solid fa-plus"></i>
                                                    </button>
                                                    <div class="btn btn-danger" wire:click="resetCredits(true, '{{ $record->employee_no }}')">
                                                        <i class="fa-solid fa-rotate"></i>
                                                    </div>
                                                    <a href="{{route('leave.show', ['leave' => $id, 'employee' => $record->employee_no, 'action' => 'view-card'])}}" class="btn btn-primary">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </a>
                                                </div>
                                            @else
                                                <button
                                                    wire:click="save('{{ $record->employee_no }}')"
                                                    wire:key="save-{{ $record->employee_no }}"
                                                    class="btn btn-sm btn-primary px-3 py-2 w-100 text-uppercase fw-bold">
                                                    <span wire:loading.remove wire:target="save-{{ $record->employee_no }}">
                                                        Save
                                                    </span>
                                                    <span wire:loading wire:target="save-{{ $record->employee_no }}">
                                                        Saving <i class="fa-solid fa-spinner ms-2 fa-spin"></i>
                                                    </span>
                                                </button>
                                            @endif
                                            @if(!$has_leave_card[$record->employee_no])
                                                <a href="{{route('leave.show', ['leave' => $id, 'employee' => $record->employee_no, 'action' => 'view-card'])}}" class="btn btn-sm btn-info w-100 mt-2 text-uppercase fw-bold py-2">
                                                    <span>
                                                        Show
                                                    </span>
                                                </a>
                                            @endif
                                            <button type="button" data-bs-toggle="modal" wire:click="select_employee('{{$record->employee_no}}')" data-bs-target="#importModal"
                                                class="mt-2 btn btn-sm btn-dark px-3 py-2 w-100 text-uppercase fw-bold">
                                                <span>
                                                    Import
                                                </span>
                                            </button>
                                        </div>
                                    </td>
                                @else
                                    <td>
                                        <input
                                            type="number"
                                            wire:key="credit-{{$record->employee_no}}"
                                            wire:model="credits.{{$record->employee_no}}"
                                            class="form-control {{ $has_leave_card[$record->employee_no] === true ? 'restricted' : '' }}"
                                            {{ $has_leave_card[$record->employee_no] === true ? 'readonly' : '' }}>
                                        <div class="error-field">
                                            @error("credits.{$record->employee_no}")
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </td>
                                    <td style="vertical-align: top; padding-top: 12px;">
                                        <input
                                            type="month"
                                            wire:key="as_of-{{$record->employee_no}}"
                                            wire:model="as_of.{{$record->employee_no}}"
                                            class="form-control {{ $has_leave_card[$record->employee_no] === true ? 'restricted' : '' }}"
                                            max="{{ $currentMonth }}"
                                            {{ $has_leave_card[$record->employee_no] === true ? 'readonly' : '' }}>
                                        <div class="error-field">
                                            @error("as_of.{$record->employee_no}")
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </td>
                                    <td>
                                        <button
                                            wire:click="save('{{ $record->employee_no }}')"
                                            wire:key="save-{{ $record->employee_no }}"
                                            class="btn btn-sm btn-primary px-3 py-2 w-100 text-uppercase fw-bold">
                                            <span wire:loading.remove wire:target="save-{{ $record->employee_no }}">
                                                Save
                                            </span>
                                            <span wire:loading wire:target="save-{{ $record->employee_no }}">
                                                Saving <i class="fa-solid fa-spinner ms-2 fa-spin"></i>
                                            </span>
                                        </button>

                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center">No records found</td>
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

    <div class="modal fade" id="importModal" wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog ">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-uppercase fw-bold" id="importModalLabel">Import Leave Credits</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="importFile" class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="importFile" wire:model="importFile">
                        <div class="error-field mt-2">
                            @error('importFile') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="alert alert-danger text-uppercase fw-bold mt-4">
                            <small>
                                Note: Importing a file will overwrite the current leave credits of the selected employee.
                            </small>
                        </div>
                    </div>
                </div>
                @if($importFile)
                    <div class="modal-footer">
                        <div class="d-flex justify-content-end">
                            <button class="btn btn-primary px-5 py-3 text-uppercase fw-bold"
                                    wire:click="upload_file"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove>Upload File</span>
                                <span wire:loading wire:target="upload_file">Importing <i class="fa-solid fa-spinner fa-spin"></i></span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="manualAddModal" wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-uppercase fw-bold">Manual Add Leave Credits</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info text-uppercase fw-bold">
                        <small>
                            This will add to the selected month’s earned credits and recompute balances forward.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Employee No</label>
                        <input type="text" class="form-control" wire:model.defer="manual_employee_no" readonly>
                        @error('manual_employee_no') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Year</label>
                            <input type="number" class="form-control" wire:model.defer="manual_year" min="2000" max="2100">
                            @error('manual_year') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Month</label>
                            <select class="form-select" wire:model.defer="manual_period">
                                @foreach($months as $m)
                                    <option value="{{ $m }}">{{ $m }}</option>
                                @endforeach
                            </select>
                            @error('manual_period') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">VL to add</label>
                            <input type="number" step="0.001" class="form-control" wire:model.defer="manual_vl_add" min="0">
                            @error('manual_vl_add') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SL to add</label>
                            <input type="number" step="0.001" class="form-control" wire:model.defer="manual_sl_add" min="0">
                            @error('manual_sl_add') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Note (optional)</label>
                        <input type="text" class="form-control" wire:model.defer="manual_note" maxlength="255" placeholder="e.g., Correction / Bonus credits">
                        @error('manual_note') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary text-uppercase fw-bold px-4"
                            wire:click="applyManualAdd"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="applyManualAdd">Apply</span>
                        <span wire:loading wire:target="applyManualAdd">Saving <i class="fa-solid fa-spinner fa-spin ms-2"></i></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
