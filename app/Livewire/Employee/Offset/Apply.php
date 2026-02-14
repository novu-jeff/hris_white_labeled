<?php

namespace App\Livewire\Employee\Offset;

use App\Models\EmployeeAccount;
use App\Models\EmployeeOffsetApplication;
use App\Models\EmployeePersonal;
use App\Models\OffsetCredits;
use App\Notifications\Notifications;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Apply extends Component
{
    public $employee_no, $employee_id;

    public $firstname, $middlename, $lastname, $section, 
    $position, $supervisor_name, $department; 
    
    public $date_filed, $offset_date_from, $offset_date_to, 
    $purpose, $requested_by, $status, 
    $approved_by_id;

    public $record_id;
    public $remaining_offset_credits = 0;

    protected $listeners = ['save'];

    public function mount($record_id = null) {
        $this->record_id = $record_id;
        $this->loadRecords();
    }

    public function loadRecords() {

        $employee_no = Auth::user()->employee_no;
        $employee_id = Auth::user()->id;

        $this->employee_no = $employee_no;
        $this->employee_id = $employee_id;
        $this->remaining_offset_credits = (float) (OffsetCredits::where('employee_no', $employee_no)->value('credits') ?? 0);
        
        $employee = DB::table('employee_account')
            ->leftJoin('employee_personal', 'employee_account.employee_no', '=', 'employee_personal.employee_no')
            ->leftJoin('employee_information', 'employee_account.employee_no', '=', 'employee_information.employee_no')
            ->leftJoin('positions', 'employee_information.position_id', '=', 'positions.id')
            ->leftJoin('sections', 'employee_information.section_id', '=', 'sections.id')
            ->leftJoin('departments', 'sections.department_id', '=', 'departments.id')
            ->select(
                'employee_account.employee_no',
                'employee_personal.firstname',
                'employee_personal.middlename',
                'employee_personal.lastname',

                'positions.code as position_code',
                'positions.name as position_name',
                
                'sections.name as section_name',
                'sections.code as section_code',
                'sections.id as section_id',

                'departments.name as department_name',
                'departments.code as department_code',

            )
            ->where('employee_account.employee_no', $this->employee_no)
            ->get();

        $sectionId = $employee->first()->section_id ?? null;
        $positionName = $employee->first()->position_name ?? '';
        $supervisorName = 'N/A';

        $isDeptSupervisor = false;
        if ($sectionId) {
            $section = DB::table('sections')->where('id', $sectionId)->first();
            if ($section && $section->supervisor_id === $employee_no) {
                $isDeptSupervisor = true;
            } else {
                $pn = strtolower($positionName);
                if (str_contains($pn, 'supervisor') || str_contains($pn, 'manager') || str_contains($pn, 'head') || str_contains($pn, 'chief') || str_contains($pn, 'director')) {
                    $isDeptSupervisor = true;
                }
            }
        }

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
                        ->where('employee_information.employee_no', '!=', $employee_no)
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
                            ->where('employee_information.employee_no', '!=', $employee_no)
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

        if(!is_null($this->record_id)) {
            $dataToEdit = EmployeeOffsetApplication::where('id', $this->record_id)
                ->where('employee_no', $employee_no)
                ->first();
            
            $this->date_filed = $dataToEdit->date_filed;
            $this->offset_date_from = $dataToEdit->offset_date_from;
            $this->offset_date_to = $dataToEdit->offset_date_to;
            $this->purpose = $dataToEdit->purpose;
        }
    
        $this->firstname = $employee->first()->firstname;
        $this->middlename = substr($employee->first()->middlename, 0, 1);
        $this->lastname = $employee->first()->lastname;
        $this->section = $employee->first()->section_name . ' ' . '(' . $employee->first()->section_code . ')';
        $this->position = $employee->first()->position_name;
        $this->supervisor_name = $supervisorName;
        $this->department = $employee->first()->department_name . ' ' . '(' . $employee->first()->department_code . ')';
    }

    public function rules() {
        $rules = [
            'employee_no' =>  'required',
            'date_filed' =>  'required|date',
            'offset_date_from' => 'required|date',
            'offset_date_to' => ['required', 'date'],
            'purpose' => 'required|max:255',
        ];

        // Activity date must be within 60 days before or after offset date
        if (!empty($this->offset_date_from)) {
            try {
                $from = Carbon::parse($this->offset_date_from);
                $minDate = $from->copy()->subDays(60)->format('Y-m-d');
                $maxDate = $from->copy()->addDays(60)->format('Y-m-d');
                $rules['offset_date_to'] = array_merge($rules['offset_date_to'], [
                    "after_or_equal:{$minDate}",
                    "before_or_equal:{$maxDate}",
                ]);
            } catch (\Exception $e) {
                // keep required|date only if parse fails
            }
        }

        return $rules;
    }

    protected function messages() {
        return [
            'offset_date_from.required' => 'The offset date from is required.',
            'offset_date_from.date' => 'The offset date from must be a valid date.',
            'offset_date_to.required' => 'The offset date to is required.',
            'offset_date_to.date' => 'The offset date to must be a valid date.',
            'offset_date_to.after_or_equal' => 'The activity date must be within 60 days before or after the offset date.',
            'offset_date_to.before_or_equal' => 'The activity date must be within 60 days before or after the offset date.',
        ];
    }

    public function save(bool $isNotify = true) {
        $this->validate($this->rules(), $this->messages());

        if (is_null($this->record_id) && $this->remaining_offset_credits <= 0) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'No Offset Credits',
                'message' => 'You do not have available offset credits. Please contact HR/Admin.',
            ]);
        }

        if($isNotify) {
            $title = 'Are you sure to continue?';
            $message = 'Yes, I am sure that all the information I have provided is accurate and true. This ensures that there will be no issues as we proceed.';
            $action = 'save';
            Log::info('employee no: ' . $this->employee_no);
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);
        } else {

            DB::beginTransaction();
            try {
                $model = EmployeeOffsetApplication::class;

                $model::updateOrCreate(
                    ['id' => $this->record_id],
                    [
                        'employee_no' => $this->employee_no,
                        'date_filed' => $this->date_filed,
                        'offset_date_from' => $this->offset_date_from,
                        'offset_date_to' => $this->offset_date_to,
                        'purpose' => $this->purpose
                    ]
                );

                DB::commit();

                // reset form if not edit
                if(is_null($this->record_id)){

                    $this->dispatch('alert', [
                        'showAlert' => true,
                        'status' => 'success',
                        'title' => 'Yey!', 
                        'message' => 'Your offset application has been successfully submitted. This allows you to work on non-working days to compensate for absences. You will be notified once it is reviewed.'
                    ]);

                    $user = EmployeeAccount::find($this->employee_id);
                    $personal = $user->personal ?? EmployeePersonal::where('employee_no', $this->employee_no)->first();
                    $name = $personal ? trim($personal->firstname . ' ' . $personal->lastname) : '';
                    $display = $name !== '' ? e($name) . ' (' . e($this->employee_no) . ')' : e($this->employee_no);
                    $message = 'Employee <strong>' . $display . '</strong> has submitted an <strong>offset application</strong>.';
                    $redirect = route('ess.offset');
                    $user->notify(new Notifications('info', $message, $redirect, 'admin'));

                    $this->resetExcept('employee_id', 'employee_no', 'firstname', 'lastname', 'middlename', 'section', 'position', 'department', 'branch');

                    return;

                } else {
                    return $this->dispatch('alert', [
                        'showAlert' => true,
                        'status' => 'success',
                        'title' => 'Yey!', 
                        'message' => 'Your offset application has been successfully updated.'
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
        return view('livewire.employee.offset.apply');
    }
}
