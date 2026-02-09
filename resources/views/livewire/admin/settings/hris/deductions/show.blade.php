<div>
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
                    <input id="search" wire:model.live="search" type="text" class="form-control w-50" placeholder="Search employee...">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-bordered w-100">
                    <thead>
                        <tr>
                            <th>Employee No</th>
                            <th>Employee Name</th>
                            <th>Amount</th>
                            <th>Valid Until</th>
                            <th style="max-width: 200px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $record)
                            <tr>
                                <td>{{ $record->employee_no }}</td>
                                <td>
                                    {{ $record->personal->firstname ?? '' }} 
                                    {{ $record->personal->lastname ?? '' }}
                                </td>
                                <td style="vertical-align: top; padding-top: 12px;">
                                    <input
                                        type="number"
                                        step="0.01"
                                        wire:key="amount-{{$record->employee_no}}"
                                        wire:model="amounts.{{$record->employee_no}}"
                                        class="form-control"
                                        min="0"
                                        placeholder="Enter amount">
                                    <div class="error-field">
                                        @error("amounts.{$record->employee_no}")
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <small class="text-muted">Leave empty or 0 to remove</small>
                                </td>
                                <td style="vertical-align: top; padding-top: 12px;">
                                    <input
                                        type="date"
                                        wire:key="valid_until-{{$record->employee_no}}"
                                        wire:model="valid_until.{{$record->employee_no}}"
                                        class="form-control">
                                    <div class="error-field">
                                        @error("valid_until.{$record->employee_no}")
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </td>
                                <td style="vertical-align: top; padding-top: 12px;">
                                    <div style="white-space: normal !important;" class="py-2">
                                        <button
                                            wire:click="save('{{ $record->employee_no }}')"
                                            wire:key="save-{{ $record->employee_no }}"
                                            class="btn btn-sm btn-primary px-3 py-2 w-100 text-uppercase fw-bold">
                                            <span wire:loading.remove wire:target="save-{{ $record->employee_no }}">
                                                <i class="fa-solid fa-save me-1"></i> Save
                                            </span>
                                            <span wire:loading wire:target="save-{{ $record->employee_no }}">
                                                Saving <i class="fa-solid fa-spinner ms-2 fa-spin"></i>
                                            </span>
                                        </button>
                                        @if(isset($amounts[$record->employee_no]) && $amounts[$record->employee_no] > 0)
                                            <button 
                                                wire:click="remove(true, '{{$record->employee_no}}')"
                                                class="mt-2 btn btn-sm btn-danger px-3 py-2 w-100 text-uppercase fw-bold">
                                                <span wire:loading.remove wire:target="remove">
                                                    Remove
                                                </span>
                                                <span wire:loading wire:target="remove">
                                                    Removing <i class="fa-solid fa-spinner ms-2 fa-spin"></i>
                                                </span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No records found</td>
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
