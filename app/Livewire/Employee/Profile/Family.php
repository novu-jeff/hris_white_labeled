<?php

namespace App\Livewire\Employee\Profile;

use App\Models\EmployeeAccount;
use App\Models\EmployeeInformation;
use App\Models\EmployeeParents;
use App\Models\EmployeeUpdateParents;
use App\Notifications\Notifications;
use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class Family extends Component
{
    public $employee_id;
    public $employee_no;

    public $originalData;
    public $records;

    protected $listeners = ['save'];

    public function mount() {
       // dd(123);
        $this->loadRecords();
    }

    public function loadRecords() {

        $user = Auth::guard('employee')->user() ?? Auth::user();
        if (!$user) {
            // Session expired / wrong guard - redirect to employee login.
            return redirect()->route('employee.login');
        }

        $this->employee_no = $user->employee_no;
        $this->employee_id = $user->id;

        $updated = EmployeeUpdateParents::where('employee_no', $this->employee_no)->first();
        $stored = EmployeeParents::where('employee_no', $this->employee_no)->first();

        $data = !empty($updated) ? $updated : $stored;

        $this->originalData = $data;
        $this->records = $this->formatRecords($data);

    }

    protected function formatRecords($data) {
        return [
            'spouse_surname' => $data->spouse_surname ?? null,
            'spouse_firstname' => $data->spouse_firstname ?? null,
            'spouse_middlename' => $data->spouse_middlename ?? null,
            'spouse_suffix' => $data->spouse_suffix ?? null,
            'spouse_occupation' => $data->spouse_occupation ?? null,
            'spouse_business_name_employer' => $data->spouse_business_name_employer ?? null,
            'spouse_business_address' => $data->spouse_business_address ?? null,
            'spouse_contact_no' => $data->spouse_contact_no ?? null,
            'father_surname' => $data->father_surname ?? null,
            'father_firstname' => $data->father_firstname ?? null,
            'father_middlename' => $data->father_middlename ?? null,
            'father_suffix' => $data->suffix ?? null,
            'mother_surname' => $data->mother_surname ?? null,
            'mother_firstname' => $data->mother_firstname ?? null,
            'mother_middlename' => $data->mother_middlename ?? null,
        ];
    }

    protected function rules() {
        return [
            'records.spouse_surname' => 'nullable|string|max:255',
            'records.spouse_firstname' => 'nullable|string|max:255',
            'records.spouse_middlename' => 'nullable|string|max:255',
            'records.spouse_suffix' => 'nullable|string|max:10',
            'records.spouse_occupation' => 'nullable|string|max:255',
            'records.spouse_business_name_employer' => 'nullable|string|max:255',
            'records.spouse_business_address' => 'nullable|string|max:255',
            'records.spouse_contact_no' => 'nullable|string|max:20',
            
            'records.father_surname' => 'nullable|string|max:255',
            'records.father_firstname' => 'nullable|string|max:255',
            'records.father_middlename' => 'nullable|string|max:255',
            'records.father_suffix' => 'nullable|string|max:10',

            'records.mother_surname' => 'nullable|string|max:255',
            'records.mother_firstname' => 'nullable|string|max:255',
            'records.mother_middlename' => 'nullable|string|max:255',
        ];
    }

    protected function messages() {
        return [
            'records.spouse_surname.string' => 'Spouse surname must be a valid string.',
            'records.spouse_firstname.string' => 'Spouse first name must be a valid string.',
            'records.spouse_middlename.string' => 'Spouse middle name must be a valid string.',
            'records.spouse_suffix.string' => 'Spouse suffix must be a valid string.',
            'records.spouse_suffix.max' => 'Spouse suffix must not exceed 10 characters.',
            'records.spouse_occupation.string' => 'Spouse occupation must be a valid string.',
            'records.spouse_business_name_employer.string' => 'Spouse employer/business name must be a valid string.',
            'records.spouse_business_address.string' => 'Spouse business address must be a valid string.',
            'records.spouse_contact_no.string' => 'Spouse contact number must be a valid string.',

            'records.father_surname.string' => 'Father\'s surname must be a valid string.',
            'records.father_firstname.string' => 'Father\'s first name must be a valid string.',
            'records.father_middlename.string' => 'Father\'s middle name must be a valid string.',
            'records.father_suffix.string' => 'Father\'s suffix must be a valid string.',
            'records.father_suffix.max' => 'Father\'s suffix must not exceed 10 characters.',

            'records.mother_surname.string' => 'Mother\'s surname must be a valid string.',
            'records.mother_firstname.string' => 'Mother\'s first name must be a valid string.',
            'records.mother_middlename.string' => 'Mother\'s middle name must be a valid string.',
        ];
    }

    public function hasChanges()
    {
        $originalData = $this->originalData?->toArray() ?? [];
        $records = $this->records;

        foreach ($records as $key => $newValue) {
            if (!array_key_exists($key, $originalData)) {
                return true;
            }

            $oldValue = $originalData[$key];

            if ($newValue !== $oldValue) {
                return true;
            }
        }

        return false;
    }

    public function setErrorActiveTabAccordions(array $errorKeys) {
        $this->dispatch('scrollToError', $errorKeys);
    }

    public function save(bool $isNotify = true) {

        if(!$this->hasChanges()) {
            return $this->dispatch('alert', [
                'status' => 'info',
                'title' => 'Please be informed!',
                'isRemoveRowDT' => false,
                'showAlert' => true,
                'message' => 'Unable to save because no changes were made, feel free to edit or update your informations first before saving. Thank you!'
            ]);
        }

        $employee_no = $this->employee_no;
        $record = EmployeeInformation::where('employee_no', $employee_no)->first();
        
        if (!$record) {
            return $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Oops',
                'isRemoveRowDT' => false,
                'showAlert' => true,
                'message' => 'Error: Saving a non-existent employee!'
            ]);
        }

        try {
            $this->validate($this->rules());
        } catch (ValidationException $e) {
            $this->setErrorActiveTabAccordions($e->validator->errors()->keys());
            throw $e;
        }

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Yes, I am sure that all the information I have provided is accurate and true. This ensures that there will be no issues as we proceed.';
            $action = 'save';
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

            return;

        } else {
            
            DB::beginTransaction();

            try {

                $data = $this->records;

                Log::info('FAMILY DB PAYLOAD (BEFORE SAVE)', [
                    'employee_no' => $employee_no,
                    'payload' => [
                        'spouse_surname' => $data['spouse_surname'] ?? null,
                        'spouse_firstname' => $data['spouse_firstname'] ?? null,
                        'spouse_middlename' => $data['spouse_middlename'] ?? null,
                        'spouse_suffix' => $data['spouse_suffix'] ?? null,
                        'spouse_occupation' => $data['spouse_occupation'] ?? null,
                        'spouse_business_name_employer' => $data['spouse_business_name_employer'] ?? null,
                        'spouse_business_address' => $data['spouse_business_address'] ?? null,
                        'spouse_contact_no' => $data['spouse_contact_no'] ?? null,
                        'father_surname' => $data['father_surname'] ?? null,
                        'father_firstname' => $data['father_firstname'] ?? null,
                        'father_middlename' => $data['father_middlename'] ?? null,
                        'father_suffix' => $data['father_suffix'] ?? null,
                        'mother_surname' => $data['mother_surname'] ?? null,
                        'mother_firstname' => $data['mother_firstname'] ?? null,
                        'mother_middlename' => $data['mother_middlename'] ?? null,
                    ],
                ]);

                EmployeeUpdateParents::updateOrCreate([
                    'employee_no' => $employee_no
                ],[
                    'employee_no' => $employee_no,
                    'spouse_surname' => $data['spouse_surname'] ?? null,
                    'spouse_firstname' => $data['spouse_firstname'] ?? null,
                    'spouse_middlename' => $data['spouse_middlename'] ?? null,
                    'spouse_suffix' => $data['spouse_suffix'] ?? null,
                    'spouse_occupation' => $data['spouse_occupation'] ?? null,
                    'spouse_business_name_employer' => $data['spouse_business_name_employer'] ?? null,
                    'spouse_business_address' => $data['spouse_business_address'] ?? null,
                    'spouse_contact_no' => $data['spouse_contact_no'] ?? null,
                    'father_surname' => $data['father_surname'] ?? null,
                    'father_firstname' => $data['father_firstname'] ?? null,
                    'father_middlename' => $data['father_middlename'] ?? null,
                    'father_suffix' => $data['father_suffix'] ?? null,
                    'mother_surname' => $data['mother_surname'] ?? null,
                    'mother_firstname' => $data['mother_firstname'] ?? null,
                    'mother_middlename' => $data['mother_middlename'] ?? null,
                ]);        
                
                DB::commit();

                $this->dispatch('alert', [
                    'status' => 'success',
                    'title' => 'Success!', 
                    'isRemoveRowDT' => false,
                    'isReloadDT' => false,
                    'message' => 'You\'re profile is now in pending for HR\'s approval. We\'ll sent you a notification once approved. Thank you!',
                ]);

                $user = EmployeeAccount::with('personal')->find($this->employee_id);
                $personal = $user->personal ?? \App\Models\EmployeePersonal::where('employee_no', $this->employee_no)->first();
                $name = $personal ? trim($personal->firstname . ' ' . $personal->lastname) : '';
                $display = $name !== '' ? e($name) . ' (' . e($this->employee_no) . ')' : e($this->employee_no);
                $message = 'Employee <strong>' . $display . '</strong> has submitted his/her updated <strong>profile information</strong>.';
                $redirect = route('ess.approval-profile.show', ['employee_no' => $user->employee_no, 'form' => 'family']);
                $user->notify(new Notifications('info', $message, $redirect, 'admin'));

                return;
                
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->dispatch('alert', [
                    'status' => 'error',
                    'title' => 'Oops!',
                    'isRemoveRowDT' => true,
                    'showAlert' => true,
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
        }

    }

    public function render()
    {
        return view('livewire.employee.profile.family');
    }
}
