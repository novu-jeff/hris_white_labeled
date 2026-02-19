<?php

namespace App\Http\Controllers\Admin\Services\Payroll;

use App\Http\Controllers\Admin\Services\PayrollService;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Services\OtherServices;
use App\Http\Controllers\Admin\Services\LeaveCardService;
use App\Jobs\PayrollJob;
use App\Models\EmployementTypes;
use App\Models\SalaryPayroll;
use App\Services\ContributionsService;
use App\Services\SummaryServices;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\DailyTimeRecordService;
use Illuminate\Support\Facades\Log;
use App\Models\Loan;
use App\Models\Setting;

class SalaryService extends Controller {

    protected $product;
    protected $bsd_emp_identical;
    protected $payrollService;

    public function __construct(PayrollService $payrollService) {
        $this->product = config('app.product');
        $this->bsd_emp_identical = config('app.bsd_emp_identical');
        $this->payrollService = $payrollService;
    }

    public function getPayroll(int $payroll_id) {

        $payroll = SalaryPayroll::with('items.information.section', 'employment_type')->findOrFail($payroll_id);

        $payroll->formatted_payroll_date = Carbon::parse($payroll->payroll_date)->format('F d, Y');

        if(!is_null($payroll->cut_off_period)) {
            [$startPeriod, $endPeriod] = explode(' to ', $payroll->cut_off_period);
            $payroll->formatted_cutoff_period = sprintf(
                '%s - %s',
                Carbon::parse(trim($startPeriod))->format('M d, Y'),
                Carbon::parse(trim($endPeriod))->format('M d, Y')
            );
        } else {
            $payroll->formatted_cutoff_period = null;
        }

        $employmentType = EmployementTypes::find($payroll->employment_type);
        $payroll->formatted_employment_type = $employmentType->name ?? '';

        $payroll->no_employees = $payroll->items->count();

        $netAmount = $payroll->items->sum(fn($item) => (float) str_replace(',', '', $item->net_amount));
        $salaryAmount = $payroll->items->sum(fn($item) => (float) str_replace(',', '', $item->salary));
        $grossAmount = $payroll->items->sum(fn($item) => (float) str_replace(',', '', $item->gross_amount_earned));

        $payroll->overall_net_amount = round($netAmount, 2);
        $payroll->overall_salary = round($salaryAmount, 2);
        $payroll->overall_gross_amount = round($grossAmount, 2);
        $payroll->type = 'Salary Payroll';

        $grouped = [];

        foreach ($payroll->items as $item) {
            $section = $item->information->section ?? null;

            if (!$section) continue;

            $sectionId = $section->id;
            $sectionName = $section->name;

            if (!isset($grouped[$sectionId])) {
                $grouped[$sectionId] = [
                    'section_id' => $sectionId,
                    'section_name' => $sectionName,
                    'employees' => [],
                ];
            }

            $employeeRow = $item->toArray() ?? [];

            // Fill bank name/account from employee/department when not stored on payroll item
            if (empty($employeeRow['bank_account']) || empty($employeeRow['bank_name'])) {
                $info = $item->information;
                if ($info) {
                    if (empty($employeeRow['bank_account'])) {
                        $employeeRow['bank_account'] = $info->bank_account_no ?? $info->payroll_account_number ?? null;
                    }
                    if (empty($employeeRow['bank_name'])) {
                        // Prefer employee's Payroll Bank (and "Other" custom name), then default setting, then department bank
                        $empBank = $info->payroll_bank ? trim($info->payroll_bank) : null;
                        if ($empBank) {
                            $employeeRow['bank_name'] = (strtolower($empBank) === 'other' && !empty($info->payroll_bank_other))
                                ? trim($info->payroll_bank_other)
                                : $empBank;
                        } else {
                            $employeeRow['bank_name'] = Setting::get('payroll_bank_default', '');
                        }
                        if (empty($employeeRow['bank_name']) && $section->department_id) {
                            $bank = \App\Models\BankInformations::where('department_id', $section->department_id)->first();
                            $employeeRow['bank_name'] = $bank->name ?? null;
                        }
                    }
                }
            }

            $grouped[$sectionId]['employees'][] = $employeeRow;
        }

        $items = array_values($grouped);

        return [
            'payroll' => $payroll->toArray() ?? [],
            'payroll_items' => $items,
            'batch_id' => $payroll->batch_id ?? null,
        ];
    
    }

    public function rules(array $payload)
    {
        return [
            'payroll_date' => [
                'required',
                'date',
            ],
            'cut_off_period' => [
                'required',
                'regex:/^\d{4}-\d{2}-\d{2} to \d{4}-\d{2}-\d{2}$/',
            ],
            'employment_type' => 'exists:employment_types,id',
            'has_deductions' => 'nullable|boolean'
        ];
    }

    public function createPayroll($payload) {
        $payroll = SalaryPayroll::create([
            'payroll_date' => $payload['payroll_date'],
            'cut_off_period' => $payload['cut_off_period'],
            'employment_type' => $payload['employment_type'],
            'hasDeductions' => $payload['has_deductions'] ?? false,
            'status' => 'pending'
        ]);

        return $payroll;

    }

    public function generateChunks(int $payroll_id, int $employment_type, string $type) {

        $payroll = SalaryPayroll::findOrFail($payroll_id);

        if(!$payroll) {
            return [
                'status' => 'error',
                'message' => 'Error occured: Unknown payroll_id ' . $payroll_id
            ];
        }

        $employees = $this->payrollService->getEmployees($employment_type, $type);
        $employees = $employees['eligible']['items'];

        $chunks = array_chunk($employees, 1000);

        $jobs = [];

       // dd('hre');

        foreach ($chunks as $chunk) {
           // dd('set');
            $jobs[] = new PayrollJob(
                                    $chunk,          // already an array
                                    $payroll->id,    // pass only ID
                                    'salary'
                                );
        }

        $payroll_date = Carbon::parse($payroll->payroll_date)->format('M d, Y');

        if(!empty($jobs)) {
            return [
                'status' => 'success',
                'jobs' => $jobs,
                'name' => 'Payroll For ' . $payroll_date,
                'payroll' => $payroll
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'No jobs were processed'
            ];
        }

    }

    public function computePayroll($payroll, $employees, $type) {

                \Log::info('Start computePayroll', [
            'payroll_id' => $payroll->id,
            'employees_count' => count($employees),
            'type' => $type
        ]);

        $hasDeductions = $payroll->hasDeductions ?? false;

      


        if ($this->product == 'government') {

                    \Log::info('Start computePayroll Government', [
            'payroll_id' => $payroll->id,
            'employees_count' => count($employees),
            'type' => $type
        ]);

            $other_service = new OtherServices;
            $dtr_service = new DailyTimeRecordService;
            $leaveCard_service = new LeaveCardService;
            $payroll_service = app(PayrollService::class);

            $data = [];

            foreach ($employees as $employee) {
                $employee_no = $employee['employee_no'];
                $name = trim($employee['firstname'] . ' ' . $employee['lastname']);
                $position = $employee['position_name'];
                $basic_salary = round(floatval($employee['salary']), 2);
                $salary_type = $employee['salary_type'];
                $isTimelogExempted = (bool) ($employee['is_timelog_exempted'] ?? false);
                $gw_tax = $employee['w_tax'];
                $rate = 0.05;
                $ceiling = 100000;

                Log::info('GW TAX RAW VALUE', [
                    'payroll_id' => $payroll->id,
                    'employee_no' => $employee_no,
                    'employee_name' => $name,
                    'gw_tax_raw' => $gw_tax,
                    'gw_tax_type' => gettype($gw_tax),
                    'has_deductions' => $hasDeductions,
                    'employee_keys' => array_keys($employee),
                ]);

                $monthYear = Carbon::parse($payroll->payroll_date)->format('m-Y');
                $cut_off_period = $other_service->splitDateRange($payroll->cut_off_period);

                $dtr = $dtr_service->getDailyTimeRecord($employee_no, $cut_off_period);

                $dtr_summary  = $dtr['summary'];

                $overtimeData = $payroll_service->computeOvertimePay($basic_salary, $dtr_summary['worked_days'], $dtr_summary['overtime_minues']);
                
                $overtime = $overtimeData['gross_ot_pay'];

                $earnings = $other_service->earnings($employee_no);
                $deductions = $hasDeductions ? $other_service->deductions($employee_no) : [];

                $current_date = Carbon::parse($payroll->payroll_date)->format('m/Y');
                $social_security = $hasDeductions
                    ? DB::table('social_security as gb')
                        ->join('social_security_items as gi', 'gb.id', '=', 'gi.social_security_id')
                        ->where('gb.billing_month', $current_date)
                        ->where('gi.crn_no', $employee['gsis_no'])
                        ->select('gi.consoloan', 'gi.emrgy_loan', 'gi.plreg', 'gi.mpl', 'gi.cpl')
                        ->first() ?? (object) []
                    : (object) [];
               

                // Earnings
                $pera = round(floatval(collect($earnings)->firstWhere('code', 'PERA')['amount'] ?? 0), 2);
                $gross = round($basic_salary + $pera, 2);

              //  dd($hasDeductions, $social_security->consoloan );

                // Deductions (based on flag). Per Management: employee deductions divided into two cut-offs per month (half per cut-off).
                $rlip = $hasDeductions ? round(floatval($basic_salary * 0.09) / 2, 2) : 0;
                $philhealth = $hasDeductions
                    ? round(min($basic_salary, $ceiling) * $rate / 2 / 2, 2)
                    : 0;
                $hdmf = $hasDeductions ? round(floatval(collect($deductions)->firstWhere('deduction.code', 'HDMF')['amount'] ?? 0) / 2, 2) : 0;
                $mp2 = $hasDeductions ? round(floatval(collect($deductions)->firstWhere('deduction.code', 'MP2')['amount'] ?? 0) / 2, 2) : 0;
                $mplstlms = $hasDeductions ? round(floatval(collect($deductions)->firstWhere('deduction.code', 'MPLSTLMS')['amount'] ?? 0) / 2, 2) : 0;
                $cir = $hasDeductions ? round(floatval(collect($deductions)->firstWhere('deduction.code', 'CIR375, CIR449')['amount'] ?? 0) / 2, 2) : 0;
                $w_tax = $hasDeductions ? round(floatval($gw_tax ?? 0) / 2, 2) : 0;
                $uca = $hasDeductions ? round(floatval(collect($deductions)->firstWhere('deduction.code', 'Unliquidated_Cash_Advances')['amount'] ?? 0) / 2, 2) : 0;
                $consoloan = $hasDeductions ? round(floatval($social_security->consoloan ?? 0) / 2, 2) : 0;
                $emergency_loan = $hasDeductions ? round(floatval($social_security->emrgy_loan ?? 0) / 2, 2) : 0;
                $plreg = $hasDeductions ? round(floatval($social_security->plreg ?? 0) / 2, 2) : 0;
                $mpl = $hasDeductions ? round(floatval($social_security->mpl ?? 0) / 2, 2) : 0;
                $cpl = $hasDeductions ? round(floatval($social_security->cpl ?? 0) / 2, 2) : 0;
                $aut = $hasDeductions
                    ? round(floatval($payroll_service->computeAutDeduction($dtr_summary, $basic_salary, $salary_type, $isTimelogExempted)) / 2, 2)
                    : 0;

                // Optional deductions (half per cut-off)
                $dbp = $hasDeductions ? round(floatval(collect($deductions)->firstWhere('deduction.code', 'DBP Savings')['amount'] ?? 0) / 2, 2) : 0;
                $kawani = $hasDeductions ? round(floatval(collect($deductions)->firstWhere('deduction.code', 'Unlad Kawani')['amount'] ?? 0) / 2, 2) : 0;

                $total_deduction = round(
                    $rlip + $hdmf + $philhealth + $consoloan + $emergency_loan +
                    $plreg + $mpl + $cpl + $mp2 + $mplstlms + $cir + $w_tax + $aut
                );

                Log::info('GW TAX BEFORE COMPUTE', [
                    'employee_no' => $employee_no,
                    'gw_tax_before_round' => $gw_tax,
                    'has_deductions' => $hasDeductions,
                ]);

                $net = round($gross - $total_deduction, 2);
                $half = round($net / 2, 2);
                $firstHalf  = floor(($net / 2) * 100) / 100;
                $secondHalf = round($net - $firstHalf, 2);

                $data[] = [
                    'payroll_id' => $payroll->id,
                    'employee_no' => $employee_no,
                    'name' => $name,
                    'position' => $position,
                    'basic_salary' => $basic_salary,
                    'pera' => $pera,
                    'gross_amount_earned' => $gross,
                    'overtime_pay' => $overtime,
                    'rlip' => $rlip,
                    'hdmf' => $hdmf,
                    'philhealth' => $philhealth,
                    'consoloan' => $consoloan,
                    'emergency_loan' => $emergency_loan,
                    'plreg' => $plreg,
                    'mpl' => $mpl,
                    'cpl' => $cpl,
                    'mp2' => $mp2,
                    'mplstlms' => $mplstlms,
                    'cir375_cir449' => $cir,
                    'w_tax' => $w_tax,
                    'uca' => $uca,
                    'aut' => $aut,
                    'total_deductions' => $total_deduction,
                    'net_amount' => $net,
                    'dbp' => $dbp,
                    'kawani' => $kawani,
                    'lbp_payroll_account' => $net,
                    'salary' => $half,
                    'net_first_half' => $firstHalf,
                    'net_second_half' => $secondHalf,
                    'is_first_half_locked' => 0,
                    'is_second_half_locked' => 0,
                ];
            }

            return $data;

        } else {

                    \Log::info('Start computePayroll', [
                'payroll_id' => $payroll->id,
                'employees_count' => count($employees),
                'type' => $type,
                'deduc' => $hasDeductions
            ]);        

            $other_service = new OtherServices;
            $dtr_service = new DailyTimeRecordService;
            $leaveCard_service = new LeaveCardService;
            $contribution_service = new ContributionsService;
            $payroll_service = app(PayrollService::class);

            $data = [];

            foreach ($employees as $employee) {
                $employee_no = $employee['employee_no'];
                $name = trim($employee['firstname'] . ' ' . $employee['lastname']);
                $position = $employee['position_name'];
                $basic_salary = round(floatval($employee['salary']), 2);


                 // Fetch approved loans for this employee
                $employeeLoans = Loan::where('employee_no', $employee_no)
                    ->where('status', 'approved')
                    ->get();

                \Log::info('Employee loans fetched', [
                    'employee_no' => $employee_no,
                    'loans_count' => $employeeLoans->count(),
                    'cut_period' => $payroll->cut_off_period,
                    'loan_ids' => $employeeLoans->pluck('id')->toArray(),
                ]);    

                $loanDeductionsToInsert = [];
                $other_loans = 0;

                foreach ($employeeLoans as $loan) {
                    if ($loan->balance > 0) {
                        // Per Management: deductions divided into two cut-offs per month (half per cut-off)
                        $deductionAmount = round($loan->monthly_amortization / 2, 2);
                        $other_loans += $deductionAmount;

                        $loanDeductionsToInsert[] = [
                            'payroll_item_id' => 0, // updated later
                            'reference_type' => 'loan',
                            'reference_id' => $loan->id,
                            'description' => 'Loan deduction: ' . ($loan->loanType->name ?? 'Loan'),
                            'amount' => $deductionAmount,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                    } else {
                        // Loan fully paid, mark as complete
                        $loan->status = 'completed';
                        $loan->save();
                    }    
                }

                $salary_rate = round(floatval($employee['salary']), 2);
                $salary_type = strtolower(trim((string) ($employee['salary_type'] ?? 'monthly')));
                $isTimelogExempted = (bool) ($employee['is_timelog_exempted'] ?? false);

                $cut_off_period = $other_service->splitDateRange($payroll->cut_off_period);

                $dtr = $dtr_service->getDailyTimeRecord($employee_no, $cut_off_period);

                $dtr_summary  = $dtr['summary'];

                $basic_salary = $this->computeCutoffBasicSalary($salary_rate, $salary_type, $dtr_summary);

                $overtimeData = $payroll_service->computeOvertimePay($salary_rate, $dtr_summary['worked_days'], $dtr_summary['overtime_minues']);
                
                $overtime = $overtimeData['gross_ot_pay'];

                $holiday_pay = round($payroll_service->computeHolidayPayment($salary_rate, $dtr_summary, $salary_type), 2);
                $allowances = $this->computeCutoffAllowance(floatval($employee['allowance'] ?? 0), $salary_type);
                
                // Per Management: employee deductions divided into two cut-offs per month (half per cut-off)
                $aut = round(floatval($payroll_service->computeAutDeduction($dtr_summary, $salary_rate, $salary_type, $isTimelogExempted)) / 2, 2);

                $sssMonthly = $hasDeductions ? ($contribution_service->computeSSS($salary_rate)['employee_share'] ?? 0) : 0;
                $pagibigMonthly = $hasDeductions ? ($contribution_service->computePagibig($salary_rate)['employee_share'] ?? 0) : 0;
                $philhealthMonthly = $hasDeductions ? ($contribution_service->computePhilHealth($salary_rate)['employee_share'] ?? 0) : 0;
                $sss = round($sssMonthly / 2, 2);
                $pagibig = round($pagibigMonthly / 2, 2);
                $philhealth = round($philhealthMonthly / 2, 2);

                $night_differential = $payroll_service->computeNightShiftDifferential($salary_rate, $dtr_summary, $salary_type);
                $gross_amount_earned = $basic_salary + $overtime + $holiday_pay + $allowances + $night_differential;

                $total_contributions = $sss + $pagibig + $philhealth;
                $monthly_taxable = max(0, $salary_rate - ($sssMonthly + $pagibigMonthly + $philhealthMonthly));
                $w_tax_monthly = $hasDeductions ? $contribution_service->computeWithholdingTax($monthly_taxable) : 0;
                $w_tax = round($w_tax_monthly / 2, 2);
                $total_deductions = $sss + $pagibig + $philhealth + $w_tax + $other_loans + $aut;
                $net_amount = $gross_amount_earned - $total_deductions;
                
                $bank_account = $employee['account_no'] ?? null;
                $bank_name = $employee['bank'] ?? null;

                Log::info('Payroll computation (NON-GOV)', [
    'payroll_id' => $payroll->id,
    'employee_no' => $employee_no,
    'name' => $name,

    // INPUTS
    'basic_salary' => $basic_salary,
    'salary_type' => $salary_type,
    'cut_off_period' => $payroll->cut_off_period,
    'dtr_summary' => $dtr_summary,

    // EARNINGS
    'overtime' => $overtime,
    'holiday_pay' => $holiday_pay,
    'night_differential' => $night_differential,
    'allowances' => $allowances,
    'gross_amount_earned' => $gross_amount_earned,

    // DEDUCTIONS
    'sss' => $sss,
    'pagibig' => $pagibig,
    'philhealth' => $philhealth,
    'w_tax' => $w_tax,
    'aut' => $aut,
    'other_loans' => $other_loans,
    'total_deductions' => $total_deductions,

    // RESULT
    'net_amount' => $net_amount,
    'bank_account' => $bank_account,
    'bank_name' => $bank_name,
]);


                $data[] = [
                    'payroll_id' => $payroll->id,
                    'employee_no' => $employee_no,
                    'name' => $name,
                    'position' => $position,
                    'basic_salary' => $basic_salary,
                    'overtime_pay' => $overtime,
                    'holiday_pay' => $holiday_pay,
                    'night_differential' => $night_differential,
                    'allowances' => $allowances,
                    'aut' => $aut,
                    'gross_amount_earned' => round($gross_amount_earned, 2),
                    'sss' => round($sss, 2),
                    'pagibig' => round($pagibig, 2),
                    'philhealth' => round($philhealth, 2),
                    'w_tax' => round($w_tax, 2),
                    'other_loans' => round($other_loans, 2),
                    'loan_deductions' => $loanDeductionsToInsert,
                    'total_deductions' => round($total_deductions, 2),
                    'net_amount' => round($net_amount, 2),
                    'bank_account' => $bank_account,
                    'bank_name' => $bank_name,
                ];
            }

            return $data;

        }

    }
    
    private function computeCutoffBasicSalary(float $salaryRate, string $salaryType, array $dtrSummary): float
    {
        if ($salaryType === 'daily') {
            $workedDays = (float) ($dtrSummary['worked_days'] ?? 0);
            return round($salaryRate * $workedDays, 2);
        }

        // Salary payroll runs per cut-off period (first half / second half).
        return round($salaryRate / 2, 2);
    }

    private function computeCutoffAllowance(float $allowance, string $salaryType): float
    {
        if ($allowance <= 0) {
            return 0;
        }

        if ($salaryType === 'daily') {
            return round($allowance, 2);
        }

        return round($allowance / 2, 2);
    }



}