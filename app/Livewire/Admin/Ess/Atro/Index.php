<?php

namespace App\Livewire\Admin\Ess\Atro;

use App\Helpers\SupervisorApproval;
use App\Models\EmployeeAccount;
use App\Models\EmployeeAtro;
use App\Models\Sections;
use App\Notifications\Notifications;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use PhpOffice\PhpWord\TemplateProcessor;

class Index extends Component
{
    
    public $status;
    public $view_records;
    public $selected_id;
    public $dates;
    public $dateSelected;
    public $officeSelected;
    public $offices;
    public $disapproval_note;
    public $activeTab = 'pending';
    protected $listeners = ['remove', 'disapproved', 'approved'];

    protected $paginationTheme = 'bootstrap';
    public $entries = 10;
    public $search = '';


    public function mount() {
        $this->loadRecords();
    }

    public function loadRecords(int $id = null) {
        $this->view_records = EmployeeAtro::with('employment', 'employee')
            ->where('id', $id)
            ->first();
        $this->dates = EmployeeAtro::selectRaw('DATE(created_at) as created_at')
            ->distinct()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->pluck('created_at');
    }

    public function showOffices(string $date) {

        $this->dateSelected = Carbon::parse($date)->format('Y-m-d');
        $this->offices = Sections::all();

        if($this->offices) {
            return $this->dispatch('showModal', [
                'modal' => 'showOffices', 
            ]);
        }

    }

    public function download() {
  
        // Fetch leave record with employee details
        $records = EmployeeAtro::with('employee.personal', 'employee.section', 'employee.positions')
            ->whereHas('employee.section', function($query) {
                return $query->where('id', $this->officeSelected);
            })
            ->whereDate('created_at', $this->dateSelected)
            ->get();

        if($records->isEmpty()) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'info',
                'title' => 'Please be informed', 
                'isRemoveRowDT' => false,
                'message' => 'No approved applications found for the selected date and office' 
            ]);
        }

     

        $earliestStartTime = Carbon::parse(collect($records)->min('start_time'))->format('g:i A');
        $latestEndTime = Carbon::parse(collect($records)->max('end_time'))->format('g:i A');
    
        // Template file path
        $template = public_path('templates/forms/HRMS-PD Form 05.docx');

        $currentDate = Carbon::now()->format('m-d-y');
        $outputPath = public_path('outputs/HRMS-PD FORM 05 | ' . $currentDate . '.docx');

        // Ensure the outputs directory exists
        $dir = public_path('outputs');
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

    
        // Check if template file exists
        if (!file_exists($template)) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops', 
                'message' => 'File does not exists!'
            ]);
        }
    
        $names = [];

        foreach($records as $record) {
            $names[] = $record->employee->personal->firstname . ' ' . $record->employee->personal->lastname;
        }

        try {
            
            $templateProcessor = new TemplateProcessor($template);

            // Use the first record for general info
            $firstRecord = $records[0];

            $templateProcessor->setValue('requesting_unit', $firstRecord->employee->section->name);

            // Date Requested from employee_atro.date
            $templateProcessor->setValue('date_requested', Carbon::parse($firstRecord->date)->format('F d, Y'));

            // Justification from employee_atro.justification
            $templateProcessor->setValue('justification', $firstRecord->justification ?? 'N/A');

            // Names of all employees in this batch
            $names = [];
            foreach($records as $record) {
                $names[] = $record->employee->personal->firstname . ' ' . $record->employee->personal->lastname;
            }
            $templateProcessor->setValue('names', implode("\n", $names));
            $templateProcessor->setValue('date', Carbon::parse($this->dateSelected)->format('F d, Y'));
            $templateProcessor->setValue('time', $earliestStartTime . ' - ' . $latestEndTime);

           

            $templateProcessor->saveAs($outputPath);

            return response()->download($outputPath)->deleteFileAfterSend(true);

            
        } catch (\Exception $e) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops', 
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function view(int $id) {
        $this->selected_id = $id;
        $this->loadRecords($id);
        if(!is_null($this->view_records)) {
            return $this->dispatch('showModal', [
                'modal' => 'showModal', 
            ]);
        }
    }

    public function disapproved(bool $isNotify = true) {

        $employeeNo = EmployeeAtro::where('id', $this->selected_id)->value('employee_no');

        if (!$this->canApprove($employeeNo ?? '')) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Access Denied!',
                'message' => 'You are not allowed to approve/disapprove overtime application requests.',
            ]);
        }

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Please be informed that you are about to disapprove this authority to render overtime application <b>#' . strtoupper(format_id($this->selected_id, 6)) . '</b>. Once this action is processed, it cannot be undone or reversed!';
            $action = 'disapproved';
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        } else {

            $record = EmployeeAtro::where('id', $this->selected_id)
                ->where('status', 'pending')
                ->first();

            

            $record->status = 'disapproved';
            $record->action_by_id = Auth::user()->id;
            $record->disapproval_note = $this->disapproval_note; // save the note
            $record->save();

            $user = EmployeeAccount::where('employee_no', $record->employee_no)->first();
            $user?->notify(new Notifications('error', 'You\'re authority to render overtime application <strong>#' . format_id($record->id, 6) . '</strong> was <strong>DISAPPROVED</strong>.', route('employee.atro'), 'employee'));

            $this->dispatch('alert', [
                'id' => $this->selected_id,
                'showAlert' => true,
                'status' => 'success',
                'title' => 'Success', 
                'isRemoveRowDT' => true,
                'message' => 'Application has been disapproved.'
            ]);

        }
    }

    public function approved(bool $isNotify = true) {

        $employeeNo = EmployeeAtro::where('id', $this->selected_id)->value('employee_no');

        if (!$this->canApprove($employeeNo ?? '')) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Access Denied!',
                'message' => 'You are not allowed to approve/disapprove overtime application requests.',
            ]);
        }

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Please be informed that you are about to approve this authority to render overtime application <b>#' . strtoupper(format_id($this->selected_id, 6)) . '</b>. Once this action is processed, it cannot be undone or reversed!';
            $action = 'approved';
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        } else {

            $record = EmployeeAtro::where('id', $this->selected_id)
                ->where('status', 'pending')
                ->first();
            
            $record->status = 'approved';
            $record->action_by_id = Auth::user()->id;
            $record->save();

            $user = EmployeeAccount::where('employee_no', $record->employee_no)->first();
            $user?->notify(new Notifications('success', 'You\'re authority to render overtime application <strong>#' . format_id($record->id, 6) . '</strong> was <strong>APPROVED</strong>. Click this notification to view more details.', route('employee.atro'), 'employee'));

            $this->dispatch('alert', [
                'id' => $this->selected_id,
                'showAlert' => true,
                'status' => 'success',
                'title' => 'Success', 
                'isRemoveRowDT' => true,
                'message' => 'Application has been approved.'
            ]);

        }
    }

    private function canApprove(?string $employeeNo = null): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        $requiredRole = (string) config('ess.approver_role', 'admins');
        $allowSuperadmin = (bool) config('ess.allow_superadmin', true);

        if ($allowSuperadmin && method_exists($user, 'hasRole') && $user->hasRole('superadmin')) {
            return true;
        }

        if (!method_exists($user, 'hasAnyRole')) {
            $baseAllowed = method_exists($user, 'hasRole') ? $user->hasRole($requiredRole) : false;
        } else {
        // Support legacy role names without breaking approval access
        $roles = array_values(array_unique(array_filter([
            $requiredRole,
            'admins',
            'admin',
            'manager',
            'supervisor',
        ])));

        $baseAllowed = $user->hasAnyRole($roles);
        }

        if (!$baseAllowed) {
            return false;
        }

        if (!$employeeNo) {
            return true;
        }

        return SupervisorApproval::canApprove($employeeNo);
    }

    public function remove(bool $isNotify = true, ? int $id = null) {

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Please be informed that you are about to remove this authority to render overtime application <b>#' . strtoupper(format_id($id, 6)) . '</b>. Once this action is processed, it cannot be undone or reversed!';
            $action = 'remove';

            $this->selected_id = $id;
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        }  else {

            $record = EmployeeAtro::find($this->selected_id);
                
            if($record) {

                $user = EmployeeAccount::where('employee_no', $record->employee_no)->first();
                $user?->notify(new Notifications('error', 'You\'re authority to render overtime application <strong>#' . format_id($record->id, 6) . '</strong> was <strong>REMOVED</strong>. Click this notification to view more details.', route('employee.atro'), 'employee'));

                $record->isDeleted = true;
                $record->action_by_id = Auth::user()->id;
                $record->save();

                $this->dispatch('alert', [
                    'status' => 'success',
                    'title' => 'Success!', 
                    'id' => $this->selected_id,
                    'isRemoveRowDT' => true,
                    'message' => 'Overtime Application #' . strtoupper(format_id($record->id, 6)) . ' has been removed successfully.' 
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

        $model = EmployeeAtro::with('employment', 'employee')
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

        return view('livewire.admin.ess.atro.index', [
            'records' => $records
        ]);
    }
}
