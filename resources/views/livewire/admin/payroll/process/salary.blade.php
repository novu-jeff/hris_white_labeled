<div>
    <div class="action mb-4">
        <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-success text-uppercase" wire:click="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel">
                <span wire:loading.remove wire:target="exportToExcel">
                    <i class="fa-solid fa-file-excel me-1"></i> Export to Excel
                </span>
                <span wire:loading wire:target="exportToExcel">
                    Exporting <i class="fa-solid fa-spinner fa-spin"></i>
                </span>
            </button>
        </div>
    </div>
    <hr class="mt-0">
    <div class="text-uppercase fw-bold">
        @if($isApproved)
            <h2 class="text-success fw-bold text-uppercase text-center">Approved</h2>
        @else
            <h2 class="text-danger fw-bold text-uppercase text-center">Pending</h2>
        @endif
    </div>
    <hr>
    <div class="row">
        <div class="col-12 col-md-6">
            <div class="text-uppercase fw-bold">
                Type : <span class="ms-2">{{$records['payroll']['type']}}</span>
            </div>
            <div class="text-uppercase fw-bold">
                Date : <span class="ms-2">{{$records['payroll']['formatted_payroll_date']}}</span>
            </div>
            <div class="text-uppercase fw-bold">
                Cut-off Period : <span class="ms-2">{{$records['payroll']['formatted_cutoff_period']}}</span>
            </div>
            <div class="text-uppercase fw-bold">
                Employee Type : <span class="ms-2">{{$records['payroll']['formatted_employment_type']}}</span>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="text-uppercase fw-bold">
                No. of employees : <span class="ms-2">{{$records['payroll']['no_employees']}}</span>
            </div>
            <div class="text-uppercase fw-bold">
                Net Amount : <span class="ms-2">PHP {{number_format($records['payroll']['overall_net_amount'], 2)}}</span>
            </div>
            <div class="text-uppercase fw-bold">
                @if($product == 'private')
                    Gross Amount Earned Total : <span class="ms-2">PHP {{number_format($records['payroll']['overall_gross_amount'] ?? 0, 2)}}</span>
                @else
                    Salary Amount : <span class="ms-2">PHP {{number_format($records['payroll']['overall_salary'], 2)}}</span>
                @endif
            </div>
        </div>
    </div>
    <hr class="pt-3">
    @php
        $status = $records['payroll']['status'];
    @endphp
    @if($product == 'government')
        @if($records['payroll']['employment_type']['id'] == '1')
            <div class="table-responsive pb-3">
                <table>
                    <thead>
                        <tr>
                            <th rowspan="2" class="vertical-text text-dark">Status</th>
                            <th rowspan="2">No.</th>
                            <th rowspan="2" class="text-center">Name</th>
                            <th rowspan="2" class="text-center">Position</th>
                            <th rowspan="2" class="text-center">Basic Salary</th>
                            <th rowspan="2" class="text-center">Pera</th>
                            <th rowspan="2" class="text-center">Gross Amount Earned</th>
                            <th colspan="26" class="text-center">DEDUCTIONS: (GSIS, MPL, PHILHEALTH, AUT, and W/TAX)</th>
                            <th colspan="2" class="text-center">AUT</th>
                            <th colspan="8" class="text-center"></th>
                            <th colspan="10" class="text-center">Salary</th> 
                        </tr>
                        <tr>
                            <th colspan="2" class="vertical-text green">RLIP</th>
                            <th colspan="2" class="vertical-text yellow">HDMF</th>
                            <th colspan="2" class="vertical-text skyblue">PHIL HEALTH</th>
                            <th colspan="2" class="vertical-text green">CONSOLOAN</th>
                            <th colspan="2" class="vertical-text green">EMERGYLN</th>
                            <th colspan="2" class="vertical-text green">PLREG</th>
                            <th colspan="2" class="vertical-text green">MPL</th>
                            <th colspan="2" class="vertical-text green">CPL</th>
                            <th colspan="2" class="vertical-text yellow">MP2</th>
                            <th colspan="2" class="vertical-text yellow">MPL STLMS</th>
                            <th colspan="2" class="vertical-text yellow">CIR375, CIR449</th>
                            <th colspan="2" class="vertical-text red">W/TAX</th>
                            <th colspan="2" class="vertical-text red">UCA</th>
                            <th class="vertical-text grey">1st Half</th>
                            <th class="vertical-text grey">2nd Half</th>
                            <th class="text-center">TOTAL DED.</th>
                            <th class="text-center">NET AMOUNT</th>
                            <th colspan="2" class="vertical-text grey">DBP</th>
                            <th colspan="2" class="vertical-text grey">KAWANI</th>
                            <th colspan="2" class="vertical-text grey">LBP PAYROLL ACCOUNT</th>
                            <th colspan="2" class="text-center">First Half</th>
                            <th colspan="2" class="text-center">Second Half</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($records['payroll_items'] as $sectionIndex => $sectionGroup)
                            <tr class="fw-bold bg-primary text-white sticky-top" style="top: 55px; z-index: 9;">
                                <td colspan="100%">
                                    <div class="d-flex justify-content-between w-100 px-5">
                                        <span>{{ $sectionGroup['section_name'] ?? 'Unknown Section' }}</span>
                                        <span class="text-center flex-grow-1">{{ $sectionGroup['section_name'] ?? 'Unknown Section' }}</span>
                                        <span>{{ $sectionGroup['section_name'] ?? 'Unknown Section' }}</span>
                                    </div>
                                </td>
                            </tr>

                            @foreach($sectionGroup['employees'] as $employeeIndex => $record)
                                <tr>
                                    <td>
                                        <div class="marked-changed">
                                            @if(in_array($record['id'], $updatedItems))
                                                <i class="fa-solid fa-triangle-exclamation unsaved" title="Unsaved changes"></i>
                                            @else
                                                <i class="fa-solid fa-check ready" title="No changes made"></i>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        #{{ $employeeIndex + 1 }}
                                    </td>
                                    <td>
                                        <a href="{{ route('hris.show', ['employee_no' => $record['employee_no'], 'form' => 'information']) }}"
                                        class="text-dark" target="_blank">
                                            {{ $record['name'] }}
                                        </a>
                                    </td>
                                    <td>{{ $record['position'] }}</td>
                                    <td>{{ number_format($record['basic_salary'], 2) }}</td>
                                    <td>{{ number_format($record['pera'], 2) }}</td>
                                    <td>{{ number_format($record['gross_amount_earned'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['rlip'], 2) }}</td>
                                    <td colspan="2">
                                        <input type="number" wire:change="recompute({{ $sectionIndex }}, {{ $employeeIndex }})"
                                            wire:model="hdmf.{{ $sectionIndex }}.{{ $employeeIndex }}"
                                            class="form-control {{ $isApproved ? 'restricted' : '' }}" style="width: 120px;" {{ $isApproved ? 'readonly' : '' }}>
                                    </td>
                                    <td colspan="2">{{ number_format($record['philhealth'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['consoloan'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['emergency_loan'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['plreg'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['mpl'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['cpl'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['mp2'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['mplstlms'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['cir375_cir449'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['w_tax'], 2) }}</td>
                                    <td colspan="2">
                                        <input type="number" wire:change="recompute({{ $sectionIndex }}, {{ $employeeIndex }})"
                                            wire:model="uca.{{ $sectionIndex }}.{{ $employeeIndex }}"
                                            class="form-control {{ $isApproved ? 'restricted' : '' }}" style="width: 120px;" {{ $isApproved ? 'readonly' : '' }}>
                                    </td>
                                    <td>{{ $record['aut'] }}</td>
                                    <td>{{ $record['aut'] }}</td>
                                    <td>{{ number_format($record['total_deductions'], 2) }}</td>
                                    <td>{{ number_format($record['net_amount'], 2) }}</td>
                                    <td colspan="2">
                                        <input type="text" wire:change="recompute({{ $sectionIndex }}, {{ $employeeIndex }})"
                                            wire:model="dbp.{{ $sectionIndex }}.{{ $employeeIndex }}"
                                            class="form-control {{ $isApproved ? 'restricted' : '' }}" style="width: 120px;" {{ $isApproved ? 'readonly' : '' }}>
                                    </td>
                                    <td colspan="2">
                                        <input type="text" wire:change="recompute({{ $sectionIndex }}, {{ $employeeIndex }})"
                                            wire:model="kawani.{{ $sectionIndex }}.{{ $employeeIndex }}"
                                            class="form-control {{ $isApproved ? 'restricted' : '' }}" style="width: 120px;" {{ $isApproved ? 'readonly' : '' }}>
                                    </td>
                                    <td colspan="2">{{ number_format($record['lbp_payroll_account'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['net_first_half'] ?? 0, 2) }}
                                                @if($record['is_first_half_locked'])
                                                    <span class="badge bg-success ms-1">Locked</span>
                                                @endif</td>
                                    <td colspan="2">{{ number_format($record['net_second_half'] ?? 0, 2) }}
                                            @if($record['is_second_half_locked'])
                                                <span class="badge bg-success ms-1">Locked</span>
                                            @endif</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="12" class="py-3 text-uppercase fw-bold text-muted">
                                    No data found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        @if($records['payroll']['employment_type']['id'] == '2')
            <div class="table-responsive pb-3">
                <table>
                    <thead>
                        <tr>
                            <th rowspan="2">No.</th>
                            <th rowspan="2" class="text-center">Name</th>
                            <th rowspan="2" class="text-center">Position</th>
                            <th rowspan="2" class="text-center">Basic Salary</th>
                            <th rowspan="2" class="text-center">Pera</th>
                            <th rowspan="2" class="text-center">Gross Amount Earned</th>
                            <th colspan="26" class="text-center">DEDUCTIONS: (GSIS, MPL, PHILHEALTH, AUT, and W/TAX)</th>
                            <th colspan="2" class="text-center">AUT</th>
                            <th colspan="8" class="text-center"></th>
                            <th colspan="10" class="text-center">Salary</th> 
                        </tr>
                        <tr>
                            <th colspan="2" class="vertical-text green">RLIP</th>
                            <th colspan="2" class="vertical-text yellow">HDMF</th>
                            <th colspan="2" class="vertical-text skyblue">PHIL HEALTH</th>
                            <th colspan="2" class="vertical-text green">CONSOLOAN</th>
                            <th colspan="2" class="vertical-text green">EMERGYLN</th>
                            <th colspan="2" class="vertical-text green">PLREG</th>
                            <th colspan="2" class="vertical-text green">MPL</th>
                            <th colspan="2" class="vertical-text green">CPL</th>
                            <th colspan="2" class="vertical-text yellow">MP2</th>
                            <th colspan="2" class="vertical-text yellow">MPL STLMS</th>
                            <th colspan="2" class="vertical-text yellow">CIR375, CIR449</th>
                            <th colspan="2" class="vertical-text red">W/TAX</th>
                            <th colspan="2" class="vertical-text red">UCA</th>
                            <th class="vertical-text grey">1st Half</th>
                            <th class="vertical-text grey">2nd Half</th>

                            <th class="text-center">TOTAL DED.</th>
                            <th class="text-center">NET AMOUNT</th>
                            <th colspan="2" class="vertical-text grey">DBP</th>
                            <th colspan="2" class="vertical-text grey">KAWANI</th>
                            <th colspan="2" class="vertical-text grey">LBP PAYROLL ACCOUNT</th>
                            <th colspan="2" class="text-center">First Half</th>
                            <th colspan="2" class="text-center">Second Half</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($records['payroll_items'] as $sectionIndex => $sectionGroup)
                            <tr class="fw-bold bg-primary text-white sticky-top" style="top: 55px; z-index: 9;">
                                <td colspan="100%">{{ $sectionGroup['section_name'] ?? 'Unknown Section' }}</td>
                            </tr>

                            @foreach($sectionGroup['employees'] as $employeeIndex => $record)
                                <tr>
                                    <td>#{{ $employeeIndex + 1 }}</td>
                                    <td>
                                        <a href="{{ route('hris.show', ['employee_no' => $record['employee_no'], 'form' => 'information']) }}"
                                        class="text-dark" target="_blank">
                                            {{ $record['name'] }}
                                        </a>
                                    </td>
                                    <td>{{ $record['position'] }}</td>
                                    <td>{{ number_format($record['basic_salary'], 2) }}</td>
                                    <td>{{ number_format($record['pera'], 2) }}</td>
                                    <td>{{ number_format($record['gross_amount_earned'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['rlip'], 2) }}</td>
                                    <td colspan="2">
                                        <input type="number" wire:change="recompute({{ $sectionIndex }}, {{ $employeeIndex }})"
                                            wire:model="hdmf.{{ $sectionIndex }}.{{ $employeeIndex }}"
                                            class="form-control {{ $isApproved ? 'restricted' : '' }}" style="width: 120px;" {{ $status === 'readonly' ? 'restricted' : '' }}>
                                    </td>
                                    <td colspan="2">{{ number_format($record['philhealth'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['consoloan'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['emergency_loan'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['plreg'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['mpl'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['cpl'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['mp2'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['mplstlms'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['cir375_cir449'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['w_tax'], 2) }}</td>
                                    <td colspan="2">
                                        <input type="number" wire:change="recompute({{ $sectionIndex }}, {{ $employeeIndex }})"
                                            wire:model="uca.{{ $sectionIndex }}.{{ $employeeIndex }}"
                                            class="form-control {{ $isApproved ? 'restricted' : '' }}" style="width: 120px;" {{ $isApproved ? 'readonly' : '' }}>
                                    </td>
                                    <td>{{ $record['aut'] }}</td>
                                    <td>{{ $record['aut'] }}</td>
                                    <td>{{ number_format($record['total_deductions'], 2) }}</td>
                                    <td>{{ number_format($record['net_amount'], 2) }}</td>
                                    <td colspan="2">
                                        <input type="text" wire:change="recompute({{ $sectionIndex }}, {{ $employeeIndex }})"
                                            wire:model="dbp.{{ $sectionIndex }}.{{ $employeeIndex }}"
                                            class="form-control {{ $isApproved ? 'restricted' : '' }}" style="width: 120px;" {{ $isApproved ? 'readonly' : '' }}>
                                    </td>
                                    <td colspan="2">
                                        <input type="text" wire:change="recompute({{ $sectionIndex }}, {{ $employeeIndex }})"
                                            wire:model="kawani.{{ $sectionIndex }}.{{ $employeeIndex }}"
                                            class="form-control {{ $isApproved ? 'restricted' : '' }}" style="width: 120px;" {{ $isApproved ? 'readonly' : '' }}>
                                    </td>
                                    <td colspan="2">{{ number_format($record['lbp_payroll_account'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['salary'], 2) }}</td>
                                    <td colspan="2">{{ number_format($record['salary'], 2) }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
    
    @if($product == 'private')
    <div class="table-responsive pb-3">
        <table>
            <thead>
                <tr>
                    <th></th>
                    <th>No.</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Basic Salary</th>
                    <th>Overtime</th>
                    <th>Holiday Pay</th>
                    <th>Night Differential</th>
                    <th>Allowances</th>
                    <th>Gross Amount</th>
                    <th>SSS</th>
                    <th>PhilHealth</th>
                    <th>Pagibig</th>
                    <th>W/Tax</th>
                    <th>AUT</th>
                    <th>Other Loans</th>
                    <th>Total Deductions</th>
                    <th>Net Amount</th>
                    <th>Bank Name</th>
                    <th>Bank Account</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records['payroll_items'] as $sectionIndex => $sectionGroup)
                    <tr class="fw-bold bg-primary text-white sticky-top" style="top: 55px; z-index: 9;">
                        <td colspan="100%">
                            <div class="d-flex justify-content-between w-100 px-5">
                                <span>{{ $sectionGroup['section_name'] ?? 'Unknown Section' }}</span>
                                <span class="text-center flex-grow-1">{{ $sectionGroup['section_name'] ?? 'Unknown Section' }}</span>
                                <span>{{ $sectionGroup['section_name'] ?? 'Unknown Section' }}</span>
                            </div>
                        </td>
                    </tr>

                    @foreach($sectionGroup['employees'] as $employeeIndex => $record)
                        <tr>
                            <td>
                                <div class="marked-changed">
                                    @if(in_array($record['id'] ?? null, $updatedItems ?? []))
                                        <i class="fa-solid fa-triangle-exclamation unsaved" title="Unsaved changes"></i>
                                    @else
                                        <i class="fa-solid fa-check ready" title="No changes made"></i>
                                    @endif
                                </div>
                            </td>
                            <td>#{{ $employeeIndex + 1 }}</td>
                            <td>
                                <a href="{{ route('hris.show', ['employee_no' => $record['employee_no'], 'form' => 'information']) }}"
                                class="text-dark" target="_blank">
                                    {{ $record['name'] }}
                                </a>
                            </td>
                            <td>{{ $record['position'] }}</td>
                            <td>{{ number_format($record['basic_salary'], 2) }}</td>
                            <td>{{ number_format($record['overtime_pay'], 2) }}</td>
                            <td>{{ number_format($record['holiday_pay'], 2) }}</td>
                            <td>{{ number_format($record['night_differential'] ?? 0, 2) }}</td>
                            <td>{{ number_format($record['allowances'], 2) }}</td>
                            <td>{{ number_format($record['gross_amount_earned'], 2) }}</td>
                            <td>{{ number_format($record['sss'], 2) }}</td>
                            <td>{{ number_format($record['philhealth'], 2) }}</td>
                            <td>{{ number_format($record['pagibig'], 2) }}</td>
                            <td>{{ number_format($record['w_tax'], decimals: 2) }}</td>
                            <td>{{ number_format($record['aut'], decimals: 2) }}</td>
                            <td>{{ number_format($record['other_loans'], 2) }}</td>
                            <td>{{ number_format($record['total_deductions'], 2) }}</td>
                            <td>{{ number_format($record['net_amount'], 2) }}</td>
                            <td>
                                {{ $record['bank_name'] }}
                            </td>
                            <td>
                                {{ $record['bank_account'] }}
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="20" class="py-3 text-uppercase fw-bold text-muted">
                            No payroll data found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif

    @if($hasChanges)
        <div class="d-flex justify-content-end mt-5">
            <button class="btn btn-primary px-5 py-3 text-uppercase" wire:loading.attr="disabled" wire:click="save">
                <span wire:loading.remove wire:target="save">Save Changes</span>
                <span wire:loading wire:target="save">
                    Saving <i class="fa-solid fa-spinner fa-spin"></i>
                </span>
            </button>
        </div>
    @endif

    @if(!$isApproved && !$hasChanges)
        <div class="d-flex justify-content-end mt-5">
            <button class="btn btn-primary px-5 py-3 text-uppercase" wire:loading.attr="disabled" wire:click="approve">
                <span wire:loading.remove wire:target="approve">Approve</span>
                <span wire:loading wire:target="approve">
                    Please Wait <i class="fa-solid fa-spinner fa-spin"></i>
                </span>
            </button>
        </div>
    @endif
</div>