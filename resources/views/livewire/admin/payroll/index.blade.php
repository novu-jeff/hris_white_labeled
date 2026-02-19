<div>
    <style>
        /* Ensure daterangepicker calendar appears above the Create Payroll modal (Bootstrap modal z-index is 1055) */
        .daterangepicker {
            z-index: 9999 !important;
        }
    </style>
    <div class="d-md-flex justify-content-end gap-3">
         <button wire:click="selectPayroll('{{$type}}')" class="btn btn-primary text-uppercase px-5 py-3 fw-medium" type="button">
            Generate Payroll
        </button>          
    </div>
    <div class="card border-0 mt-3">
        <div class="card-body p-0">
            <div class="table-responsive">
               @php
                    $reportComponentMap = [
                        'salary'             => 'admin.payroll.reports.salary',
                        'clothing_allowance' => 'admin.payroll.reports.clothing-allowance',
                        'mid_year'           => 'admin.payroll.reports.mid-year',
                        'year_end'           => 'admin.payroll.reports.year-end',
                        'ot_pay'             => 'admin.payroll.reports.ot-pay',
                    ];
                @endphp

                @if(isset($reportComponentMap[$type]))
                    @livewire($reportComponentMap[$type], [
                        'entries' => $entries,
                        'status' => $status,
                        'type' => $type,
                        'employment_type' => $employment_type
                    ])
                @endif

            </div>
        </div>
    </div>
    <div wire:ignore.self class="modal fade" data-bs-backdrop="static" id="newPayroll" tabindex="-1" aria-labelledby="newPayrollLabel" aria-hidden="true">
        <div class="modal-dialog {{ $isToCreate ? 'modal-lg' : '' }} {{ $activeTab == 'ineligible' ? '' : ''  }}">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-uppercase fw-medium fw-bold" id="newPayrollLabel">Create Payroll</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <form wire:submit.prevent="createPayroll">
                        @if(!$isToCreate) 
                            @foreach ($dynamicFormFields['items']['fields'] as $fieldKey => $field)
                                <div class="mb-3">
                                    @if($field['type'] != 'checkbox') 
                                        <label for="{{ $fieldKey }}" class="form-label text-uppercase">
                                            {{ $field['label'] }}
                                        </label>
                                    @endif

                                    @php
                                        $inputValue = $field['value'] ?? '';
                                        $inputClass = $field['class'] ?? '';
                                        $inputAttr = $field['attr'] ?? [];
                                    @endphp

                                    @switch($field['type'])
                                        @case('text')
                                        @case('date')
                                            <input
                                                type="{{ $field['type'] }}"
                                                id="{{ $fieldKey }}"
                                                class="form-control {{ $inputClass }}"
                                                wire:model.defer="{{ $fieldKey }}"
                                                value="{{ $inputValue }}"
                                                @foreach ($inputAttr as $attrKey => $attrVal)
                                                    {{ $attrKey }}="{{ $attrVal }}"
                                                @endforeach
                                            >
                                            @break

                                        @case('datepicker')
                                            <input
                                                type="text"
                                                id="{{ $fieldKey }}"
                                                class="form-control {{ $inputClass }}"
                                                wire:model.defer="{{ $fieldKey }}"
                                                value="{{ $inputValue }}"
                                                @foreach ($inputAttr as $attrKey => $attrVal)
                                                    {{ $attrKey }}="{{ $attrVal }}"
                                                @endforeach
                                            >
                                            @break

                                        @case('monthyear')
                                            <input
                                                type="month"
                                                id="{{ $fieldKey }}"
                                                class="form-control {{ $inputClass }}"
                                                wire:model.defer="{{ $fieldKey }}"
                                                value="{{ old($fieldKey, $inputValue) }}"
                                                @foreach ($inputAttr as $attrKey => $attrVal)
                                                    {{ $attrKey }}="{{ $attrVal }}"
                                                @endforeach
                                            >
                                            @break

                                        @case('select')
                                            <select
                                                id="{{ $fieldKey }}"
                                                class="form-control {{ $inputClass }}"
                                                wire:model.defer="{{ $fieldKey }}"
                                                @foreach ($inputAttr as $attrKey => $attrVal)
                                                    {{ $attrKey }}="{{ $attrVal }}"
                                                @endforeach
                                            >
                                                <option value="">-- CHOOSE --</option>
                                                @foreach ($field['options'] as $option)
                                                    <option value="{{ $option->id }}" 
                                                        {{ (old($fieldKey, $inputValue) == $option->id) ? 'selected' : '' }}
                                                    >
                                                        {{ $option->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @break
                                        @case('checkbox')
                                            <div class="form-check">
                                                <input 
                                                    type="checkbox" 
                                                    id="{{ $fieldKey }}" 
                                                    class="form-check-input {{ $inputClass }}" 
                                                    wire:model.defer="{{ $fieldKey }}"
                                                    @foreach ($inputAttr as $attrKey => $attrVal)
                                                        {{ $attrKey }}="{{ $attrVal }}"
                                                    @endforeach
                                                >
                                                <label class="form-check-label" for="{{ $fieldKey }}">
                                                    {{ $field['label'] ?? ucfirst(str_replace('_', ' ', $fieldKey)) }}
                                                </label>
                                            </div>
                                        @break
                                    @endswitch

                                    <div class="error-field">
                                        @error($fieldKey)
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            @endforeach

                            <div class="d-flex justify-content-end mt-5 pb-2">
                                <button class="btn btn-primary px-5 py-3 text-uppercase" wire:loading.attr="disabled" type="submit" >
                                    <span wire:loading.remove wire:target="createPayroll">Next</span>
                                    <span wire:loading wire:target="createPayroll">
                                        Please Wait <i class="fa-solid fa-spinner fa-spin"></i>
                                    </span>
                                </button>
                            </div>
                        @else
                            <h5 class="mt-3 mb-4 text-uppercase fw-bold">Below are the eligible and ineligible for payroll processing</h5>
                            <ul class="nav nav-pills mb-3" id="employeeTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button wire:ignore.self wire:click="setActiveTab('eligible')" class="nav-link text-uppercase fw-bold active" id="eligible-tab" data-bs-toggle="tab" data-bs-target="#eligible" type="button" role="tab" aria-controls="eligible" aria-selected="true">
                                        Eligible
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button wire:ignore.self wire:click="setActiveTab('ineligible')" class="nav-link text-uppercase fw-bold" id="ineligible-tab" data-bs-toggle="tab" data-bs-target="#ineligible" type="button" role="tab" aria-controls="ineligible" aria-selected="false">
                                        Ineligible
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content" id="employeeTabsContent">
                                <div wire:ignore.self class="tab-pane fade show active" id="eligible" role="tabpanel" aria-labelledby="eligible-tab">
                                    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
                                        <span class="fw-bold text-success text-uppercase fw-bold">Total Eligible: {{ $employeesChecked['eligible']['count'] }}</span>
                                    </div>
                                    <div class="table-responsive mt-2" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-striped table-bordered w-100 m-0">
                                            <thead class="table-light" style="position: sticky; top: 0; z-index: 1; background-color: #f8f9fa;">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Employee No</th>
                                                    <th>Name</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($employeesChecked['eligible']['items'] as $index => $employee)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>{{ $employee['employee_no'] ?? 'N/A' }}</td>
                                                        <td>{{ $employee['name'] }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="text-center fw-bold py-3">No data was found</td>
                                                    </tr> 
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div wire:ignore.self class="tab-pane fade" id="ineligible" role="tabpanel" aria-labelledby="ineligible-tab">
                                    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
                                        <span class="fw-bold text-danger text-uppercase fw-bold">Total Ineligible: {{ $employeesChecked['ineligible']['count'] }}</span>
                                    </div>
                                    <div class="table-responsive mt-2" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-striped table-bordered w-100 m-0">
                                            <thead class="table-light" style="position: sticky; top: 0; z-index: 1; background-color: #f8f9fa;">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Employee No</th>
                                                    <th>Name</th>
                                                    <th>Reason</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($employeesChecked['ineligible']['items'] as $index => $employee)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>{{ $employee['employee_no'] ?? 'N/A' }}</td>
                                                        <td>{{ $employee['name'] }}</td>
                                                        <td>
                                                            <div class="text-danger">
                                                                {{ is_array($employee['reason']) ? implode(', ', $employee['reason']) : $employee['reason'] }}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="text-center fw-bold py-3">No data was found</td>
                                                    </tr> 
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-5 pb-3">
                                <!--<button class="btn btn-outline-primary px-5 py-3 text-uppercase" type="button" wire:click="go_back">
                                    Go Back
                                </button>-->
                                <button class="btn btn-primary px-5 py-3 text-uppercase" wire:loading.attr="disabled" type="submit">
                                    <span wire:loading.remove wire:target="createPayroll">Create</span>
                                    <span wire:loading wire:target="createPayroll">
                                        Creating <i class="fa-solid fa-spinner fa-spin"></i>
                                    </span>
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($isBatchProcessing) 
        <div 
            class="modal fade d-block show"
            data-bs-backdrop="static"
            data-bs-keyboard="false"
            tabindex="-1"
            aria-modal="true"
            role="dialog"
            style="background-color: rgba(0, 0, 0, 0.5);"
        >
            <div class="modal-dialog modal-md modal-dialog-centered">
                <div class="modal-content text-center py-4 shadow">
                    <div class="modal-body">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        
                        <p class="fw-bold mb-0 text-uppercase text-muted">
                            {{ $batchStatusMessage }}
                        </p>

                        <div class="px-4">
                            <div class="progress mb-2 mt-3" style="height: 30px;">
                                <div 
                                    class="progress-bar progress-bar-striped progress-bar-animated bg-primary fw-bold" 
                                    role="progressbar" 
                                    style="width: {{ $batchProgress }}%; font-size: 12px;" 
                                    aria-valuenow="{{ $batchProgress }}" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                    {{ $batchProgress }}%
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center mt-4">
                            <button class="btn btn-danger text-uppercase fw-bold px-4 py-2" wire:click="cancel_payroll" wire:loading.attr="disabled">
                                <i class="fa-solid fa-circle-xmark me-1"></i> Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div wire:poll.3000ms="checkBatchStatus"></div>
        </div>    
    @endif
</div>

@section('script')
    <script>
        $(function() {

            Livewire.on('initDateRange', (event) => {
                $('.range').attr('autocomplete', 'off');
                $('.range').daterangepicker({
                    locale: { format: 'YYYY-MM-DD' },
                    autoUpdateInput: false
                });

                $('.range').on('apply.daterangepicker', function(ev, picker) {
                    var rangeStr = picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format('YYYY-MM-DD');
                    @this.set('cut_off_period', rangeStr);
                    @this.set('ot_period', rangeStr);
                    // Policy: payroll for cut-off 1-15 runs on the 17th (employees have full day of 16th for time adjustments)
                    if (picker.endDate.date() === 15) {
                        var payrollDate17 = picker.endDate.format('YYYY-MM') + '-17';
                        @this.set('payroll_date', payrollDate17);
                        $('.datepicker-single').val(payrollDate17);
                    }
                });

                // Single-date picker for Payroll Date (appears above modal via .daterangepicker z-index)
                $('.datepicker-single').attr('autocomplete', 'off');
                $('.datepicker-single').daterangepicker({
                    singleDatePicker: true,
                    locale: { format: 'YYYY-MM-DD' },
                    autoUpdateInput: false
                });
                $('.datepicker-single').on('apply.daterangepicker', function(ev, picker) {
                    var val = picker.startDate.format('YYYY-MM-DD');
                    $(this).val(val);
                    @this.set('payroll_date', val);
                });
            });

            Livewire.on('start-job-dispatch', (event) => {
                const payroll_id = event[0].payroll_id;
                const employment_type = event[0].employment_type
                const type = event[0].type

                setTimeout(() => {
                    Livewire.dispatch('dispatchPayrollJobs', [payroll_id, employment_type, type]);
                }, 100);
            });

        });
    </script>
@endsection