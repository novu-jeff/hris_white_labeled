<?php

namespace App\Livewire\Admin\Payroll\Reports;

use App\Models\EmployementTypes;
use App\Models\SalaryPayroll;
use Illuminate\Support\Facades\Bus;
use Livewire\Component;

class Salary extends Component
{
    public $payroll_id, $status, $entries, $type, $employment_type;
    public $records;
    protected $listeners = ['regeneratePayroll', 'removePayroll', 'cancelPayroll'];

    public function regeneratePayroll($payroll_id) {

      
       // dd('here');
        // 1️⃣ Fetch the payroll record
        $payroll = SalaryPayroll::findOrFail($payroll_id);
        $this->payroll_id = $payroll_id;

        // 2️⃣ Delete existing payroll items for this payroll
        \DB::table('payroll_salary_items')
            ->where('payroll_id', $payroll->id)
            ->delete();

        // 2️⃣ Get your PayrollService
        $service = app(\App\Http\Controllers\Admin\Services\PayrollService::class);

        // 3️⃣ Fetch employees for this payroll
        // getEmployees() returns eligible/ineligible arrays
     
        $employeesData = $service->getEmployees($payroll->employment_type, 'salary');

        //dd($employeesData);

        // Only take eligible employees
        $employees = $employeesData['eligible']['items'] ?? [];

        if (empty($employees)) {
            session()->flash('error', 'No eligible employees found for this payroll.');
            return;
        }

        // 4️⃣ Split employees into manageable chunks
        $chunks = array_chunk($employees, 1000);

       // dd($chunks );

        // 5️⃣ Prepare PayrollJob instances
        $jobs = [];
        foreach ($chunks as $chunk) {
            $jobs[] = new \App\Jobs\PayrollJob(
                $chunk,        // array of employees
                $payroll->id,  // payroll ID
                'salary'       // payroll type
            );
        }
   \Log::info("regenerate count jobs payroll", ['count' => count($jobs)]);
        // 6️⃣ Dispatch jobs as a batch
        $batch = \Illuminate\Support\Facades\Bus::batch($jobs)
            ->onQueue('payroll')
            ->name('Regenerate Payroll #' . $payroll->id)
            ->allowFailures()
            ->dispatch();

        // 7️⃣ Save batch ID in payroll record
        $payroll->batch_id = $batch->id;
        $payroll->save();

       

        $this->dispatch('start-job-dispatch', [
            'payroll_id' => $payroll->id,
            'employment_type' => $payroll->employment_type,
            'type' => 'salary'
        ]);

    }

    public function cancelPayroll(bool $isNotify = true)
    {
        
        if($isNotify) {
            $title = 'Are you sure to cancel the current process?';
            $message = 'Please be informed that by proceeding, the entire process finished will be undone and removed';
            $action = 'cancelPayroll';
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action,
            ]);
        } else {
            $this->deletePayroll($this->payroll_id);
        }        
    }

    public function removePayroll(bool $isNotify, int $payroll_id = null)
    {
        if($isNotify) {
            $title = 'Are you sure to remove this payroll?';
            $message = 'Please be informed that by proceeding, all data that is connected to this payroll process will be permanently deleted.';
            $action = 'removePayroll';

            $this->payroll_id = $payroll_id;
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action,
            ]);

            

        } else {
            $this->deletePayroll($this->payroll_id);

            // ✅ Dispatch success AFTER deletion
        $this->dispatch('alert', [
            'status' => 'success',
            'title' => 'Success!',
            'id' => $this->payroll_id,
            'isRemoveRowDT' => true,
            'message' => 'Payroll #' . strtoupper($this->payroll_id) . ' has been successfully removed.'
        ]);
        }
    }

    public function deletePayroll($payroll_id) {
        $payroll = SalaryPayroll::find($payroll_id);

        if ($payroll) {
            $batchId = $payroll->batch_id;

            $payroll->delete();

            if ($batchId) {
                Bus::findBatch($batchId)?->delete();
            }
        }

        return;
    }

    public function render()
    {
    
        $employment_type_id = EmployementTypes::where('name', 'like', '%' . $this->employment_type . '%')->value('id');
        
        $records = SalaryPayroll::where('employment_type', $employment_type_id)
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->paginate($this->entries);

        return view('livewire.admin.payroll.reports.salary', [
            'salary' => $records
        ]);
    }
}
