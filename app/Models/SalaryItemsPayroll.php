<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryItemsPayroll extends Model
{
    use HasFactory;

    protected $table = 'payroll_salary_items';

    protected $fillable = [];

    public function getFillable()
    {
        $product = config('app.product');

        if ($product === 'government') {
            return [
                'payroll_id',
                'employee_no',
                'name',
                'position',
                'basic_salary',
                'pera',
                'gross_amount_earned',
                'rlip',
                'hdmf',
                'philhealth',
                'consoloan',
                'emergency_loan',
                'plreg',
                'mpl',
                'cpl',
                'mp2',
                'mplstlms',
                'cir375_cir449',
                'w_tax',
                'uca',
                'aut',
                'total_deductions',
                'net_amount',
                'dbp',
                'kawani',
                'lbp_payroll_account',
                'salary',
                'net_first_half',
                'net_second_half',
                'is_first_half_locked',
                'is_second_half_locked',
            ];
        }

        return [
            'payroll_id',
            'employee_no',
            'name',
            'position',
            'basic_salary',
            'overtime_pay',
            'aut',
            'holiday_pay',
            'night_differential',
            'allowances',
            'gross_amount_earned',
            'sss',
            'pagibig',
            'philhealth',
            'w_tax',
            'other_loans',
            'total_deductions',
            'net_amount',
            'bank_account',
            'bank_name',
            'salary',
        ];
    }

    public function information() {
        return $this->belongsTo(EmployeeInformation::class, 'employee_no', 'employee_no');
    }

    public function payroll() {
        return $this->belongsTo(SalaryPayroll::class, 'payroll_id', 'id');
    }

    public function deductions()
    {
        return $this->hasMany(PayrollSallaryDeduction::class,'payroll_item_id')
                        ->with('loan.loanType'); // 👈 important!
    }

}
