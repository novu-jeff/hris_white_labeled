<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeInformation;
use App\Models\OtherDeductions;
use App\Models\OtherEarnings;

class HrisAdvancedPayrollController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read hris');
    }

    public function index(string $employee_no)
    {
        $employee = $this->employeeOrRedirect($employee_no);
        if (!$employee) {
            return redirect()->route('hris.index');
        }

        return view('admin.hris.payroll-advanced.index', compact('employee'));
    }

    public function allowances(string $employee_no)
    {
        $employee = $this->employeeOrRedirect($employee_no);
        if (!$employee) {
            return redirect()->route('hris.index');
        }

        $allowanceItems = OtherEarnings::query()
            ->where(function ($query) {
                $query->where('name', 'like', '%allowance%')
                    ->orWhere('name', 'like', '%communication%')
                    ->orWhere('name', 'like', '%transportation%')
                    ->orWhere('code', 'like', '%PERA%');
            })
            ->orderBy('name')
            ->get();

        return view('admin.hris.payroll-advanced.allowances', compact('employee', 'allowanceItems'));
    }

    public function deMinimis(string $employee_no)
    {
        $employee = $this->employeeOrRedirect($employee_no);
        if (!$employee) {
            return redirect()->route('hris.index');
        }

        $deMinimisItems = OtherEarnings::query()
            ->where(function ($query) {
                $query->where('name', 'like', '%rice%')
                    ->orWhere('name', 'like', '%laundry%')
                    ->orWhere('name', 'like', '%medical%')
                    ->orWhere('name', 'like', '%uniform%')
                    ->orWhere('name', 'like', '%de minimis%');
            })
            ->orderBy('name')
            ->get();

        return view('admin.hris.payroll-advanced.de-minimis', compact('employee', 'deMinimisItems'));
    }

    public function government(string $employee_no)
    {
        $employee = $this->employeeOrRedirect($employee_no);
        if (!$employee) {
            return redirect()->route('hris.index');
        }

        $governmentItems = OtherDeductions::query()
            ->where(function ($query) {
                $query->where('name', 'like', '%tax%')
                    ->orWhere('name', 'like', '%sss%')
                    ->orWhere('name', 'like', '%philhealth%')
                    ->orWhere('name', 'like', '%hdmf%')
                    ->orWhere('name', 'like', '%pag-ibig%')
                    ->orWhere('name', 'like', '%mp2%')
                    ->orWhere('name', 'like', '%loan%');
            })
            ->orderBy('name')
            ->get();

        return view('admin.hris.payroll-advanced.government', compact('employee', 'governmentItems'));
    }

    private function employeeOrRedirect(string $employee_no): ?EmployeeInformation
    {
        return EmployeeInformation::where('employee_no', $employee_no)->first();
    }
}
