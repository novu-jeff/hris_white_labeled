<?php

namespace App\Http\Controllers\Admin\Services;

use App\Http\Controllers\Admin\Services\Payroll\BonusService;
use App\Http\Controllers\Admin\Services\Payroll\ClothingAllowanceService;
use App\Http\Controllers\Admin\Services\Payroll\OverTimeService;
use App\Http\Controllers\Admin\Services\Payroll\SalaryService;
use App\Http\Controllers\Controller;
use App\Models\BonusItemsPayroll;
use App\Models\BonusPayroll;
use App\Models\ClothingAllowanceItemsPayroll;
use App\Models\ClothingAllowancePayroll;
use App\Models\EmployementTypes;
use App\Models\OTItemsPayroll;
use App\Models\OTPayroll;
use App\Models\SalaryItemsPayroll;
use App\Models\SalaryPayroll;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService extends Controller {

    protected $bsd_emp_identical;
    protected string $product;

    public function __construct() {
        $this->product = config('app.product');
        $this->bsd_emp_identical = config('app.bsd_emp_identical');
    }

    public function getEmployees($employment_type, $type = null)
    {
        $currentYear = now()->year;
        $internTypeId = EmployementTypes::where('name', 'like', '%intern%')->value('id');

        $results = DB::table('employee_information as ei')
            ->select(
                'ei.id as employee_id',
                'ei.employee_no',
                'ei.employment_type_id',
                'ei.date_hired',
                'ei.salary',
                'ei.allowance',
                'ei.is_timelog_exempted',
                'ei.bsd_no',
                'ei.w_tax',
                'ei.position_id',
                'ei.salary_type',
                'ei.bank_account_no',
                'ei.payroll_account_number',
                'ei.payroll_bank',
                'ei.payroll_bank_other',
                'p.firstname',
                'p.lastname',
                'p.gsis_no',
                'po.name as position_name',
                'po.salary_grade',
                's.name as section_name',
                'bi.name as bank_name'
            )
            ->join('employee_personal as p', 'ei.employee_no', '=', 'p.employee_no')
            ->leftJoin('positions as po', 'ei.position_id', '=', 'po.id')
            ->leftJoin('sections as s', 'ei.section_id', '=', 's.id')
            ->leftJoin('bank_informations as bi', 's.department_id', '=', 'bi.department_id')
            ->where(function ($query) use ($employment_type) {
                $query->where('ei.employment_type_id', $employment_type)
                    ->orWhereNull('ei.employment_type_id'); // include missing type
            })
            ->where('ei.status', 'active')    // only active employees
            ->where('ei.isDeleted', 0)        // only not deleted
            ->get();

         //  dd($results);

        $employees = [
            'eligible' => [
                'count' => 0,
                'items' => []
            ],
            'ineligible' => [
                'count' => 0,
                'items' => []
            ],
        ];

        foreach ($results as $row) {
            $reasons = [];

            if (empty($row->employment_type_id)) {
                $reasons[] = 'no employment type';
            }

            $dateHired = $row->date_hired ? Carbon::parse($row->date_hired) : null;

            if ($this->product === 'government') {
                if (empty($row->bsd_no)) {
                    $reasons[] = 'no BSD number';
                }

                if (empty($row->position_id)) {
                    $reasons[] = 'no position assigned';
                }

                if ($type === 'mid_year') {
                    $may15 = Carbon::create($currentYear, 5, 15);
                    $july1Prev = Carbon::create($currentYear - 1, 7, 1);

                    if (!$dateHired || $dateHired->gt($may15)) {
                        $reasons[] = 'not in service as of May 15';
                    }

                    if (!$dateHired || $dateHired->gt($july1Prev)) {
                        if ($dateHired->diffInMonths($may15) < 4) {
                            $reasons[] = 'less than 4 months of service from July 1 to May 15';
                        }
                    }
                }

                if ($type === 'year_end') {
                    $oct31 = Carbon::create($currentYear, 10, 31);
                    $jan1 = Carbon::create($currentYear, 1, 1);

                    if (!$dateHired || $dateHired->gt($oct31)) {
                        $reasons[] = 'not in service as of October 31';
                    }

                    if (!$dateHired || $dateHired->gt($jan1)) {
                        if ($dateHired->diffInMonths($oct31) < 4) {
                            $reasons[] = 'less than 4 months of service from January 1 to October 31';
                        }
                    }
                }
            }

            $isIntern = $internTypeId !== null && (string) $row->employment_type_id === (string) $internTypeId;
            $hasSalary = !empty($row->salary) && floatval($row->salary) > 0;
            $hasAllowance = !empty($row->allowance) && floatval($row->allowance) > 0;

            if (!$isIntern && !$hasSalary) {
                $reasons[] = 'no salary rate';
            }

            if ($isIntern && !$hasSalary && !$hasAllowance) {
                $reasons[] = 'no salary or allowance rate';
            }

            $employeeData = (array) $row;
            $employeeData['name'] = trim(($row->firstname ?? '') . ' ' . ($row->lastname ?? ''));
            $employeeData['status'] = $reasons ? 'ineligible' : 'eligible';
            $employeeData['reason'] = $reasons ?: null;
            $employeeData['account_no'] = $row->bank_account_no ?? $row->payroll_account_number ?? null;
            // Prefer employee's Payroll Bank (and "Other" custom name), then department bank, then default setting
            $bankFromEmployee = null;
            if (!empty($row->payroll_bank)) {
                $bankFromEmployee = (strtolower(trim($row->payroll_bank)) === 'other' && !empty($row->payroll_bank_other))
                    ? trim($row->payroll_bank_other)
                    : trim($row->payroll_bank);
            }
            $employeeData['bank'] = $bankFromEmployee ?? $row->bank_name ?? Setting::get('payroll_bank_default', '');

            $status = $employeeData['status'];
            $employees[$status]['items'][] = $employeeData;
            $employees[$status]['count']++;
        }
//dd($employees);
        return $employees;
    }

    public function getProcess(string $type) {

        $map = [
            'salary' => [
                'service' => SalaryService::class,
                'models' => [
                    'parent' => SalaryPayroll::class,
                    'child' => SalaryItemsPayroll::class,
                ]
            ],
            'clothing_allowance' => [
                'service' => ClothingAllowanceService::class,
                'models' => [
                    'parent' => ClothingAllowancePayroll::class,
                    'child' => ClothingAllowanceItemsPayroll::class,
                ]
            ],
            'mid_year' => [
                'service' => BonusService::class,
                'models' => [
                    'parent' => BonusPayroll::class,
                    'child' => BonusItemsPayroll::class,
                ]
            ],
            'year_end' => [
                'service' => BonusService::class,
                'models' => [
                    'parent' => BonusPayroll::class,
                    'child' => BonusItemsPayroll::class,
                ]
            ],
            'ot_pay' => [
                'service' => OverTimeService::class,
                'models' => [
                    'parent' => OTPayroll::class,
                    'child' => OTItemsPayroll::class,
                ]
            ]

        ];

        return $map[$type] ?? null;

    }

    public function computeWithholdingTax(float $taxableIncome): float
    {
        return \App\Services\ContributionsService::computeWithholdingTax($taxableIncome);
    }

    /**
     * Night shift differential: 10% for work between 10pm–6am (configurable via settings).
     */
    public function computeNightShiftDifferential(float $basicSalary, array $dtrSummary, string $payType): float
    {
        $minutes = (int) ($dtrSummary['night_shift_minutes'] ?? 0);
        if ($minutes <= 0) {
            return 0;
        }

        $rate = (float) (\App\Models\Setting::get('night_shift_differential', 10) / 100);
        $workPerWeek = (int) ($dtrSummary['workingDaysPerWeek'] ?? 5);
        $daysPerMonth = $workPerWeek > 5 ? 26 : 22;
        $dailyRate = $basicSalary / $daysPerMonth;
        $hourlyRate = $dailyRate / 8;
        $nightHours = $minutes / 60;

        return round($nightHours * $hourlyRate * $rate, 2);
    }

    public function computeBonusTax(float $bonus, float $cashGift = 0): float
    {
        $total = $bonus + $cashGift;
        $exemptLimit = 90000; 

        if ($total <= $exemptLimit) {
            return 0;
        }

        $taxable = $total - $exemptLimit;
        $rate = 0.20;

        return round($taxable * $rate, 2);
    }

    public function computeOvertimePay(float $basic_salary, int $workedDays, string $time_in_minutes): array
    {
        
        if ($workedDays <= 0) {
            return [
                'overtime_hours' => 0,
                'hourly_rate' => 0,
                'ot_rate' => 0,
                'gross_ot_pay' => 0,
                'tax' => 0,
                'net_ot_pay' => 0,
            ];
        }

        $decimalHours = floatval($time_in_minutes) / 60;
        $dailyRate = $basic_salary / $workedDays;
        $hourlyRate = $dailyRate / 8;
        $otRate = $hourlyRate * 1.25;
        $grossOtPay = $decimalHours * $otRate;
        $tax = $this->computeWithholdingTax($grossOtPay);
        $netOtPay = round($grossOtPay - $tax, 2);

        return [
            'overtime_hours' => round($decimalHours, 2),
            'hourly_rate' => round($hourlyRate, 2),
            'ot_rate' => round($otRate, 2),
            'gross_ot_pay' => round($grossOtPay, 2),
            'tax' => round($tax, 2),
            'net_ot_pay' => $netOtPay,
        ];
    }

    public function computeAutDeduction(array $summary, $salary, $payType, bool $isTimelogExempted = false)
    {
        if ($isTimelogExempted) {
            return 0;
        }

        $payType = strtolower(trim((string) $payType));
        $totalAbsences = $summary['absences'];          # Days
        $workPerWeek = $summary['workingDaysPerWeek'];  # 5 or 6 days
        $tardiness_mins = $summary['tardiness'];        # Minutes
        $undertime_mins = $summary['undertime'];        # Minutes

        $TOTAL_AUT = 0;

        if ($payType === 'monthly') {
            # DOLE standard: 22 or 26 working days per month
            $daysPerMonth = $workPerWeek > 5 ? 26 : 22;

            $daily_rate = $salary / $daysPerMonth;
            $hourly_rate = $daily_rate / 8; # 8 hours per day
            $minute_rate = $hourly_rate / 60;

            $absenceDeduction = $daily_rate * $totalAbsences;
            $tardinessDeduction = $minute_rate * $tardiness_mins;
            $undertimeDeduction = $minute_rate * $undertime_mins;

            $TOTAL_AUT = $absenceDeduction + $tardinessDeduction + $undertimeDeduction;
        }

        if ($payType === 'daily') {
            # If salary is daily rate directly
            $daily_rate = $salary;
            $hourly_rate = $daily_rate / 8; # 8 hours per day
            $minute_rate = $hourly_rate / 60;

            $absenceDeduction = $daily_rate * $totalAbsences;
            $tardinessDeduction = $minute_rate * $tardiness_mins;
            $undertimeDeduction = $minute_rate * $undertime_mins;

            $TOTAL_AUT = $absenceDeduction + $tardinessDeduction + $undertimeDeduction;
        }

        return $TOTAL_AUT;
    }

   public function computeHolidayPayment($salary, $summary, $payType) 
    {
        $payType = strtolower(trim((string) $payType));
        $work_on_legal_hol = $summary['worked_on_legal_holidays'];
        $work_on_special_hol = $summary['worked_on_special_holidays'];
        $workPerWeek = $summary['workingDaysPerWeek'];  # 5 or 6 days

        $legal_hol = 0;
        $special_hol = 0;

        if ($payType === 'monthly') {
            # Assume: 22 days for 5-day work week, 26 for 6-day
            $daysPerMonth = $workPerWeek > 5 ? 26 : 22;

            $daily_rate = $salary / $daysPerMonth;

            if ($work_on_legal_hol > 0) {
                $legal_hol = $daily_rate * 2 * $work_on_legal_hol;
            }

            if ($work_on_special_hol > 0) {
                $special_hol = $daily_rate * 1.3 * $work_on_special_hol;
            }
        }

        if ($payType === 'daily') {
            $daily_rate = $salary;

            if ($work_on_legal_hol > 0) {
                $legal_hol = $daily_rate * 2 * $work_on_legal_hol;
            }

            if ($work_on_special_hol > 0) {
                $special_hol = $daily_rate * 1.3 * $work_on_special_hol;
            }
        }

        return round($legal_hol + $special_hol, 2);
    }

}