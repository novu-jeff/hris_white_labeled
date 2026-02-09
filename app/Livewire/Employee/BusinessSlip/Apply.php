<?php

namespace App\Livewire\Employee\BusinessSlip;

use App\Models\EmployeeAccount;
use App\Models\EmployeeBusinessSlip;
use App\Models\EmployeePersonal;
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
    $position, $branch, $department; 
    
    public $date_filed, $destination, 
    $purpose, $departure_time, $arrival_time, $requested_by, $status, 
    $approved_by_id;

    public $record_id;

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
        
        $employee = DB::table('employee_account')
            ->leftJoin('employee_personal', 'employee_account.employee_no', '=', 'employee_personal.employee_no')
            ->leftJoin('employee_information', 'employee_account.employee_no', '=', 'employee_information.employee_no')
            ->leftJoin('positions', 'employee_information.position_id', '=', 'positions.id')
            ->leftJoin('sections', 'employee_information.section_id', '=', 'sections.id')
            ->leftJoin('branches', 'sections.branch_id', '=', 'branches.id')
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

                'branches.name as branch_name',
                'branches.code as branch_code',

                'departments.name as department_name',
                'departments.code as department_code',

            )
            ->where('employee_account.employee_no', $this->employee_no)
            ->get();


            if(!is_null($this->record_id)) {
            $dataToEdit = EmployeeBusinessSlip::where('id', $this->record_id)
                ->where('employee_no', $employee_no)
                ->first();
            
            $this->date_filed = $dataToEdit->date_filed;
            $this->destination = $dataToEdit->destination;
            $this->purpose = $dataToEdit->purpose;
            $this->departure_time = Carbon::parse($dataToEdit->departure_time)->format('h:i A');
            $this->arrival_time = Carbon::parse($dataToEdit->arrival_time)->format('h:i A');
        }
    
        $this->firstname = $employee->first()->firstname;
        $this->middlename = substr($employee->first()->middlename, 0, 1);
        $this->lastname = $employee->first()->lastname;
        $this->section = $employee->first()->section_name . ' ' . '(' . $employee->first()->section_code . ')';
        $this->position = $employee->first()->position_name;
        $this->branch = $employee->first()->branch_name . ' ' . '(' . $employee->first()->branch_code . ')';
        $this->department = $employee->first()->department_name . ' ' . '(' . $employee->first()->department_code . ')';
    }

    public function rules() {
        $rules = [
            'employee_no' =>  'required',
            'date_filed' =>  'required|date',
            'destination' => 'required|max:255',
            'purpose' => 'required|max:255',
            'departure_time' =>  'required',
            'arrival_time' =>  'required',
        ];
    
        return $rules;
    }

    public function save(bool $isNotify = true) {
        $this->validate();

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
                $model = EmployeeBusinessSlip::class;


                $departure_time = Carbon::createFromFormat('h:i A', $this->departure_time)->format('H:i:s');
                $arrival_time = Carbon::createFromFormat('h:i A', $this->arrival_time)->format('H:i:s');

                $model::updateOrCreate(
                    ['id' => $this->record_id],
                    [
                        'employee_no' => $this->employee_no,
                        'date_filed' => $this->date_filed,
                        'destination' => $this->destination,
                        'purpose' => $this->purpose,
                        'departure_time' => $departure_time,
                        'arrival_time' => $arrival_time
                    ]
                );

                DB::commit();

                // reset form if not edit
                if(is_null($this->record_id)){


                    $this->dispatch('alert', [
                        'showAlert' => true,
                        'status' => 'success',
                        'title' => 'Yey!', 
                        'message' => 'Your application has been successfully submitted. You can now download the form to begin obtaining the required signatures. Once completed, please send a copy back to us. Thank you for your cooperation.'
                    ]);

                    $user = EmployeeAccount::find($this->employee_id);
                    $personal = $user->personal ?? EmployeePersonal::where('employee_no', $this->employee_no)->first();
                    $name = $personal ? trim($personal->firstname . ' ' . $personal->lastname) : '';
                    $display = $name !== '' ? e($name) . ' (' . e($this->employee_no) . ')' : e($this->employee_no);
                    $message = 'Employee <strong>' . $display . '</strong> has submitted an application for <strong>official business slip</strong>.';
                    $redirect = route('ess.obs');
                    $user->notify(new Notifications('info', $message, $redirect, 'admin'));

                    $this->resetExcept('employee_id', 'employee_no', 'firstname', 'lastname', 'middlename', 'section', 'position', 'department', 'branch');

                    return;

                } else {
                    return $this->dispatch('alert', [
                        'showAlert' => true,
                        'status' => 'success',
                        'title' => 'Yey!', 
                        'message' => 'Your application has been successfully updated. You can now download the form to begin obtaining the required signatures. Once completed, please send a copy back to us. Thank you for your cooperation.'
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
        return view('livewire.employee.business-slip.apply');
    }
}
