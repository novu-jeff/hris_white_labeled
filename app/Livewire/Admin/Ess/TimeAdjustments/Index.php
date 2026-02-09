<?php

namespace App\Livewire\Admin\Ess\TimeAdjustments;

use App\Helpers\SupervisorApproval;
use App\Models\EmployeeAccount;
use App\Models\EmployeeTimeAdjustments;
use App\Notifications\Notifications;
use App\Services\TimeAdjustmentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $status = 'pending';
    public $view_records;
    public $selected_id;

    protected $listeners = ['remove', 'disapproved', 'approved'];

    protected $paginationTheme = 'bootstrap';
    public $entries = 10;
    public $search = '';

    public function mount(string $status = 'pending'): void
    {
        $this->status = $status ?: 'pending';
    }

    public function view(int $id): void
    {
        $this->selected_id = $id;
        $this->loadRecords($id);
        if (!is_null($this->view_records)) {
            $this->dispatch('showModal', [
                'modal' => 'showModal',
            ]);
        }
    }

    public function loadRecords(int $id): void
    {
        $this->view_records = EmployeeTimeAdjustments::with(['attachments', 'personal', 'employee'])
            ->where('id', $id)
            ->first();
    }

    public function approved(bool $isNotify = true): void
    {
        $this->loadRecords($this->selected_id);
        $employeeNo = $this->view_records->employee_no ?? null;

        if (!$this->canApprove($employeeNo)) {
            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Access Denied!',
                'message' => 'You are not allowed to approve/disapprove time adjustments.',
            ]);
            return;
        }

        if ($isNotify) {
            $this->dispatch('showConfirmation', [
                'title' => 'Are you sure to continue?',
                'message' => 'Please be informed that you are about to approve this request timelog <b>#' . strtoupper(format_id($this->selected_id, 6)) . '</b>. Once this action is processed, it cannot be undone or reversed!',
                'action' => 'approved',
            ]);
            return;
        }

        $record = EmployeeTimeAdjustments::where('id', $this->selected_id)
            ->where('status', 'pending')
            ->first();

        if (!$record) {
            Log::channel('single')->info('Time adjustment approval: record not found or not pending', [
                'selected_id' => $this->selected_id,
            ]);
            return;
        }

        $record->status = 'approved';
        $record->action_by_id = Auth::id();
        $record->save();

        Log::channel('single')->info('Time adjustment approved by admin', [
            'adjustment_id' => $record->id,
            'employee_no' => $record->employee_no,
            'date' => $record->date,
            'action_by_id' => Auth::id(),
        ]);

        $dtrApplied = false;
        $applyMessage = '';
        try {
            $service = new TimeAdjustmentService();
            $result = $service->applyApprovedAdjustmentToDtr($record);
            $dtrApplied = $result['success'];
            $applyMessage = $result['message'];
            if (!$result['success']) {
                Log::channel('single')->warning('Time adjustment approved but DTR apply failed', [
                    'adjustment_id' => $record->id,
                    'employee_no' => $record->employee_no,
                    'message' => $result['message'],
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('single')->error('Time adjustment approval: DTR apply exception', [
                'adjustment_id' => $record->id,
                'employee_no' => $record->employee_no,
                'error' => $e->getMessage(),
            ]);
            $applyMessage = $e->getMessage();
        }

        $message = 'Request timelog has been approved.';
        if ($dtrApplied) {
            $message .= ' The adjustment has been applied to the employee DTR.';
        } elseif ($applyMessage) {
            $message .= ' (DTR was not updated: ' . $applyMessage . ')';
        }

        $this->dispatch('alert', [
            'id' => $this->selected_id,
            'showAlert' => true,
            'status' => 'success',
            'title' => 'Success',
            'isRemoveRowDT' => true,
            'message' => $message,
        ]);

        $user = EmployeeAccount::where('employee_no', $record->employee_no)->first();
        $user?->notify(new Notifications(
            'success',
            'Your request timelog <strong>#' . format_id($record->id, 6) . '</strong> was <strong>APPROVED</strong>.'
                . ($dtrApplied ? ' It has been applied to your DTR.' : ''),
            route('employee.time-adjustments'),
            'employee'
        ));
    }

    public function disapproved(bool $isNotify = true): void
    {
        $this->loadRecords($this->selected_id);
        $employeeNo = $this->view_records->employee_no ?? null;

        if (!$this->canApprove($employeeNo)) {
            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Access Denied!',
                'message' => 'You are not allowed to approve/disapprove time adjustments.',
            ]);
            return;
        }

        if ($isNotify) {
            $this->dispatch('showConfirmation', [
                'title' => 'Are you sure to continue?',
                'message' => 'Please be informed that you are about to disapprove this request timelog <b>#' . strtoupper(format_id($this->selected_id, 6)) . '</b>. Once this action is processed, it cannot be undone or reversed!',
                'action' => 'disapproved',
            ]);
            return;
        }

        $record = EmployeeTimeAdjustments::where('id', $this->selected_id)
            ->where('status', 'pending')
            ->first();

        if (!$record) {
            Log::channel('single')->info('Time adjustment disapproval: record not found or not pending', [
                'selected_id' => $this->selected_id,
            ]);
            return;
        }

        $record->status = 'disapproved';
        $record->action_by_id = Auth::id();
        $record->save();

        Log::channel('single')->info('Time adjustment disapproved by admin', [
            'adjustment_id' => $record->id,
            'employee_no' => $record->employee_no,
            'date' => $record->date,
            'action_by_id' => Auth::id(),
        ]);

        $this->dispatch('alert', [
            'id' => $this->selected_id,
            'showAlert' => true,
            'status' => 'success',
            'title' => 'Success',
            'isRemoveRowDT' => true,
            'message' => 'Request timelog has been disapproved.',
        ]);

        $user = EmployeeAccount::where('employee_no', $record->employee_no)->first();
        $user?->notify(new Notifications(
            'error',
            'Your request timelog <strong>#' . format_id($record->id, 6) . '</strong> was <strong>DISAPPROVED</strong>.',
            route('employee.time-adjustments'),
            'employee'
        ));
    }

    public function remove(bool $isNotify = true, ?int $id = null): void
    {
        if ($isNotify) {
            $this->selected_id = $id;
            $this->dispatch('showConfirmation', [
                'title' => 'Are you sure to continue?',
                'message' => 'Please be informed that you are about to delete this request timelog <b>#' . strtoupper(format_id($id, 6)) . '</b>. Once this action is processed, it cannot be undone or reversed!',
                'action' => 'remove',
            ]);
            return;
        }

        $record = EmployeeTimeAdjustments::find($this->selected_id);
        if (!$record) {
            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops!',
                'isRemoveRowDT' => false,
                'message' => 'Error: ID does not exists',
            ]);
            return;
        }

        $record->isDeleted = true;
        $record->action_by_id = Auth::id();
        $record->save();

        $this->dispatch('alert', [
            'status' => 'success',
            'title' => 'Success!',
            'id' => $this->selected_id,
            'isRemoveRowDT' => true,
            'message' => 'Request timelog #' . strtoupper(format_id($record->id, 6)) . ' has deleted successfully.',
        ]);
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

    public function render()
    {
        $status = $this->status === 'granted' ? 'approved' : $this->status;

        $model = EmployeeTimeAdjustments::with(['employee.personal'])
            ->where('status', $status)
            ->where('isDeleted', false);

        if ($this->search) {
            $this->resetPage();
            $model->where(function ($query) {
                $query->where('employee_no', 'like', '%' . $this->search . '%')
                    ->orWhereHas('personal', function ($subQuery) {
                        $subQuery->whereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ['%' . $this->search . '%']);
                    });
            });
        }

        $records = $model->latest()->paginate($this->entries);

        return view('livewire.admin.ess.time-adjustments.index', [
            'records' => $records,
        ]);
    }
}
