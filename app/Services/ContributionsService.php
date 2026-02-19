<?php

namespace App\Services;

class ContributionsService
{
    /**
     * PhilHealth 2025/2026: 5% total (2.5% employee, 2.5% employer).
     * Min ₱500 total (₱250 employee) for salary ≤₱10,000.
     * Max ₱5,000 total (₱2,500 employee) for salary ≥₱100,000.
     * @see https://incometaxcalculator.ph/
     */
    function computePhilHealth($monthlySalary): array
    {
        $baseSalary = max(10000, min($monthlySalary, 100000));
        $total = $baseSalary * 0.05;

        return [
            'total' => round($total, 2),
            'employee_share' => round($total / 2, 2),
            'employer_share' => round($total / 2, 2),
        ];
    }

    /**
     * SSS 2025/2026: 15% total (5% employee, 10% employer) of MSC.
     * MSC ranges ₱5,000 to ₱35,000. Employee: ₱250 to ₱1,750.
     * @see https://incometaxcalculator.ph/
     */
    function computeSSS(float $monthlySalary): array
    {
        $msc = min(max($monthlySalary, 5000), 35000);
        $employeeShare = round($msc * 0.05, 2);
        $employerShare = round($msc * 0.10, 2);

        return [
            'total' => round($employeeShare + $employerShare, 2),
            'employee_share' => $employeeShare,
            'employer_share' => $employerShare,
            'ec' => 0,
            'msc' => $msc,
        ];
    }

    /**
     * Pag-IBIG 2025/2026: 4% total (2% employee, 2% employer).
     * Employee capped at ₱200 for salary >₱10,000.
     * @see https://incometaxcalculator.ph/
     */
    function computePagibig($monthlySalary): array
    {
        $employee = $monthlySalary > 10000 ? 200 : round($monthlySalary * 0.02, 2);
        $employer = $monthlySalary > 10000 ? 200 : round($monthlySalary * 0.02, 2);

        return [
            'total' => round($employee + $employer, 2),
            'employee_share' => round($employee, 2),
            'employer_share' => round($employer, 2),
        ];
    }

    function computeSalary($rate): float
    {
        if ($rate !== null && $rate <= 1000) {
            return round($rate * 22, 2);
        }

        return $rate ?? 0;
    }

    /**
     * TRAIN Law Withholding Tax (monthly). Taxable income = gross - SSS - PhilHealth - Pag-IBIG.
     * @see https://incometaxcalculator.ph/
     */
    public static function computeWithholdingTax(float $taxableIncome): float
    {
        $taxableIncome = round($taxableIncome, 2);

        if ($taxableIncome <= 20833) {
            return 0;
        }
        if ($taxableIncome <= 33332) {
            return round(($taxableIncome - 20833) * 0.15, 2);
        }
        if ($taxableIncome <= 66666) {
            return round(1875 + ($taxableIncome - 33333) * 0.20, 2);
        }
        if ($taxableIncome <= 166666) {
            return round(8541.80 + ($taxableIncome - 66667) * 0.25, 2);
        }
        if ($taxableIncome <= 666666) {
            return round(33541.80 + ($taxableIncome - 166667) * 0.30, 2);
        }

        return round(183541.80 + ($taxableIncome - 666667) * 0.35, 2);
    }
}
