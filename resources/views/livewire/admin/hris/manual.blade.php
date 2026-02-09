<div>
    <form wire:submit.prevent="save">
        <div class="card mb-4 border-0">
            <div class="card-header border-0 bg-transparent">
                <h5 class="mb-0 text-uppercase fw-bold pt-4 pb-0 ps-2">Employee Details</h5>
            </div>
            <div class="card-body px-4">
                <div class="row my-3">
                    <div class="col-12 col-md-3 mb-3">
                        <label class="mb-2" for="employee_no">Employee No. <span class="text-danger">*</span></label>
                        <input type="text" wire:model="records.employee_information.employee_no" id="records.employee_information.employee_no" class="form-control" >
                        <div class="error-field">
                            @error('records.employee_information.employee_no') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-3 mb-3">
                        <label class="mb-2" for="biometrics_id">Biometrics ID <span class="text-danger">*</span></label>
                        <input type="number" min="0"  wire:model="records.employee_information.biometrics_id" id="records.employee_information.biometrics_id" class="form-control">
                        <div class="error-field">
                            @error('records.employee_information.biometrics_id') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>  
                    <div class="col-12 col-md-3 mb-3">
                        <label class="mb-2" for="date_hired">Date Hired <span class="text-danger">*</span></label>
                        <input type="date" wire:change="select_change('date_hired')" wire:model="records.employee_information.date_hired" id="records.employee_information.date_hired" class="form-control" >
                        <div class="error-field">
                            @error('records.employee_information.date_hired') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div> 
                    <div class="col-12 col-md-3 mb-3">
                        <label class="mb-2" for="service_duration">Service Duration   </label>
                        <input type="text" wire:model="records.employee_information.service_duration" id="records.employee_information.service_duration" class="form-control restricted" readonly>
                        <div class="error-field">
                            @error('records.employee_information.service_duration') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div> 
                    <div class="col-md-3 mb-3">
                        <label class="mb-2" for="status">Account Status <span class="text-danger">*</span></label>
                        <select wire:model="records.employee_information.status" id="records.employee_information.status" class="form-select">
                            <option value=""> - CHOOSE - </option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <div class="error-field">
                            @error('records.employee_information.status') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2" for="date_resignation">Date Resignation</label>
                        <input type="text" wire:model="records.employee_information.date_resignation" id="records.employee_information.date_resignation" class="form-control" >
                        <div class="error-field">
                            @error('records.employee_information.date_resignation') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>  
                    <div class="col-12 mt-4 mb-3">
                        <h5 class="mb-0 text-uppercase fw-bold pt-4 pb-0 ps-2">Organization Details</h5>
                        <hr>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="mb-2" for="section_id">Section <span class="text-danger">*</span></label>
                        <select wire:model="records.employee_information.section_id" wire:change="select_change('section')" id="records.employee_information.section_id" class="form-select">
                            <option value=""> - CHOOSE - </option>
                            @foreach ($sections as $section)
                                <option value="{{$section->id}}">{{$section->code . ' - ' . $section->name}}</option>
                            @endforeach
                        </select>
                        <div class="error-field">
                            @error('records.employee_information.section_id') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="mb-2" for="department">Cluster</label>
                        <input type="text" wire:model="records.employee_information.department" id="records.employee_information.department" class="form-control" >
                        <div class="error-field">
                            @error('records.employee_information.department') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-12 mt-4 mb-3">
                        <h5 class="mb-0 text-uppercase fw-bold pt-4 pb-0 ps-2">Employment Details</h5>
                        <hr>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="mb-2" for="type">Employment Type <span class="text-danger">*</span></label>
                        <select wire:change="handleSalary" wire:model="records.employee_information.type" id="records.employee_information.type" class="form-select">
                            <option value=""> - CHOOSE - </option>
                            @foreach ($employmentTypes as $category)
                                <option value="{{strtolower($category->id)}}">{{$category->name}}</option>
                            @endforeach
                        </select>
                        <div class="error-field">
                            @error('records.employee_information.type') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    @if($isGovernment)
                        @if(in_array($records['employee_information']['type'], ['1', '2']))
                            <div class="col-md-4 mb-3">
                                <label class="mb-2" for="position_id">Position <span class="text-danger">*</span></label>
                                <select wire:change="handleSalary" wire:model.live="records.employee_information.position_id" id="records.employee_information.position_id" class="form-select">
                                    <option value=""> - CHOOSE - </option>
                                    @foreach ($positions as $position)
                                        <option value="{{$position->id}}">{{$position->name}}</option>
                                    @endforeach
                                </select>
                                <div class="error-field">
                                    @error('records.employee_information.position_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="mb-2" for="step_id">Tranche Step <span class="text-danger">*</span></label>
                                <select wire:change="handleSalary" wire:model.live="records.employee_information.step_id" id="records.employee_information.step_id" class="form-select">
                                    <option value=""> - CHOOSE - </option>
                                    @for($i = 1; $i <= 8; $i++)
                                        <option value="{{$i}}"> Step {{$i}}</option>
                                    @endfor
                                </select>
                                <div class="error-field">
                                    @error('records.employee_information.step_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>      
                        @elseif($records['employee_information']['type'] == 3)    
                            <div class="col-md-4 mb-3">
                                <label class="mb-2" for="job_completion">Job Order Completion <span class="text-danger">*</span></label>
                                <input type="date" wire:model="records.employee_information.job_completion" id="records.employee_information.job_completion" class="form-control">
                                <div class="error-field">
                                    @error('records.employee_information.job_completion') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>     
                        @endif
                    @else
                        <div class="col-md-4 mb-3">
                            <label class="mb-2" for="position_id">Position <span class="text-danger">*</span></label>
                            <select wire:change="handleSalary" wire:model.live="records.employee_information.position_id" id="records.employee_information.position_id" class="form-select">
                                <option value=""> - CHOOSE - </option>
                                @foreach ($positions as $position)
                                    <option value="{{$position->id}}">{{$position->name}}</option>
                                @endforeach
                            </select>
                            <div class="error-field">
                                @error('records.employee_information.position_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @endif
                    <div class="col-12 col-md-3 mb-3">
                        <label class="mb-2" for="shift_schedule">Shift Schedule <span class="text-danger">*</span></label>
                        <select wire:model="records.employee_information.shift_schedule" wire:change="select_change('section')" id="records.employee_information.shift_schedule" class="form-select">
                            <option value=""> - CHOOSE - </option>
                            @foreach ($shiftSchedule as $shift)
                                <option value="{{$shift->id}}">{{$shift->name . ' (' . $shift->work_setup . ')'}}</option>
                            @endforeach
                        </select>
                        <div class="error-field">
                            @error('records.employee_information.shift_schedule') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div> 
                    <div class="col-12 col-md-3 mb-3">
                        <label class="mb-2" for="employee_schedule">Days Schedule <span class="text-danger">*</span></label>
                        <select wire:model="records.employee_information.employee_schedule" wire:change="select_change('section')" id="records.employee_information.employee_schedule" class="form-select">
                            <option value=""> - CHOOSE - </option>
                            @foreach ($employeeSchedule as $schedule)
                                <option value="{{$schedule->id}}">{{$schedule->name}}</option>
                            @endforeach
                        </select>
                        <div class="error-field">
                            @error('records.employee_information.employee_schedule') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div> 
                    <div class="col-12 mt-4 mb-3">
                        <h5 class="mb-0 text-uppercase fw-bold pt-4 pb-0 ps-2">Salary & Payroll Details</h5>
                        <hr>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="mb-2" for="salary_method">Salary Method <span class="text-danger">*</span></label>
                        <select wire:model="records.employee_information.salary_method" id="records.employee_information.salary_method" class="form-select">
                            <option value=""> - CHOOSE - </option>
                            <option value="cash">Cash</option>
                            <option value="bank transfer">Bank Transfer</option>
                            <option value="paycheck">Paycheck</option>
                            <option value="e-wallet">E-Wallet</option>
                        </select>
                        <div class="error-field">
                            @error('records.employee_information.salary_method') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="mb-2" for="salary_type">Salary Type <span class="text-danger">*</span></label>
                        <select wire:model="records.employee_information.salary_type" id="records.employee_information.salary_type" class="form-select">
                            <option value=""> - CHOOSE - </option>
                            <option value="monthly">Monthly Rate</option>
                            <option value="salary">Daily Rate</option>
                        </select>
                        <div class="error-field">
                            @error('records.employee_information.salary_type') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    @if($isGovernment)
                        <div class="col-md-3 mb-3">
                            <label class="mb-2" for="salary">Salary Amount <span class="text-danger">*</span></label>
                            <input type="text" wire:model="records.employee_information.salary" id="records.employee_information.salary" class="form-control {{$records['employee_information']['type'] == 3 ? '' : 'restricted'}}" {{$records['employee_information']['type'] == 3 ? '' : ''}}>
                        <div class="error-field">
                                @error('records.employee_information.salary') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        </div>
                    @else
                    <div class="col-md-3 mb-3">
                            <label class="mb-2" for="salary">Salary Amount <span class="text-danger">*</span></label>
                            <input type="text" wire:model="records.employee_information.salary" id="records.employee_information.salary" class="form-control">
                        <div class="error-field">
                                @error('records.employee_information.salary') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        </div>
                    @endif
                    <div class="col-md-3 mb-3">
                        <label class="mb-2" for="payroll_account_number">Payroll Account No.</label>
                        <input type="text" wire:model="records.employee_information.payroll_account_number" id="records.employee_information.payroll_account_number" class="form-control">
                        <div class="error-field">
                            @error('records.employee_information.payroll_account_number') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @if (!empty($records))
            <hr class="mb-5">
            <div class="card-footer d-flex justify-content-end bg-transparent border-0">
                <div class="text-end">
                    <button type="submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                        <span wire:loading.remove wire:target="save">Save <i class="fa-solid fa-arrow-right ms-2"></i></span>
                        <span wire:loading wire:target="save">Saving <i class="fa-solid fa-spinner ms-2 fa-spin"></i></span>
                    </button>
                    <div class="mt-3 pb-5">
                        @if ($errors->any())
                            <small class="text-danger">There's an error upon submitting, please review your form.</small>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </form>
</div>
