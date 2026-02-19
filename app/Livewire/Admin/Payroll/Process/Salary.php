<?php

namespace App\Livewire\Admin\Payroll\Process;

use App\Exports\SalaryPayrollExport;
use App\Http\Controllers\Admin\Services\Payroll\SalaryService;
use App\Models\EmployeeAccount;
use App\Models\Payroll;
use App\Models\SalaryItemsPayroll;
use App\Models\SalaryPayroll;
use App\Notifications\Notifications;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class Salary extends Component
{
    public $product;
    public $type;
    public $employment_type;
    public $payroll_id;
    public $hdmf = [];
    public $uca = [];
    public $dbp = [];
    public $kawani = [];
    public array $originalItems = [];
    public array $updatedItems = [];
    public bool $isApproved = false;
    public bool $hasChanges = false;
    public $records;
    protected $listeners = ['save', 'approve'];

    public function mount() {
        $this->loadRecords();
    }

    public function loadRecords()
    {
        $this->product = config('app.product');

        $service = app(SalaryService::class);

        $records = $service->getPayroll($this->payroll_id);

        $employmentType = $records['payroll']['employment_type'] ?? null;
        $this->employment_type = strtolower($employmentType['name'] ?? ''); 

        foreach ($records['payroll_items'] as $sectionIndex => $sectionGroup) {
            $employees = $sectionGroup['employees'] ?? [];

            foreach ($employees as $employeeIndex => $record) {
                $this->hdmf[$sectionIndex][$employeeIndex]   = $record['hdmf'] ?? 0;
                $this->uca[$sectionIndex][$employeeIndex]    = $record['uca'] ?? 0;
                $this->dbp[$sectionIndex][$employeeIndex]    = $record['dbp'] ?? 0;
                $this->kawani[$sectionIndex][$employeeIndex] = $record['kawani'] ?? 0;
            }
        }

        $this->originalItems = json_decode(json_encode($records['payroll_items']), true);

        if(is_null($records['payroll']['batch_id'])) {
            return redirect()->route('payroll.index');
        }

        $this->isApproved = $records['payroll']['status'] == 'approved' ? true : false;
        $this->records = $records;
        $this->batchId = $records['batch_id'];

    }

    public function recompute($sectionIndex, $employeeIndex)
{
    // =========================================================
    // 1. REFERENCES
    // =========================================================
    $payroll      = &$this->records['payroll'];
    $payrollItem  = &$this->records['payroll_items'][$sectionIndex]['employees'][$employeeIndex];
    $dbItem       = SalaryItemsPayroll::find($payrollItem['id']);

    // =========================================================
    // 2. SYNC INPUT DEDUCTIONS
    // =========================================================
    foreach (['hdmf', 'uca', 'dbp', 'kawani'] as $field) {
        $payrollItem[$field] = round(
            floatval($this->{$field}[$sectionIndex][$employeeIndex] ?? 0),
            2
        );
    }

    // =========================================================
    // 3. DEDUCTIONS GROUPS
    // =========================================================
    $netAffectingDeductions = [
        'rlip','hdmf','philhealth','consoloan','emergency_loan',
        'plreg','mpl','cpl','mp2','mplstlms','cir375_cir449',
        'uca','w_tax','aut'
    ];

    $bankAllocations = ['dbp', 'kawani'];

    // Total deductions that affect net
    $netDeductions = round(array_sum(array_map(
        fn ($f) => floatval($payrollItem[$f] ?? 0),
        $netAffectingDeductions
    )), 2);

    // Total bank allocations (DBP/Kawani)
    $bankTotal = round(array_sum(array_map(
        fn ($f) => floatval($payrollItem[$f] ?? 0),
        $bankAllocations
    )), 2);

    // =========================================================
    // 4. COMPUTE GROSS NET
    // =========================================================
    $gross       = round(floatval($payrollItem['gross_amount_earned'] ?? 0), 2);
    $netComputed = round($gross - $netDeductions, 2);

    // =========================================================
    // 5. GOVERNMENT PAYROLL LOGIC
    // =========================================================
if ($this->product === 'government' && $dbItem) {

    $original = $this->originalItems[$sectionIndex]['employees'][$employeeIndex];

    $hdmfChanged = ($payrollItem['hdmf'] ?? 0) != ($original['hdmf'] ?? 0);
    $ucaChanged  = ($payrollItem['uca'] ?? 0)  != ($original['uca'] ?? 0);

    // ------------------------------------
    // NET AMOUNT (HDMF + UCA only)
    // ------------------------------------
   if ($hdmfChanged || $ucaChanged) {
    
        $netAmount = round(
            ($payrollItem['gross_amount_earned'] ?? 0)
            - $netDeductions,
            2
        );

         \Log::debug('UCA CHANGED', [
            'firsthalf' => $payrollItem['gross_amount_earned'],
            'netamount' => $netDeductions,
            'net' => $netAmount
        ]);

        $payrollItem['net_amount'] = $netAmount;
        $dbItem->net_amount        = $netAmount;
    } else {
        $netAmount = round($payrollItem['net_amount'], 2);
    }

    // ------------------------------------
    // FIRST HALF
    // ------------------------------------
    if ($hdmfChanged) {
        // Only HDMF re-splits halves
        
        $firstHalf = floor(($netAmount / 2) * 100) / 100;
        $n = $netAmount; 

        \Log::debug('here in hdmf changed', [
            'firsthalf' => $firstHalf,
            'netamount' => $netAmount,
            'n' => $n
        ]);
      // dd($firstHalf);
    } else {
        // Fixed first half
      
        \Log::debug('here in no hdmf changed');
        $firstHalf = round($original['net_first_half'], 2);
        
    }

    // ------------------------------------
    // LBP PAYROLL ACCOUNT
    // ------------------------------------
    $bankTotal = round(
        floatval($payrollItem['dbp'] ?? 0) +
        floatval($payrollItem['kawani'] ?? 0),
        2
    );

    $lbpPayroll = round($netAmount - $bankTotal, 2);

    // ------------------------------------
    // SECOND HALF (ALWAYS RECALCULATED)
    // ------------------------------------
    $secondHalf = round($lbpPayroll - $firstHalf, 2);

    \Log::debug('secondhalf computation', [
        'secondhalf' => $secondHalf,
        'firsthalf' => $firstHalf,
        'lbpayroll' => $lbpPayroll,
        'netamount' => $netAmount,
    ]);



    // ------------------------------------
    // APPLY VALUES
    // ------------------------------------
    $payrollItem['net_first_half']      = $firstHalf;
    $payrollItem['net_second_half']     = $secondHalf;
    $payrollItem['salary']              = $secondHalf;
    $payrollItem['lbp_payroll_account'] = $lbpPayroll;

    $dbItem->net_first_half  = $firstHalf;
    $dbItem->net_second_half = $secondHalf;
}



    if ($original) {
        $changed = $this->isChanged($payrollItem, $original);
        $id      = $payrollItem['id'];

        if ($changed && !in_array($id, $this->updatedItems)) {
            $this->updatedItems[] = $id;
        }

        if (!$changed && ($key = array_search($id, $this->updatedItems)) !== false) {
            unset($this->updatedItems[$key]);
        }

        $this->updatedItems = array_values($this->updatedItems);
        $this->hasChanges   = !empty($this->updatedItems);
    }

    // =========================================================
    // 6. TOTAL DEDUCTIONS (ALWAYS RECOMPUTE)
    // =========================================================

    // Net-affecting deductions
    $netAffectingDeductions = [
        'rlip','hdmf','philhealth','consoloan','emergency_loan',
        'plreg','mpl','cpl','mp2','mplstlms','cir375_cir449',
        'uca','w_tax','aut'
    ];

    $netDeductionTotal = round(array_sum(array_map(
        fn ($f) => floatval($payrollItem[$f] ?? 0),
        $netAffectingDeductions
    )), 2);

    // Bank allocations (DBP + Kawani)
    $bankTotal = round(
        floatval($payrollItem['dbp'] ?? 0) +
        floatval($payrollItem['kawani'] ?? 0),
        2
    );

    // Final total deductions
    $payrollItem['total_deductions'] = round(
        $netDeductionTotal,
        2
    );

    $lbpPayroll = round($netAmount - $bankTotal, 2);

// ------------------------------------
// SECOND HALF (ALWAYS CHANGES)
// ------------------------------------
$secondHalf = round($lbpPayroll - $firstHalf, 2);

    \Log::debug('Payroll recomputed', [
        'section' => $sectionIndex,
        'employee' => $employeeIndex,
        'net' => $payrollItem['net_amount'],
        'lbp' => $payrollItem['lbp_payroll_account'],
        'total_deductions' => $payrollItem['total_deductions']
    ]);
}





    protected function isChanged(array $current, array $original): bool
    {
        foreach ($current as $key => $value) {
            if (array_key_exists($key, $original)) {
                if (number_format((float)$value, 2, '.', '') !== number_format((float)$original[$key], 2, '.', '')) {
                    return true;
                }
            }
        }
        return false;
    }

    public function save(bool $isNotify = true)
    {
        if ($isNotify) {
            $this->dispatch('showConfirmation', [
                'title' => 'Are you sure to continue?',
                'message' => 'We\'ve noticed that there are changes made. Are you sure to save this action first?',
                'action' => 'save'
            ]);

            return;
        }

        DB::beginTransaction();

        try {

            foreach ($this->records['payroll_items'] as $section) {
                foreach ($section['employees'] as $employeeData) {
                    if (empty($employeeData['id'])) {
                        continue;
                    }

                    $payrollItem = SalaryItemsPayroll::find($employeeData['id']);
                    if (!$payrollItem) {
                        continue;
                    }

                    $updateData = [
                        'rlip'                => $employeeData['rlip'] ?? 0,
                        'hdmf'                => $employeeData['hdmf'] ?? 0,
                        'philhealth'          => $employeeData['philhealth'] ?? 0,
                        'consoloan'           => $employeeData['consoloan'] ?? 0,
                        'emergency_loan'      => $employeeData['emergency_loan'] ?? 0,
                        'plreg'               => $employeeData['plreg'] ?? 0,
                        'mpl'                 => $employeeData['mpl'] ?? 0,
                        'cpl'                 => $employeeData['cpl'] ?? 0,
                        'mp2'                 => $employeeData['mp2'] ?? 0,
                        'mplstlms'            => $employeeData['mplstlms'] ?? 0,
                        'cir375_cir449'       => $employeeData['cir375_cir449'] ?? 0,
                        'uca'                 => $employeeData['uca'] ?? 0,
                        'dbp'                 => $employeeData['dbp'] ?? 0,
                        'kawani'              => $employeeData['kawani'] ?? 0,
                        'w_tax'               => $employeeData['w_tax'] ?? 0,
                        'aut'                 => $employeeData['aut'] ?? 0,
                        'total_deductions'    => $employeeData['total_deductions'] ?? 0,
                        'net_amount'          => $employeeData['net_amount'] ?? 0,
                        'lbp_payroll_account' => $employeeData['lbp_payroll_account'] ?? 0,
                        'salary'              => $employeeData['salary'] ?? 0,
                        'net_first_half'      => $employeeData['net_first_half'] ?? 0,
                        'net_second_half'     => $employeeData['net_second_half'] ?? 0,
                    ];

                    $payrollItem->update($updateData);
                }
            }

            DB::commit();

            $this->hasChanges = false;

            $this->reset('updatedItems');

            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'success',
                'title' => 'Yey!',
                'message' => 'Changes Saved',
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            \Log::error('Payroll save failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            $this->dispatch('closeModal', ['modal' => 'loading']);

            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops!',
                'message' => 'Error occurred: ' . $e->getMessage(),
                'redirect' => '_reload'
            ]);
        }
    }

    public function approve(bool $isNotify = true) {

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Please be informed that once proceed payslip will be released to the employees. This action cannot be reverted';
            $action = 'approve';
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        } else {

            $payroll = SalaryPayroll::find($this->payroll_id);
            $payroll->status = 'approved';
            $payroll->save();

            $employeeNos = SalaryItemsPayroll::where('payroll_id', $payroll->id)
                ->distinct()
                ->pluck('employee_no')
                ->filter()
                ->values();

            $payrollDate = Carbon::parse($payroll->payroll_date)->format('F d, Y');
            $message = "Your payslip for <strong>{$payrollDate}</strong> is now <strong>AVAILABLE</strong>. Click this notification to view your payslip.";
            $redirect = route('employee.payslip');

            EmployeeAccount::whereIn('employee_no', $employeeNos)
                ->get()
                ->each(function ($employee) use ($message, $redirect) {
                    try {
                        $employee->notify(new Notifications('success', $message, $redirect, 'employee'));
                    } catch (\Throwable $e) {
                        \Log::warning('Payroll approval notification failed', [
                            'employee_no' => $employee->employee_no ?? null,
                            'error' => $e->getMessage(),
                        ]);
                    }
                });

            $this->dispatch('alert', [
                'status' => 'success',
                'title' => 'Success!',
                'showAlert' => true,
                'message' => 'Payroll was approved, Payslip will be visible to employees',
                'redirect' => route('payroll.process', ['type' => $this->type, 'payroll_id' => $this->payroll_id])
            ]);
        }

    }

    public function exportToExcel()
    {
        if (empty($this->records['payroll_items'] ?? [])) {
            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'No data',
                'message' => 'No payroll items available for export.',
            ]);

            return;
        }

        $payrollDate = Carbon::parse($this->records['payroll']['payroll_date'] ?? now())->format('Ymd');
        $filename = sprintf(
            'salary-payroll-%s-%s.xlsx',
            $this->payroll_id,
            $payrollDate
        );

        return Excel::download(
            new SalaryPayrollExport($this->records, $this->product),
            $filename
        );
    }

    public function render()
    {
        return view('livewire.admin.payroll.process.salary');
    }
}
