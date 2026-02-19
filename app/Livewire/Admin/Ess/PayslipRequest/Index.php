<?php

namespace App\Livewire\Admin\Ess\PayslipRequest;

use App\Helpers\SupervisorApproval;
use App\Models\EmployeeAccount;
use App\Models\EmployeePayslipRequest;
use App\Notifications\Notifications;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{

    use WithPagination;

    public $status;
    public $view_records;
    public $selected_id;
    public $activeTab = 'pending';
    protected $listeners = ['remove', 'disapproved', 'approved'];
    public $accepts_autwopay;

    protected $paginationTheme = 'bootstrap';
    public $entries = 10;
    public $search = '';

    public function view(int $id) {
        $this->selected_id = $id;
        $this->loadRecords($id);
        if(!is_null($this->view_records)) {
            return $this->dispatch('showModal', [
                'modal' => 'showModal',
            ]);
        }
    }

    public function loadRecords(int $id) {
        $view_records = EmployeePayslipRequest::with('payroll', 'employee')->where('id', $id)
            ->first();
        $this->view_records = $view_records;
    }

    public function disapproved(bool $isNotify = true) {
        
        $this->loadRecords($this->selected_id);

        $employeeNo = $this->view_records->employee_no ?? null;
        if ($employeeNo && !SupervisorApproval::canApprove($employeeNo)) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Access Denied!',
                'message' => 'You are not allowed to approve/disapprove payslip requests for this employee.',
            ]);
        }

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Please be informed that you are about to disapprove this payslip download request <b>#' . strtoupper(format_id($this->selected_id, 6)) . '</b>. Once this action is processed, it cannot be undone or reversed!';
            $action = 'disapproved';
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        } else {

            $record = EmployeePayslipRequest::where('id', $this->selected_id)
                ->where('status', 'pending')
                ->first();

            $record->status = 'disapproved';
            $record->action_by_id = Auth::user()->id;
            $record->save();

            $this->dispatch('alert', [
                'id' => $this->selected_id,
                'showAlert' => true,
                'status' => 'success',
                'title' => 'Success',
                'isRemoveRowDT' => true,
                'message' => 'Request has been disapproved'
            ]);

            $user = EmployeeAccount::where('employee_no', $record->employee_no)->first();
            $user?->notify(new Notifications('error', 'Your payslip download request <strong>#' . format_id($record->id, 6) . '</strong> was <strong>DISAPPROVED</strong>.', route('employee.payslip'), 'employee'));
        }
    }

    public function approved(bool $isNotify = true) {

        $this->loadRecords($this->selected_id);

        $employeeNo = $this->view_records->employee_no ?? null;
        if ($employeeNo && !SupervisorApproval::canApprove($employeeNo)) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Access Denied!',
                'message' => 'You are not allowed to approve/disapprove payslip requests for this employee.',
            ]);
        }

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Please be informed that you are about to approve this payslip download request <b>#' . strtoupper(format_id($this->selected_id, 6)) . '</b>. Once this action is processed, it cannot be undone or reversed!';
            $action = 'approved';
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        } else {

            $record = EmployeePayslipRequest::where('id', $this->selected_id)
                ->where('status', 'pending')
                ->first();

            if(is_null($record)) {
                return redirect()->route('ess.payslip-request');
            }

            $record->update([
                'action_by_id' => Auth::user()->id,
                'status' => 'approved'
            ]);

            $this->dispatch('alert', [
                'id' => $this->selected_id,
                'showAlert' => true,
                'status' => 'success',
                'title' => 'Success',
                'isRemoveRowDT' => true,
                'message' => 'Request has been approved'
            ]);

            $user = EmployeeAccount::where('employee_no', $record->employee_no)->first();
            $user?->notify(new Notifications('success', 'Your request <strong>#' . format_id($record->id, 6) . '</strong> for downloading payslip was <strong>APPROVED</strong>. Click this notification to view more details.', route('employee.payslip'), 'employee'));

            return;
        }

    }

    public function remove(bool $isNotify = true, ? int $id = null) {

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Please be informed that you are about to delete this request <b>#' . strtoupper(format_id($id, 6)) . '</b>. Once this action is processed, it cannot be undone or reversed!';
            $action = 'remove';

            $this->selected_id = $id;
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        }  else {

            $record = EmployeePayslipRequest::find($this->selected_id);

            if($record) {

                $user = EmployeeAccount::where('employee_no', $record->employee_no)->first();
                $user?->notify(new Notifications('error', 'Your payslip download request <strong>#' . format_id($record->id, 6) . '</strong> was <strong>REMOVED</strong>. Click this notification to view more details.', route('employee.payslip'), 'employee'));

                $record->isDeleted = true;
                $record->action_by_id = Auth::user()->id;
                $record->save();

                $this->dispatch('alert', [
                    'status' => 'success',
                    'title' => 'Success!',
                    'id' => $this->selected_id,
                    'isRemoveRowDT' => true,
                    'message' => 'Payslip request #' . strtoupper(format_id($record->id, 6)) . ' has deleted successfully.'
                ]);

            } else {
                return $this->dispatch('alert', [
                    'showAlert' => true,
                    'status' => 'error',
                    'title' => 'Oops!',
                    'isRemoveRowDT' => false,
                    'message' => 'Error: ID does not exists'
                ]);
            }
        }
    }

    public function render()
    {

        if($this->status == 'granted') {
            $status = 'approved';
        } else {
            $status = $this->status;
        }

        $model = EmployeePayslipRequest::with('employee')->where('status', $status)
            ->where('isDeleted', false);

        if ($this->search) {

            $this->resetPage();

            $records = $model->where(function ($query) {
                $query->where('employee_no', 'like', '%' . $this->search . '%')
                ->orWhereHas('employee', function ($subQuery) {
                    $subQuery->whereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ['%' . $this->search . '%']);
                });
            });
        }

        $records = $model->latest()->paginate($this->entries);

        return view('livewire.admin.ess.payslip-request.index', [
            'records' => $records
        ]);
    }
}
