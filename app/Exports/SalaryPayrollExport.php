<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalaryPayrollExport implements FromArray, WithHeadings
{
    public function __construct(
        protected array $records,
        protected string $product
    ) {
    }

    public function headings(): array
    {
        if ($this->product === 'government') {
            return [
                'Section',
                'Employee No',
                'Name',
                'Position',
                'Basic Salary',
                'PERA',
                'Gross Amount Earned',
                'RLIP',
                'HDMF',
                'PhilHealth',
                'Conso Loan',
                'Emergency Loan',
                'PLREG',
                'MPL',
                'CPL',
                'MP2',
                'MPL STLMS',
                'CIR375, CIR449',
                'W/Tax',
                'UCA',
                'AUT',
                'Total Deductions',
                'Net Amount',
                'DBP',
                'Kawani',
                'LBP Payroll Account',
                'Net First Half',
                'Net Second Half',
            ];
        }

        return [
            'Section',
            'Employee No',
            'Name',
            'Position',
            'Basic Salary',
            'Overtime Pay',
            'Holiday Pay',
            'Allowances',
            'Gross Amount Earned',
            'SSS',
            'PhilHealth',
            'Pagibig',
            'W/Tax',
            'AUT',
            'Other Loans',
            'Total Deductions',
            'Net Amount',
            'Bank Name',
            'Bank Account',
        ];
    }

    public function array(): array
    {
        $rows = [];

        foreach (($this->records['payroll_items'] ?? []) as $sectionGroup) {
            $sectionName = $sectionGroup['section_name'] ?? '';

            foreach (($sectionGroup['employees'] ?? []) as $employee) {
                if ($this->product === 'government') {
                    $rows[] = [
                        $sectionName,
                        $employee['employee_no'] ?? '',
                        $employee['name'] ?? '',
                        $employee['position'] ?? '',
                        $employee['basic_salary'] ?? 0,
                        $employee['pera'] ?? 0,
                        $employee['gross_amount_earned'] ?? 0,
                        $employee['rlip'] ?? 0,
                        $employee['hdmf'] ?? 0,
                        $employee['philhealth'] ?? 0,
                        $employee['consoloan'] ?? 0,
                        $employee['emergency_loan'] ?? 0,
                        $employee['plreg'] ?? 0,
                        $employee['mpl'] ?? 0,
                        $employee['cpl'] ?? 0,
                        $employee['mp2'] ?? 0,
                        $employee['mplstlms'] ?? 0,
                        $employee['cir375_cir449'] ?? 0,
                        $employee['w_tax'] ?? 0,
                        $employee['uca'] ?? 0,
                        $employee['aut'] ?? 0,
                        $employee['total_deductions'] ?? 0,
                        $employee['net_amount'] ?? 0,
                        $employee['dbp'] ?? 0,
                        $employee['kawani'] ?? 0,
                        $employee['lbp_payroll_account'] ?? 0,
                        $employee['net_first_half'] ?? 0,
                        $employee['net_second_half'] ?? 0,
                    ];

                    continue;
                }

                $rows[] = [
                    $sectionName,
                    $employee['employee_no'] ?? '',
                    $employee['name'] ?? '',
                    $employee['position'] ?? '',
                    $employee['basic_salary'] ?? 0,
                    $employee['overtime_pay'] ?? 0,
                    $employee['holiday_pay'] ?? 0,
                    $employee['allowances'] ?? 0,
                    $employee['gross_amount_earned'] ?? 0,
                    $employee['sss'] ?? 0,
                    $employee['philhealth'] ?? 0,
                    $employee['pagibig'] ?? 0,
                    $employee['w_tax'] ?? 0,
                    $employee['aut'] ?? 0,
                    $employee['other_loans'] ?? 0,
                    $employee['total_deductions'] ?? 0,
                    $employee['net_amount'] ?? 0,
                    $employee['bank_name'] ?? '',
                    $employee['bank_account'] ?? '',
                ];
            }
        }

        return $rows;
    }
}
