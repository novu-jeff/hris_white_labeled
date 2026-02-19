<?php

namespace App\Livewire\Admin\Ess\Offset;

use App\Helpers\SupervisorApproval;
use App\Models\EmployeeAccount;
use App\Models\EmployeeOffsetApplication;
use App\Models\OffsetCredits;
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

    public function mount($status = 'pending') {
        $this->status = $status;
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

    public function loadRecords(int $id) {
        $this->view_records = EmployeeOffsetApplication::with('employment.section', 'employment.section.branch', 'employment.section.department', 'employment.positions', 'employee.personal')
            ->where('id', $id)
            ->first();
        
        if (!$this->view_records) {
            return;
        }

        $emp = $this->view_records;
        $employeeNo = $emp->employee_no;
        $sectionId = $emp->employment->section_id ?? null;
        $positionName = $emp->employment->positions['name'] ?? '';

        $isDeptSupervisor = false;
        if ($sectionId) {
            $section = DB::table('sections')->where('id', $sectionId)->first();
            if ($section && $section->supervisor_id === $employeeNo) {
                $isDeptSupervisor = true;
            } else {
                $pn = strtolower($positionName);
                if (str_contains($pn, 'supervisor') || str_contains($pn, 'manager') || str_contains($pn, 'head') || str_contains($pn, 'chief') || str_contains($pn, 'director')) {
                    $isDeptSupervisor = true;
                }
            }
        }

        $supervisorName = 'N/A';
        if ($isDeptSupervisor) {
            $ceo = DB::table('employee_information')
                ->leftJoin('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
                ->leftJoin('positions', 'employee_information.position_id', '=', 'positions.id')
                ->where('employee_information.status', 'active')
                ->where('employee_information.isDeleted', false)
                ->where(function ($q) {
                    $q->whereRaw('LOWER(positions.name) LIKE ?', ['%ceo%'])
                        ->orWhereRaw('LOWER(positions.name) LIKE ?', ['%chief executive%'])
                        ->orWhereRaw('LOWER(positions.name) LIKE ?', ['%president%']);
                })
                ->orderBy('positions.id', 'asc')
                ->select('employee_personal.firstname', 'employee_personal.lastname')
                ->first();
            $supervisorName = $ceo ? strtoupper($ceo->firstname . ' ' . $ceo->lastname) : 'N/A';
        } else {
            if ($sectionId) {
                $section = $section ?? DB::table('sections')->where('id', $sectionId)->first();
                if ($section && $section->supervisor_id) {
                    $sup = DB::table('employee_personal')
                        ->where('employee_no', $section->supervisor_id)
                        ->select('firstname', 'lastname')
                        ->first();
                    if ($sup) {
                        $supervisorName = strtoupper($sup->firstname . ' ' . $sup->lastname);
                    }
                }
                if ($supervisorName === 'N/A') {
                    $sup = DB::table('employee_information')
                        ->leftJoin('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
                        ->leftJoin('positions', 'employee_information.position_id', '=', 'positions.id')
                        ->where('employee_information.section_id', $sectionId)
                        ->where('employee_information.employee_no', '!=', $employeeNo)
                        ->where('employee_information.status', 'active')
                        ->where('employee_information.isDeleted', false)
                        ->where(function ($q) {
                            $q->where('positions.name', 'like', '%supervisor%')
                                ->orWhere('positions.name', 'like', '%head%')
                                ->orWhere('positions.name', 'like', '%manager%')
                                ->orWhere('positions.name', 'like', '%chief%');
                        })
                        ->orderBy('positions.id', 'asc')
                        ->select('employee_personal.firstname', 'employee_personal.lastname')
                        ->first();
                    if ($sup) {
                        $supervisorName = strtoupper($sup->firstname . ' ' . $sup->lastname);
                    } else {
                        $fallback = DB::table('employee_information')
                            ->leftJoin('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
                            ->where('employee_information.section_id', $sectionId)
                            ->where('employee_information.employee_no', '!=', $employeeNo)
                            ->where('employee_information.status', 'active')
                            ->where('employee_information.isDeleted', false)
                            ->orderBy('employee_information.date_hired', 'asc')
                            ->select('employee_personal.firstname', 'employee_personal.lastname')
                            ->first();
                        if ($fallback) {
                            $supervisorName = strtoupper($fallback->firstname . ' ' . $fallback->lastname);
                        }
                    }
                }
            }
        }
        $this->view_records->supervisor_name = $supervisorName;
    }

    public function disapproved(bool $isNotify = true) {

        $employeeNo = EmployeeOffsetApplication::where('id', $this->selected_id)->value('employee_no');
        if ($employeeNo && !SupervisorApproval::canApprove($employeeNo)) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Access Denied!',
                'message' => 'You are not allowed to approve/disapprove offset requests for this employee.',
            ]);
        }

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Please be informed that you are about to disapprove this offset application <b>#' . strtoupper(format_id($this->selected_id, 6)) . '</b>. Once this action is processed, it cannot be undone or reversed!';
            $action = 'disapproved';
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        } else {

            $record = EmployeeOffsetApplication::where('id', $this->selected_id)
                ->where('status', 'pending')
                ->first();
            
            $record->status = 'disapproved';
            $record->action_by_id = Auth::user()->id;
            $record->save();

            $user = EmployeeAccount::where('employee_no', $record->employee_no)->first();
            $user?->notify(new Notifications('error', 'Your offset application <strong>#' . format_id($record->id, 6) . '</strong> was <strong>DISAPPROVED</strong>.', route('employee.offset.index'), 'employee'));

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

        $employeeNo = EmployeeOffsetApplication::where('id', $this->selected_id)->value('employee_no');
        if ($employeeNo && !SupervisorApproval::canApprove($employeeNo)) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Access Denied!',
                'message' => 'You are not allowed to approve/disapprove offset requests for this employee.',
            ]);
        }

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Please be informed that you are about to approve this offset application <b>#' . strtoupper(format_id($this->selected_id, 6)) . '</b>. Once this action is processed, it cannot be undone or reversed!';
            $action = 'approved';
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        } else {

            $record = EmployeeOffsetApplication::with('employment')->where('id', $this->selected_id)
                ->where('status', 'pending')
                ->first();

            if (!$record) {
                return $this->dispatch('alert', [
                    'showAlert' => true,
                    'status' => 'error',
                    'title' => 'Oops!',
                    'message' => 'Offset application not found or already processed.',
                ]);
            }

            $offsetCredits = OffsetCredits::firstOrCreate(
                ['employee_no' => $record->employee_no],
                ['credits' => 0, 'as_of' => now()->toDateString()]
            );

            if ((float) $offsetCredits->credits < 1) {
                return $this->dispatch('alert', [
                    'showAlert' => true,
                    'status' => 'error',
                    'title' => 'Insufficient Credits',
                    'message' => 'Cannot approve this application. Employee has no available offset credits.',
                ]);
            }

            DB::beginTransaction();
            try {
                $record->status = 'approved';
                $record->action_by_id = Auth::user()->id;
                $record->save();

                $offsetCredits->credits = max(0, (float) $offsetCredits->credits - 1);
                $offsetCredits->as_of = now()->toDateString();
                $offsetCredits->save();
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                return $this->dispatch('alert', [
                    'showAlert' => true,
                    'status' => 'error',
                    'title' => 'Oops!',
                    'message' => 'Approval failed: ' . $e->getMessage(),
                ]);
            }

            $user = EmployeeAccount::where('employee_no', $record->employee_no)->first();
            $user?->notify(new Notifications('success', 'Your offset application <strong>#' . format_id($record->id, 6) . '</strong> was <strong>APPROVED</strong>. Click this notification to view more details.', route('employee.offset.index'), 'employee'));
        
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
            $message = 'Please be informed that you are about to remove this offset application <b>#' . strtoupper(format_id($id, 6)) . '</b>. Once this action is processed, it cannot be undone or reversed!';
            $action = 'remove';

            $this->selected_id = $id;
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        }  else {

            $record = EmployeeOffsetApplication::find($this->selected_id);
                
            if($record) {
                
                $user = EmployeeAccount::where('employee_no', $record->employee_no)->first();
                $user?->notify(new Notifications('error', 'Your offset application <strong>#' . format_id($record->id, 6) . '</strong> was <strong>REMOVED</strong>. Click this notification to view more details.', route('employee.offset.index'), 'employee'));

                $record->isDeleted = true;
                $record->action_by_id = Auth::user()->id;
                $record->save();

                $this->dispatch('alert', [
                    'status' => 'success',
                    'title' => 'Success!', 
                    'id' => $this->selected_id,
                    'isRemoveRowDT' => true,
                    'message' => 'Offset Application #' . strtoupper(format_id($record->id, 6)) . ' has been removed successfully.' 
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

        $model = EmployeeOffsetApplication::with('employment', 'employee.personal')
            ->where('status', $status)
            ->where('isDeleted', false);

        if ($this->search) {

            $this->resetPage(); 

            $records = $model->where(function ($query) {
                $query->where('employee_no', 'like', '%' . $this->search . '%')
                ->orWhereHas('employee.personal', function ($subQuery) {
                    $subQuery->whereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ['%' . $this->search . '%']);
                });
            });
        }

        $records = $model->latest()->paginate($this->entries);

        return view('livewire.admin.ess.offset.index', [
            'records' => $records
        ]);
    }
}
