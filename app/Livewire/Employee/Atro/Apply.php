<?php

namespace App\Livewire\Employee\Atro;

use App\Models\EmployeeAccount;
use App\Models\EmployeeAtro;
use App\Models\EmployeeAtroRelative;
use App\Models\EmployeeInformation;
use App\Models\User;
use App\Notifications\Notifications;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Apply extends Component
{


    public $record_id;
    public $employee_id;
    public $employee_no;
    public $OtherEmployees;
    public array $fields = [
        'date' => '',
        'start_time' => '',
        'end_time' => '',
        'justification' => '',
        'employees' => []
    ];

    protected $listeners = ['onChange', 'save'];

    public function mount() {
        $this->loadRecords();
    }

    public function loadRecords() {

        $user = Auth::guard('employee')->user() ?? Auth::user();
        if (!$user) {
            return redirect()->route('employee.login');
        }

        $employee_no = $user->employee_no;
        $employee_id = $user->id;

        $this->employee_id = $employee_id;
        $this->employee_no = $employee_no;

        $this->getOtherEmployees();

        if(!is_null($this->record_id)) {

            $records = EmployeeAtro::with('relative')
                ->where('id', $this->record_id)
                ->where('employee_no', $employee_no)
                ->first();
        
            if(!$records) {
                return redirect()
                    ->route('employee.atro');
            }

           // $start_time = Carbon::createFromFormat('h:i A', $records->start_time)->format('H:i:s');
           // $end_time = Carbon::createFromFormat('h:i A', $records->end_time)->format('H:i:s');

            $this->fields = [
                'date' => $records->date,
                'start_time' => $records->start_time,
                'end_time' => $records->end_time,
                'justification' => $records->justification,
                'disapproval_note' => $records->disapproval_note,
                'status' => $records->status,
                'employees' => $records->relative->pluck('employee_no')->toArray(),
            ];

        }
    }

    public function onChange($value) {
        $this->fields['employees'] = $value;
        $this->dispatch('reloadSelect2');
        $this->getOtherEmployees();
    }

    private function getOtherEmployees() {
        $employees = EmployeeInformation::with('personal')
            ->where('employee_no', '!=', $this->employee_no)
            ->where('isDeleted', false)
            ->whereHas('personal')   // ← only employees WITH personal record
            ->get();

        $this->OtherEmployees = $employees;

        return;
    }

    protected function rules()
    {
        return [
            'fields.employees' => [
                function ($attribute, $value, $fail) {
                    if (empty($this->fields['employees'])) {
                        $this->dispatch('select2Err', 'Employee(s) cannot be empty.');
                    } else {
                        $this->dispatch('select2Err', '');
                    }
                }
            ],
            'fields.date' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    if (is_null($this->record_id)) {
                        $employeeNo = $this->employee_no;

                        $exists = EmployeeAtro::where('employee_no', $employeeNo)
                            ->where('date', $value)
                            ->exists();

                        if ($exists) {
                            $fail('An overtime application has already been submitted for this date.');
                        }
                    }
                },
            ],
            'fields.start_time' => 'required',
            'fields.end_time' => 'required|after:fields.start_time',
            'fields.justification' => 'required|string|max:255',
        ];
    }

    protected function messages()
    {
        return [
            'fields.date.required' => 'The date field is required.',
            'fields.date.date' => 'The date must be a valid date.',
            'fields.start_time.required' => 'The start time field is required.',
            'fields.start_time.date_format' => 'The start time must be in the format HH:MM.',
            'fields.end_time.required' => 'The end time field is required.',
            'fields.end_time.date_format' => 'The end time must be in the format HH:MM.',
            'fields.end_time.after' => 'The end time must be after the start time.',
            'fields.justification.required' => 'The justification field is required.',
            'fields.justification.string' => 'The justification must be a valid string.',
            'fields.justification.max' => 'The justification may not exceed 255 characters.',
            'fields.date.unique' => 'The employee cannot have multiple records for the same date.',
        ];
    }

    public function save(bool $isNotify = true) {

        $this->validate();
    
        if ($isNotify) {
            $this->dispatch('showConfirmation', [
                'title' => 'Are you sure to continue?',
                'message' => 'Yes, I am sure that all the information I have provided is accurate and true. This ensures that there will be no issues as we proceed.',
                'action' => 'save',
            ]);
        } else {
            try {
                
                $self_ = $this->employee_no;

                if (is_null($this->record_id)) {

                    $atro = EmployeeAtro::create([
                        'employee_no' => $this->employee_no,
                        'date' => $this->fields['date'],
                        'start_time' => $this->fields['start_time'],
                        'end_time' => $this->fields['end_time'],
                        'justification' => $this->fields['justification'],
                    ]);

                    
                    // if(!in_array($self_, $this->fields['employees'])) {
                    //     $this->fields['employees'][] = $self_;
                    // }

                    foreach ($this->fields['employees'] as $employee_no) {
                        EmployeeAtroRelative::create([
                            'employee_atro_id' => $atro->id,
                            'employee_no' => $employee_no,
                        ]);
                    }

                } else {

                    $atro = EmployeeAtro::find($this->record_id);

                    $atro->update([
                        'date' => $this->fields['date'],
                        'start_time' => $this->fields['start_time'],
                        'end_time' => $this->fields['end_time'],
                        'justification' => $this->fields['justification'],
                    ]);

                    EmployeeAtroRelative::where('employee_atro_id', $atro->id)->delete();

                    if(!in_array($self_, $this->fields['employees'])) {
                        $this->fields['employees'][] = $self_;
                    }

                    foreach ($this->fields['employees'] as $employee_no) {
                        EmployeeAtroRelative::create([
                            'employee_atro_id' => $atro->id,
                            'employee_no' => $employee_no,
                        ]);
                    }
                }

                if(is_null($this->record_id)) {
                    
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

                        $message = 'Employee <strong>' . $this->employee_no . '</strong> has submitted an application for <strong>authority to render overtime</strong>.';
                        $redirect = route('ess.atro');

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
                        Log::warning('ATRO apply: approver notify failed', [
                            'employee_no' => $this->employee_no,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    $this->dispatch('alert', [
                        'showAlert' => true,
                        'status' => 'success',
                        'title' => 'Yey!', 
                        'message' => 'Your application has been submitted. You will receive an email regarding your application status as soon as we review it. Thank you for your understanding.',
                        'redirect' => '_reload'
                    ]);

                    $this->resetExcept('employee_no', 'employee_id');

                    $this->fields['employees'] = [];

                    $this->getOtherEmployees();

                    return;

                } else {
                    
                    $this->getOtherEmployees();

                    return $this->dispatch('alert', [
                        'showAlert' => true,
                        'status' => 'success',
                        'title' => 'Yey!', 
                        'message' => 'Your application has been updated. You will receive an email regarding your application status as soon as we review it. Thank you for your understanding.',
                    ]);

                }


            } catch (\Exception $e) {

                $this->getOtherEmployees();

                $this->dispatch('alert', [
                    'showAlert' => true,
                    'status' => 'error',
                    'title' => 'Oops',
                    'message' => 'Error: ' . $e->getMessage(),
                ]);
            }
        }
    }
    

    public function render()
    {
        return view('livewire.employee.atro.apply');
    }
}
