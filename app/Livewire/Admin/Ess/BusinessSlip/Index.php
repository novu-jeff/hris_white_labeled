<?php

namespace App\Livewire\Admin\Ess\BusinessSlip;

use App\Helpers\SupervisorApproval;
use App\Models\EmployeeAccount;
use App\Models\EmployeeBusinessSlip;
use App\Notifications\Notifications;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $this->view_records = EmployeeBusinessSlip::with('employment.section', 'employment.section.branch', 'employment.section.department', 'employee', 'employment.positions','employee.personal')
            ->where('id', $id)
            ->first();
    }

    public function disapproved(bool $isNotify = true) {

        $employeeNo = EmployeeBusinessSlip::where('id', $this->selected_id)->value('employee_no');
        if ($employeeNo && !SupervisorApproval::canApprove($employeeNo)) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Access Denied!',
                'message' => 'You are not allowed to approve/disapprove official business applications for this employee.',
            ]);
        }

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Please be informed that you are about to disapprove this OB application <b>#' . strtoupper(format_id($this->selected_id, 6)) . '</b>. Once this action is processed, it cannot be undone or reversed!';
            $action = 'disapproved';
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        } else {

            $record = EmployeeBusinessSlip::where('id', $this->selected_id)
                ->where('status', 'pending')
                ->first();
            
            $record->status = 'disapproved';
            $record->action_by_id = Auth::user()->id;
            $record->save();

            $user = EmployeeAccount::where('employee_no', $record->employee_no)->first();
            $user?->notify(new Notifications('error', 'You\'re official business slip application <strong>#' . format_id($record->id, 6) . '</strong> was <strong>DISAPPROVED</strong>.', route('employee.obs.index'), 'employee'));

            $this->dispatch('alert', [
                'id' => $this->selected_id,
                'showAlert' => true,
                'status' => 'success',
                'title' => 'Success', 
                'isRemoveRowDT' => true,
                'message' => 'Application has been disapproved'
            ]);


        }
    }

    public function approved(bool $isNotify = true) {

        $employeeNo = EmployeeBusinessSlip::where('id', $this->selected_id)->value('employee_no');
        if ($employeeNo && !SupervisorApproval::canApprove($employeeNo)) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Access Denied!',
                'message' => 'You are not allowed to approve/disapprove official business applications for this employee.',
            ]);
        }

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Please be informed that you are about to approve this OB application <b>#' . strtoupper(format_id($this->selected_id, 6)) . '</b>. Once this action is processed, it cannot be undone or reversed!';
            $action = 'approved';
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        } else {

            $record = EmployeeBusinessSlip::with('employment')->where('id', $this->selected_id)
                ->where('status', 'pending')
                ->first();

            $record->status = 'approved';
            $record->action_by_id = Auth::user()->id;
            $record->save();

            $user = EmployeeAccount::where('employee_no', $record->employee_no)->first();
            $user?->notify(new Notifications('success', 'You\'re official business slip application <strong>#' . format_id($record->id, 6) . '</strong> was <strong>APPROVED</strong>. Click this notification to view more details.', route('employee.obs.index'), 'employee'));
        
            $this->dispatch('alert', [
                'id' => $this->selected_id,
                'showAlert' => true,
                'status' => 'success',
                'title' => 'Success', 
                'isRemoveRowDT' => true,
                'message' => 'Application has been approved'
            ]);


        }
    }

    public function remove(bool $isNotify = true, int $id = null) {

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Please be informed that you are about to remove this OB application <b>#' . strtoupper(format_id($id, 6)) . '</b>. Once this action is processed, it cannot be undone or reversed!';
            $action = 'remove';

            $this->selected_id = $id;
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        }  else {

            $record = EmployeeBusinessSlip::find($this->selected_id);
                
            if($record) {
                
                $user = EmployeeAccount::where('employee_no', $record->employee_no)->first();
                $user?->notify(new Notifications('error', 'You\'re official business slip application <strong>#' . format_id($record->id, 6) . '</strong> was <strong>REMOVED</strong>. Click this notification to view more details.', route('employee.obs.index'), 'employee'));

                $record->isDeleted = true;
                $record->action_by_id = Auth::user()->id;
                $record->save();

                $this->dispatch('alert', [
                    'status' => 'success',
                    'title' => 'Success!', 
                    'id' => $this->selected_id,
                    'isRemoveRowDT' => true,
                    'message' => 'OBS Application #' . strtoupper(format_id($record->id, 6)) . 'has been removed successfully.' 
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

        $model = EmployeeBusinessSlip::with('employment', 'employee')
            ->where('status', $status)
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

        return view('livewire.admin.ess.business-slip.index', [
            'records' => $records
        ]);
    }
}
