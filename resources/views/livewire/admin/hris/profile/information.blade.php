<div>
    <form wire:submit.prevent="save">
        <div class="card mb-4 border-0">
            <div class="card-header border-0 bg-transparent">
                <h5 class="mb-0 text-uppercase fw-bold pt-4 pb-0 ps-2">Employee Details</h5>
            </div>
            <div class="card-body px-4">
                <div class="row my-3">
                    <div class="col-12 mb-5">
                        <div class="row">
                            <div class="col-12 col-md-6">
                                @php
                                $personal = $records['employee_personal'];

                                $fullname = trim(($personal['firstname'] ?? '') . ' ' . ($personal['lastname'] ?? ''));

                                // Normalize profile (null if missing or file does not exist)
                                $profile = $personal['profile'] ?? null;
                                if ($profile && !Storage::disk('public')->exists($profile)) {
                                    $profile = null;
                                }
                            @endphp
                              @if($profile)
        {{-- Show Uploaded Profile --}}
                                    <img src="{{ asset('storage/' . $profile) }}"
                                        alt="Profile"
                                        style="width: 180px; height: 180px; object-fit: cover; border-radius: 8px;">
                                @else
                                    {{-- Fallback Avatar --}}
                                    <img src="https://ui-avatars.com/api/?background=005668&color=ffffff&bold=true&name={{ urlencode($fullname ?: '!') }}"
                                        style="width: 180px; height: 180px; border-radius: 8px;">
                                @endif
                            </div>
                        </div>
                    </div>  
                    <div class="col-12 col-md-3 mb-3">
                        <label class="mb-2" for="employee_no">Employee No. <span class="text-danger">*</span></label>
                        <input type="text" wire:model="records.employee_information.employee_no" id="records.employee_information.employee_no" class="form-control restricted" readonly>
                        <div class="error-field">
                            @error('records.employee_information.employee_no') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-3 mb-3">
                        <label class="mb-2" for="biometrics_id">Biometrics ID</label>
                        <input type="number" wire:model="records.employee_information.biometrics_id" id="records.employee_information.biometrics_id" class="form-control">
                        <div class="error-field">
                            @error('records.employee_information.biometrics_id') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>  
                    <div class="col-12 col-md-3 mb-3">
                        <label class="mb-2" for="date_hired">Date Hired</label>
                        <input type="text" wire:model="records.employee_information.date_hired" id="records.employee_information.date_hired" class="form-control restricted" readonly>
                        <div class="error-field">
                            @error('records.employee_information.date_hired') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div> 
                    <div class="col-12 col-md-3 mb-3">
                        <label class="mb-2" for="service_duration">Service Duration</label>
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
                        <input type="text" wire:model="records.employee_information.date_resignation" id="records.employee_information.date_resignation" class="form-control restricted" readonly>
                        <div class="error-field">
                            @error('records.employee_information.date_resignation') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>  
                    <div class="col-12 mt-4 mb-3">
                        <h5 class="mb-0 text-uppercase fw-bold pt-4 pb-0 ps-2">Organization Details</h5>
                        <hr>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="mb-2" for="section_id">Department <span class="text-danger">*</span></label>
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
                        <label class="mb-2" for="selectedBranchId">Central / Field Office</label>
                        <select wire:model="selectedBranchId" id="selectedBranchId" class="form-select">
                            <option value=""> - CHOOSE - </option>
                            @foreach ($branches as $branch)
                                <option value="{{$branch->id}}">{{$branch->code ? ($branch->code . ' - ') : ''}}{{$branch->name}}</option>
                            @endforeach
                        </select>
                        <div class="error-field">
                            @error('selectedBranchId') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                   <!-- <div class="col-md-6 mb-3">
                        <label class="mb-2" for="department">Cluster</label>
                        <input type="text" wire:model="records.employee_information.department" id="records.employee_information.department" class="form-control" readonly>
                        <div class="error-field">
                           {{-- @error('records.employee_information.department') <span class="text-danger">{{ $message }}</span> @enderror --}}
                        </div>
                    </div>-->
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
                        <label class="mb-2" for="shift_schedule">Shift Schedule</label>
                        <select wire:model="records.employee_information.shift_schedule" wire:change="select_change('section')" id="records.employee_information.shift_schedule" class="form-select">
                            <option value=""> - CHOOSE - </option>
                            @foreach ($shiftSchedule as $shift)
                                <option value="{{$shift->id}}">{{$shift->name }}</option>
                            @endforeach
                        </select>
                        <div class="error-field">
                            @error('records.employee_information.shift_schedule') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div> 
                    <div class="col-12 col-md-3 mb-3">
                        <label class="mb-2" for="employee_schedule">Days Schedule</label>
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
                    @if($isGovernment)
                        <div class="col-md-3 mb-3">
                            <label class="mb-2" for="salary">Basic Salary <span class="text-danger">*</span></label>
                            <input type="text" wire:model="records.employee_information.salary" id="records.employee_information.salary" class="form-control {{$records['employee_information']['type'] == 3 ? '' : 'restricted'}}" {{$records['employee_information']['type'] == 3 ? '' : 'readonly'}}>
                        <div class="error-field">
                                @error('records.employee_information.salary') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        </div>
                    @else
                    <div class="col-md-3 mb-3">
                            <label class="mb-2" for="salary">Basic Salary <span class="text-danger">*</span></label>
                            <input type="text" wire:model="records.employee_information.salary" id="records.employee_information.salary" class="form-control">
                        <div class="error-field">
                                @error('records.employee_information.salary') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        </div>
                    @endif
                    <div class="col-md-3 mb-3">
                        <label class="mb-2" for="allowance">Allowance</label>
                        <input type="text" wire:model="records.employee_information.allowance" id="records.employee_information.allowance" class="form-control" placeholder="0.00">
                        <div class="error-field">
                            @error('records.employee_information.allowance') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
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
        
        <!-- Deduction Section -->
        <div class="row">
            <div class="col-12 mt-4 mb-3">
                <h5 class="mb-0 text-uppercase fw-bold pt-4 pb-0 ps-2">Deductions</h5>
                <hr>
            </div>
            <div class="col-md-3 mb-3">
                <label class="mb-2">Tax (Withholding Tax)</label>
                <div class="form-control bg-light d-flex align-items-center" style="cursor: not-allowed; min-height: 38px;">
                    <span class="text-muted">₱</span>
                    <span class="ms-1">{{ $deductions['tax'] ?? '0.00' }}</span>
                </div>
                <small class="text-muted">Calculated automatically</small>
            </div>
            <div class="col-md-3 mb-3">
                <label class="mb-2">SSS</label>
                <div class="form-control bg-light d-flex align-items-center" style="cursor: not-allowed; min-height: 38px;">
                    <span class="text-muted">₱</span>
                    <span class="ms-1">{{ $deductions['sss'] ?? '0.00' }}</span>
                </div>
                <small class="text-muted">Calculated automatically</small>
            </div>
            <div class="col-md-3 mb-3">
                <label class="mb-2">HDMF (Pag-IBIG)</label>
                <div class="form-control bg-light d-flex align-items-center" style="cursor: not-allowed; min-height: 38px;">
                    <span class="text-muted">₱</span>
                    <span class="ms-1">{{ $deductions['hdmf'] ?? '0.00' }}</span>
                </div>
                <small class="text-muted">Calculated automatically</small>
            </div>
            <div class="col-md-3 mb-3">
                <label class="mb-2">PhilHealth</label>
                <div class="form-control bg-light d-flex align-items-center" style="cursor: not-allowed; min-height: 38px;">
                    <span class="text-muted">₱</span>
                    <span class="ms-1">{{ $deductions['philhealth'] ?? '0.00' }}</span>
                </div>
                <small class="text-muted">Calculated automatically</small>
            </div>
        </div>
        
        <hr class="mt-5">
        <div class="row">
            <div class="col-12 col-md-4 mb-4">
                <div class="card mb-4 border-0">
                    <div class="card-header border-0 bg-transparent">
                        <h5 class="mb-0 text-uppercase fw-bold pt-2 pb-0 ps-2">Leave Credits</h5>
                    </div>
                    <div class="card-body px-4">
                        <ul class="list-unstyled">
                            @foreach ($records['leaveCredits'] as $item)
                                <li class="d-flex align-items-center gap-3 mb-2">
                                    <div class="text-uppercase">
                                        <span> {{ strtoupper($item['code']) . ' - ' .  ucwords($item['name']) }}</span>
                                        <strong> ({{ $item['credits'] }})</strong>
                                    </div>
                                </li>
                            @endforeach
                        </ul>                            
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 mb-4">
                <div class="card mb-4 border-0">
                    <div class="card-header border-0 bg-transparent">
                        <h5 class="mb-0 text-uppercase fw-bold pt-2 pb-0 ps-2">Other Earnings</h5>
                    </div>
                    <div class="card-body px-4">
                        <ul class="list-unstyled">
                            @if (count($records['other_earnings']) > 0)
                                @foreach ($records['other_earnings'] as $item)
                                     <li class="d-flex align-items-center gap-2 mb-2 text-uppercase">
                                        <span>{{ ucwords($item['name']) }}</span>
                                        -
                                        <strong>PHP {{ number_format($item['amount'], 2) }}</strong>
                                        <i class="fa fa-check text-primary fs-4 ms-2" aria-hidden="true"></i>
                                    </li>
                                @endforeach
                            @else
                                <li class="text-muted text-uppercase">No other earnings found.</li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 mb-4">
                <div class="card mb-4 border-0">
                    <div class="card-header border-0 bg-transparent">
                        <h5 class="mb-0 text-uppercase fw-bold pt-2 pb-0 ps-2">Other Deductions</h5>
                    </div>
                    <div class="card-body px-4">
                        <ul class="list-unstyled">
                            @php
                                $hasDeductions = !empty($records['other_deductions']) && collect($records['other_deductions'])->where('amount', '>', 0)->isNotEmpty();
                                $hasGsis = !empty($records['employee_gsis']) && $records['employee_gsis']['ps'] > 0;
                            @endphp
                        
                            {{-- Display Other Deductions --}}
                            @if($hasDeductions)
                                @foreach ($records['other_deductions'] as $item)
                                    <li class="d-flex align-items-center gap-2 mb-2 text-uppercase">
                                        <span>{{ ucwords($item['name']) }}</span>
                                        -
                                        <strong>PHP {{ number_format($item['amount'], 2) }}</strong>
                                        <i class="fa fa-check text-primary fs-4 ms-2" aria-hidden="true"></i>
                                    </li>
                                @endforeach
                            @endif
                        
                            {{-- Display GSIS Deduction --}}
                            @if($hasGsis)
                                <li class="d-flex align-items-center gap-3 mb-2">
                                    <div>
                                        <span>GSIS</span>
                                        <strong>worth ₱{{ number_format($records['employee_gsis']['ps'], 2) }}</strong>
                                        <i class="fa fa-check text-primary fs-4 ms-2" aria-hidden="true"></i>
                                    </div>
                                </li>
                            @endif
                        
                            {{-- No Deductions Found --}}
                            @if(!$hasDeductions && !$hasGsis)
                                <li class="text-muted text-uppercase">No other deductions found.</li>
                            @endif
                        </ul>                                   
                    </div>
                </div>
            </div>
        </div>
        @if (!empty($records))
            <hr class="mb-4">
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
