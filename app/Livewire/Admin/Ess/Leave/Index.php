<?php

namespace App\Livewire\Admin\Ess\Leave;

use App\Helpers\SupervisorApproval;
use App\Http\Controllers\Admin\Services\LeaveCardService;
use App\Models\EmployeeAccount;
use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveCard;
use App\Models\LeaveCredits;
use App\Models\LeaveType;
use App\Notifications\Notifications;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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
        $view_records = EmployeeLeave::with('dates', 'employment', 'employee', 'leave_type')
            ->where('id', $id)
            ->first();

        $duration = $view_records->duration ?? 'wholeday'; 
        $daysCovered = count($view_records->dates ?? []);

        if ($duration == 'wholeday') {
            $leaveEquiv = number_format(round($daysCovered * 1, 3), 2);
        } else {
            $leaveEquiv = number_format(round($daysCovered / 2 * 1, 3), 2);
        }

        $view_records->leave_equivalent = $leaveEquiv;
        $this->view_records = $view_records;

    }

    public function disapproved(bool $isNotify = true) {
        
        $this->loadRecords($this->selected_id);

        $employeeNo = $this->view_records->employee_no ?? null;

        if (!$this->canApprove($employeeNo)) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Access Denied!',
                'message' => 'You are not allowed to approve/disapprove leave requests.',
            ]);
        }

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Please be informed that you are about to disapprove this leave application <b>#' . strtoupper(format_id($this->selected_id, 6)) . '</b>. Once this action is processed, it cannot be undone or reversed!';
            $action = 'disapproved';
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        } else {

            $record = EmployeeLeave::where('id', $this->selected_id)
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
                'message' => 'Application has been disapproved'
            ]);

            $user = EmployeeAccount::where('employee_no', $record->employee_no)->first();
            $user?->notify(new Notifications('error', 'You\'re leave application <strong>#' . format_id($record->id, 6) . '</strong> was <strong>DISAPPROVED</strong>.', route('employee.leave'), 'employee'));
        }
    }

    public function approved(bool $isNotify = true) {

        $this->loadRecords($this->selected_id);

        $employeeNo = $this->view_records->employee_no ?? null;

        if (!$this->canApprove($employeeNo)) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Access Denied!',
                'message' => 'You are not allowed to approve/disapprove leave requests.',
            ]);
        }

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Please be informed that you are about to approve this leave application <b>#' . strtoupper(format_id($this->selected_id, 6)) . '</b>. Once this action is processed, it cannot be undone or reversed!';
            $action = 'approved';
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        } else {

            $record = EmployeeLeave::with('dates', 'employment')->where('id', $this->selected_id)
                ->where('status', 'pending')
                ->first();

            if(is_null($record)) {
                return redirect()->route('ess.leave');
            }

            // Update leave credits model
            $leaveCreditsModel = LeaveCredits::class;
            $leaveTypeModel = LeaveType::find($record->leave_id);

            $daysCovered = count($record->dates ?? []);

            // if no credits left

            if($record->leave_id == 1 || $record->leave_id == 2 || $record->leave_id == 3) {

                $leaveType = LeaveType::where('id', $record->leave_id)
                    ->first();
                $leaveTypes = strtolower($leaveType->code);

                $leaveTotalCredits = EmployeeLeaveCard::where('employee_no', $record->employee_no)
                    ->where('year', Carbon::now()->year)
                    ->orderBy('year', 'asc')
                    ->get()
                    ->last();

                if($record->leave_id == 1 || $record->leave_id == 2) {
                    $leaveTotalCredits = $leaveTotalCredits ? $leaveTotalCredits->{$leaveTypes . '_bal'} ?? '' : 0;
                } else {
                    $leaveTotalCredits = $leaveTotalCredits ? $leaveTotalCredits->vl_bal ?? '' : 0;
                }

                $duration = $record->duration ?? 'wholeday'; 


                if ($duration == 'wholeday') {
                    $leaveEquiv = number_format(round($daysCovered * 1, 3), 2);
                } else {
                    $leaveEquiv = number_format(round($daysCovered / 2 * 1.0, 3), 2);
                }

                if(empty($leaveTotalCredits)) {
                    return $this->dispatch('alert', [
                        'showAlert' => true,
                        'status' => 'error',
                        'title' => 'Oops',
                        'message' => 'Unfortunately, this employee\'s leave balance is not yet set.'
                    ]);
                }

                if(!$this->accepts_autwopay) {
                    if($leaveTotalCredits == 0 || $leaveEquiv > $leaveTotalCredits) {
                        $this->accepts_autwopay = true;
                        return $this->dispatch('showConfirmation', [
                            'title' => 'Please be Informed',
                            'message' => '
                                Unfortunately, this employee\'s leave credits are insufficient. He/she is requesting '.$leaveEquiv.' day(s) of leave, but only have '.$leaveTotalCredits.' remaining. This may still proceed, but please note that this will be considered as Absence Without Pay (AUT w/o pay).
                            ',
                            'action' => 'approved'
                        ]);
                    }
                }

            } else {

                $leaveCredits = $leaveCreditsModel::where('leave_type_id', $record->leave_id)
                    ->where('employee_no', $record->employee_no)
                    ->first();

                if(is_null($leaveCredits) || $leaveCredits->credits == 0) {
                    return $this->dispatch('alert', [
                        'showAlert' => true,
                        'status' => 'error',
                        'title' => 'Oops',
                        'message' => 'Unfortunately, this employee have no credits left for <b>' . $leaveTypeModel->name . '</b>.'
                    ]);
                }

                $leaveEquivOther = ($record->duration ?? 'wholeday') === 'wholeday'
                    ? $daysCovered
                    : round($daysCovered / 2 * 1.0, 2);
                if($leaveEquivOther > $leaveCredits->credits) {
                    return $this->dispatch('alert', [
                        'showAlert' => true,
                        'status' => 'error',
                        'title' => 'Oops',
                        'message' => 'Unfortunately, this employee have insufficient leave credits. Applying for '.$leaveEquivOther.' day(s), but only have ' . $leaveCredits->credits . ' remaining leave credits.'
                    ]);
                }
            }

            $leaveCardService = new LeaveCardService;
            $leaveCardService->init($record->employee_no, 'leave_approval', $record);

            unset($record->daysCovered);

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
                'message' => 'Application has been approved'
            ]);

            $user = EmployeeAccount::where('employee_no', $record->employee_no)->first();
            $user?->notify(new Notifications('success', 'Your leave application <strong>#' . format_id($record->id, 6) . '</strong> was <strong>APPROVED</strong>. Click this notification to view more details.', route('employee.leave'), 'employee'));

            return;
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

        // If no specific employee context, fall back to role-based behaviour.
        if (!$employeeNo) {
            return true;
        }

        // Apply supervisor-approval rules when enabled.
        return SupervisorApproval::canApprove($employeeNo);
    }

    public function remove(bool $isNotify = true, ? int $id = null) {

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Please be informed that you are about to delete this leave application <b>#' . strtoupper(format_id($id, 6)) . '</b>. Once this action is processed, it cannot be undone or reversed!';
            $action = 'remove';

            $this->selected_id = $id;
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        }  else {

            $record = EmployeeLeave::find($this->selected_id);

            if($record) {

                $user = EmployeeAccount::where('employee_no', $record->employee_no)->first();
                $user?->notify(new Notifications('error', 'You\'re leave application <strong>#' . format_id($record->id, 6) . '</strong> was <strong>REMOVED</strong>. Click this notification to view more details.', route('employee.leave'), 'employee'));

                $record->isDeleted = true;
                $record->action_by_id = Auth::user()->id;
                $record->save();

                $this->dispatch('alert', [
                    'status' => 'success',
                    'title' => 'Success!',
                    'id' => $this->selected_id,
                    'isRemoveRowDT' => true,
                    'message' => 'Leave Application #' . strtoupper(format_id($record->id, 6)) . ' has deleted successfully.'
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

        $model = EmployeeLeave::with('employment', 'employee', 'leave_type')
            ->where('status', $status)
            ->where('isDeleted', false);

        if ($this->search) {
            $this->resetPage();
            $model = $model->where(function ($query) {
                $query->where('employee_no', 'like', '%' . $this->search . '%')
                    ->orWhereHas('employee', function ($subQuery) {
                        $subQuery->whereRaw("CONCAT(COALESCE(firstname,''), ' ', COALESCE(lastname,'')) LIKE ?", ['%' . $this->search . '%']);
                    });
            });
        }

        $records = $model->latest()->paginate($this->entries);

        return view('livewire.admin.ess.leave.index', [
            'records' => $records
        ]);
    }
}
