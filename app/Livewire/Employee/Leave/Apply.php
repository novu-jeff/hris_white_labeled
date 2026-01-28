<?php

namespace App\Livewire\Employee\Leave;

use App\Models\EmployeeAccount;
use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveCard;
use App\Models\EmployeeLeaveDates;
use App\Models\Holiday;
use App\Models\LeaveCredits;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\Notifications;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class Apply extends Component
{

    public $type;
    public $duration;
    public $from;
    public $to;
    public $reason;
    public $record_id;
    public $employee_no;
    public $employee_id;
    public $leaveTypes;
    public $isMoreThanOne = null;
    public $selectedDates;
    public $isEdit = false;

    public $notification;
    public $accepts_autwopay;

    public $location;
    public $location_specific;
    public $confinement;
    public $illness;
    public $study;
    public $study_other_purpose;
    public $commutation;

    public $remaining_credits;
    public bool $isDurationDisabled = false;
    public $scheduledDates;
    public $currentYear;

    protected $listeners = ['setSelectedDates', 'save', 'setTime'];

    public function mount() {
        $this->loadRecords();
    }

    public function loadRecords() {

        $this->leaveTypes = LeaveType::all();

        $user = Auth::guard('employee')->user() ?? Auth::user();
        if (!$user) {
            return redirect()->route('employee.login');
        }

        $employee_no = $user->employee_no;
        $employee_id = $user->id;

        $this->currentYear = Carbon::now()->format('Y');
        $this->employee_no = $employee_no;
        $this->employee_id = $employee_id;

        if(!is_null($this->record_id)) {

            $records = EmployeeLeave::with('dates')->where('id', $this->record_id)
                ->where('employee_no', $employee_no)
                ->first();

            if(!$records) {
                return redirect()
                    ->route('employee.leave');
            }

            $this->type = $records->leave_id;
            $dates = $records->dates;

            $this->selectedDates = $dates;
            $this->isEdit = true;
            $daysCovered = count($dates) ?? 0;

            $this->selectDuration();
            $this->handleLeaveCredits($daysCovered);

            $this->from = $records->from;
            $this->to = $records->to;
            $this->reason = $records->reason;
            $this->location = $records->location;
            $this->location_specific = $records->location_specific;
            $this->confinement = $records->confinement;
            $this->illness = $records->illness;
            $this->study = $records->study;
            $this->study_other_purpose = $records->study_other_purpose;
            $this->commutation = $records->commutation;
        }

        $this->scheduledDates = $this->gatherDates($employee_no);

    }


    private function gatherDates(string $employee_no)
    {

        $leaveDates = collect(
            EmployeeLeaveDates::with('employeeLeave.leave_type')
                ->whereHas('employeeLeave', function ($query) use ($employee_no) {
                    $query->where('employee_no', $employee_no)
                        ->where('status', '!=', 'disapproved'); 
                })
                ->get()
                ->map(function ($item) {
                    return [
                        'date'   => $item->date,
                        'name'   => optional($item->employeeLeave->leave_type)->name ?? 'Leave',
                        'type'   => 'leave',
                        'status' => $item->employeeLeave->status ?? null 
                    ];
                })
        );

        $holidays = collect(
            Holiday::get()->map(function ($holiday) {
                return [
                    'date' => $holiday->date,
                    'name' => ucwords($holiday->name),
                    'type' => 'holiday',
                ];
            })
        );

        $myCalendar = $leaveDates->merge($holidays)->sortBy('date')->values();

        return $myCalendar->toArray();

    }

    public function setSelectedDates($dates) {
        $this->selectedDates = $dates;
    }

    public function handleLeaveCredits(int $duration = null) {

        $leaveType = LeaveType::where('id', $this->type)
            ->first();
        $leaveTypes = strtolower($leaveType->code ?? null);


        if($this->type == 1 || $this->type == 2) {
            $records = EmployeeLeaveCard::where('employee_no', $this->employee_no)
                ->where('year', Carbon::now()->year)
                ->orderBy('year', 'asc')
                ->get()
                ->last();
            $leaveTotalCredits = $records->{$leaveTypes . '_bal'} ?? 0;

        } else if($this->type == 3) {

            $this->isDurationDisabled = true;
            $this->isMoreThanOne = true;

            $records = EmployeeLeaveCard::where('employee_no', $this->employee_no)
                ->where('year', Carbon::now()->year)
                ->orderBy('year', 'asc')
                ->get()
                ->last();

            $leaveTotalCredits = $records->vl_bal ?? 0;

            if($leaveTotalCredits > 10) {
                $leaveTotalCredits = 5;
            }

        } else {
            $records = LeaveCredits::where('employee_no', $this->employee_no)
                ->where('leave_type_id', $this->type)
                ->first();

            $leaveTotalCredits = $records->credits ?? 0;
        }

        $this->remaining_credits = $leaveTotalCredits;
    }

    public function rules() {

        $rules = [
            'type' => 'required|exists:leave_types,id',
            'commutation' => 'required|in:yes,no',
            'selectedDates' => 'required|array|min:1'
        ];


        // Conditional validation based on type
        switch ($this->type) {
            case 1: // Location required for type 1
                $rules['location'] = 'required|in:ph,abroad';
                $rules['duration'] = 'required|in:wholeday,halfday_morning,halfday_afternoon';
                break;

            case 2: // Confinement and illness required for type 2
                $rules['confinement'] = 'required';
                $rules['duration'] = 'required|in:wholeday,halfday_morning,halfday_afternoon';
                break;

            case 3: // Mandatory/forced leave - minimum 5 selected dates
                $rules['selectedDates'] = [
                    'required',
                    'array',
                    function ($attribute, $value, $fail) {
                        if (!is_array($value) || count($value) < 5) {
                            $fail('Mandatory leave requires at least 5 selected days.');
                        }
                    }
                ];
                break;

            case 8: // Study leave
                $rules['study'] = 'required|in:completion_masters,examination,others';
                if ($this->study === 'others') {
                    $rules['study_other_purpose'] = 'required';
                }
                break;
        }


        return $rules;
    }

    public function messages()
    {
        return [
            'type.required' => 'Leave type is required.',
            'type.exists' => 'The selected leave type does not exist.',
            'duration.required' => 'Leave duration is required.',
            'duration.in' => 'Invalid leave duration selected.',

            'selectedDates.required' => 'Please select at least one date for your leave.',
            'selectedDates.array' => 'The selected dates must be in a valid format.',
            'selectedDates.min' => 'You must select at least :min day(s) of leave.',

            'location.required' => 'The location field is required.',
            'location.in' => 'The location must be either "ph" or "abroad".',

            'location_specific.required' => 'The specific location field is required.',

            'confinement.required' => 'The confinement field is required.',
            'illness.required' => 'The illness field is required.',

            'commutation.required' => 'Please indicate if commutation is requested.',
            'commutation.in' => 'Commutation must be either "yes" or "no".',

            'study.required' => 'Please select the purpose of your study leave.',
            'study.in' => 'The selected study leave purpose is invalid.',
            'study_other_purpose.required' => 'Please specify the other purpose of your study leave.',
        ];
    }

     public function selectDuration() {

        if(!empty($this->duration)) {
            if($this->duration == 2) {
                return $this->isMoreThanOne = true;
            }

            return $this->isMoreThanOne = false;
        } else {
            return $this->isMoreThanOne = null;
        }
    }

    public function save(bool $isNotify = true) {

        $this->validate();

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Yes, I am sure that all the information I have provided is accurate and true. This ensures that there will be no issues as we proceed.';
            $action = 'save';
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        } else {

            try {
                DB::beginTransaction();

                $selectedCount = count($this->selectedDates);

                if (in_array($this->type, [1, 2]) && $this->duration !== 'wholeday') {
                    $daysCovered = $selectedCount / 2;
                } else {
                    $daysCovered = $selectedCount;
                }

                $employeeLeaveModel = EmployeeLeave::class;
                $leaveTypeModel = LeaveType::find($this->type);
                $leaveCreditsModel = LeaveCredits::class;

                $pendingApplications = $employeeLeaveModel::where('employee_no', $this->employee_no)
                    ->where('status', false)
                    ->count();

                $max_pending_application = 5; // env('MAX_PENDING_LEAVE_APPLICATION', 5);
// dd($pendingApplications, $max_pending_application);
                if ($pendingApplications >= $max_pending_application) {
                    return $this->dispatch('alert', [
                        'showAlert' => true,
                        'status' => 'error',
                        'title' => 'Oops',
                        'message' => 'Unfortunately, you have reached your maximum limit for leave applications. You currently have ' . $pendingApplications . ' applications awaiting approval.'
                    ]);
                }

                // Check overlapping leave dates
                $existingLeave = EmployeeLeaveDates::whereHas('employeeLeave', function ($query) {
                    $query->where('employee_no', $this->employee_no)
                        ->where('status', 'pending')
                        ->where('isDeleted', false);
                })->whereIn('date', $this->selectedDates)->exists();
                // dd($existingLeave);
                if ($existingLeave) {
                    return $this->dispatch('alert', [
                        'showAlert' => true,
                        'status' => 'error',
                        'title' => 'Oops',
                        'message' => 'You already have an existing leave application on your selected date(s).'
                    ]);
                }

                // Validate leave credits
                if (in_array($this->type, [1, 2, 3])) {
                    $leaveCode = strtolower($leaveTypeModel->code);
                    $leaveCard = EmployeeLeaveCard::where('employee_no', $this->employee_no)
                        ->where('year', Carbon::now()->year);

                    if (!$leaveCard->exists()) {
                        return $this->dispatch('alert', [
                            'showAlert' => true,
                            'status' => 'error',
                            'title' => 'Oops',
                            'message' => 'VL, SL, and MFL are not available. Please try again later.'
                        ]);
                    }

                    $leaveCard = $leaveCard->orderBy('year', 'asc')->get()->last();
                    $leaveTotalCredits = $this->type == 3
                        ? (float) ($leaveCard->vl_bal ?? 0)
                        : (float) ($leaveCard->{$leaveCode . '_bal'} ?? 0);

                    if ($this->type == 3 && $leaveTotalCredits <= 10) {
                        return $this->dispatch('alert', [
                            'showAlert' => true,
                            'status' => 'error',
                            'title' => 'Oops',
                            'message' => 'You cannot use Mandatory/Forced Leave if your VL credits are 10 or below.'
                        ]);
                    }

                    $leaveEquiv = round($daysCovered * 1.00, 3);

                    if (in_array($this->type, [1, 2]) && !$this->accepts_autwopay) {
                        if ($leaveTotalCredits == 0 || $leaveEquiv > $leaveTotalCredits) {
                            $this->accepts_autwopay = true;
                            return $this->dispatch('showConfirmation', [
                                'title' => 'Please be Informed',
                                'message' => "You’re requesting {$daysCovered} day(s) of leave but only have {$leaveTotalCredits} credits. This may count as Absence Without Pay (AUT w/o pay). Proceed?",
                                'action' => 'save'
                            ]);
                        }
                    }
                } else {    
                    // dd('here');
                    $leaveCredits = $leaveCreditsModel::where('leave_type_id', $this->type)
                        ->where('employee_no', $this->employee_no)
                        ->first();
                    
                    $leaveCredits = (float) $leaveCredits->credits ?? 0;

                    if (!$leaveCredits || $leaveCredits == 0) {
                        return $this->dispatch('alert', [
                            'showAlert' => true,
                            'status' => 'error',
                            'title' => 'Oops',
                            'message' => "You have no available credits for <b>{$leaveTypeModel->name}</b>."
                        ]);
                    }

                    if ($daysCovered > $leaveCredits) {
                        return $this->dispatch('alert', [
                            'showAlert' => true,
                            'status' => 'error',
                            'title' => 'Oops',
                            'message' => "You're applying for {$daysCovered} day(s), but only have {$leaveCredits} credit(s) left."
                        ]);
                    }
                }

                // Save or update leave
                $employeeLeave = $employeeLeaveModel::updateOrCreate([
                    'id' => $this->record_id,
                ], [
                    'employee_no' => $this->employee_no,
                    'leave_id' => $this->type,
                    'duration' => $this->duration,
                    'location' => $this->location ?? null,
                    'location_specific' => $this->location_specific ?? null,
                    'confinement' => $this->confinement ?? null,
                    'illness' => $this->illness ?? null,
                    'study' => $this->study ?? null,
                    'study_other_purpose' => $this->study_other_purpose ?? null,
                    'commutation' => $this->commutation ?? null,
                ]);

                // If updating, remove old dates first
                if ($this->record_id) {
                    EmployeeLeaveDates::where('employee_leave_id', $employeeLeave->id)->delete();
                }

                // Insert selectedDates
                $dates = array_map(fn ($date) => [
                    'employee_leave_id' => $employeeLeave->id,
                    'employee_no' => $this->employee_no,
                    'date' => $date,
                ], $this->selectedDates);

                EmployeeLeaveDates::insert($dates);

                DB::commit();

                if (is_null($this->record_id)) {
                    // Notify approvers (admin/manager) in web portal.
                    // IMPORTANT: this must NEVER block the employee submit flow.
                    try {
                        $approverRole = (string) config('ess.approver_role', 'admins');
                        $allowSuperadmin = filter_var(config('ess.allow_superadmin', true), FILTER_VALIDATE_BOOLEAN);

                        $roleCandidates = array_values(array_unique(array_filter([
                            $approverRole,
                            // common legacy role names
                            'admins',
                            'admin',
                            'manager',
                            $allowSuperadmin ? 'superadmin' : null,
                        ])));

                        $message = "Employee <strong>{$this->employee_no}</strong> submitted a leave application.";
                        $redirect = route('ess.leave');

                        $rolesToNotify = Role::where('guard_name', 'web')
                            ->whereIn('name', $roleCandidates)
                            ->pluck('name')
                            ->toArray();

                        foreach ($rolesToNotify as $roleName) {
                            $approvers = User::role($roleName, 'web')->get();
                            foreach ($approvers as $approver) {
                                $approver->notify(new Notifications('info', $message, $redirect, (string) $roleName));
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Leave apply: approver notify failed', [
                            'employee_no' => $this->employee_no,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    $this->dispatch('alert', [
                        'showAlert' => true,
                        'status' => 'success',
                        'title' => 'Yey!',
                        'message' => 'Your application has been submitted. Please download the form and secure the required signatures.',
                        'redirect' => '_reload'
                    ]);

                    $this->resetExcept('employee_no', 'employee_id', 'leaveTypes');
                    $this->accepts_autwopay = false;

                    return;
                } else {
                    return $this->dispatch('alert', [
                        'showAlert' => true,
                        'status' => 'success',
                        'title' => 'Yey!',
                        'message' => 'Your application has been successfully updated.',
                        'redirect' => '_reload'
                    ]);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->dispatch('alert', [
                    'showAlert' => true,
                    'status' => 'error',
                    'title' => 'Oops',
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }

        }
    }

    public function render()
    {
        return view('livewire.employee.leave.apply');
    }
}
