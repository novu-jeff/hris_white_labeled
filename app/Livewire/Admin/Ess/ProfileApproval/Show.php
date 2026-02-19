<?php

namespace App\Livewire\Admin\Ess\ProfileApproval;

use App\Models\EmployeeAccount;
use App\Models\EmployeeChildren;
use App\Models\EmployeeCivilService;
use App\Models\EmployeeEducation;
use App\Models\EmployeeEmploymentHistory;
use App\Models\EmployeeOtherWorks;
use App\Models\EmployeeParents;
use App\Models\EmployeePersonal;
use App\Models\EmployeeSkillsHobbies;
use App\Models\EmployeeTrainings;
use App\Models\EmployeeUpdateChildren;
use App\Models\EmployeeUpdateCivilService;
use App\Models\EmployeeUpdateEducation;
use App\Models\EmployeeUpdateEmploymentHistory;
use App\Models\EmployeeUpdateOtherWorks;
use App\Models\EmployeeUpdateParents;
use App\Models\EmployeeUpdatePersonal;
use App\Models\EmployeeUpdateSkillsHobbies;
use App\Models\EmployeeUpdateTrainings;
use App\Notifications\Notifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class Show extends Component
{

    public $form;
    public $employee_no;
    public $tabsHasChanges;
    protected $listeners = ['approved', 'disapproved'];
    public $profile;

    public function mount() {
        $this->tabsHasChanges = $this->getTabsHasChanges();
    }

    public function approved(bool $isNotify = true) {

        if (Gate::denies('write employee-profile-approval')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!', 
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'The action cannot be undone or reverted!';
            $action = 'approved';
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

            return;

        }

        DB::beginTransaction();

        try {
            $methods = [
                'updatePersonalData',
                'updateEducationData',
                'updateParentsData',
                'updateChildrenData',
                'updateEmploymentData',
                'updateCivilServiceData',
                'updateTrainingData',
                'updateOthersData',
                'updateSkillsData',
            ];

            foreach ($methods as $method) {
                $this->$method($this->employee_no);
            }

            $this->remove();

            DB::commit();

            EmployeeAccount::where('employee_no', $this->employee_no)
                ->first()?->notify(new Notifications(
                    'success',
                    "Your profile update application was <strong>approved</strong>.",
                    route('employee.profile', ['employee_no' => $this->employee_no, 'form' => 'details']),
                    'employee'
                ));

            return $this->dispatch('alert', [
                'status' => 'success',
                'title' => 'Success!',
                'isRemoveRowDT' => false,
                'isReloadDT' => false,
                'message' => 'Employee ' . strtoupper($this->employee_no) . ' was approved successfully.',
                'redirect' => route('ess.approval-profile.index')
            ]);

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

    public function disapproved(bool $isNotify = true) {

         if (Gate::denies('write employee-profile-approval')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!', 
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        if($isNotify) {
            
            $title = 'Are you sure to continue?';
            $message = 'The action cannot be undone or reverted!';
            $action = 'disapproved';
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

            return;
        } 

        DB::beginTransaction();

        try {

            $this->remove();

            DB::commit();

            $user = EmployeeAccount::where('employee_no', $this->employee_no)->first();
            $user?->notify(new Notifications('success', 'You\'re profile update application was <strong>disapproved</strong>. Click this notification to view more details.', route('employee.profile', ['employee_no' => $this->employee_no, 'form' => 'details']), 'employee'));

            return $this->dispatch('alert', [
                'status' => 'success',
                'title' => 'Success!',
                'isRemoveRowDT' => false,
                'isReloadDT' => false,
                'message' => 'Employee ' . strtoupper($this->employee_no) . ' was disapproved for updating profile.',
                'redirect' => route('ess.approval-profile.index')
            ]);

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

    public function remove()
    {
        $record = EmployeePersonal::where('employee_no', $this->employee_no)->first();

        if (!$record) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops!',
                'isRemoveRowDT' => false,
                'message' => 'Error: Employee record not found.'
            ]);
        }

        collect([
            EmployeeUpdatePersonal::class,
            EmployeeUpdateParents::class,
            EmployeeUpdateChildren::class,
            EmployeeUpdateEducation::class,
            EmployeeUpdateEmploymentHistory::class,
            EmployeeUpdateCivilService::class,
            EmployeeUpdateTrainings::class,
            EmployeeUpdateOtherWorks::class,
            EmployeeUpdateSkillsHobbies::class,
        ])->each(fn ($model) => $model::where('employee_no', $this->employee_no)->delete());

        return $this->dispatch('alert', [
            'showAlert' => true,
            'status' => 'success',
            'title' => 'Success',
            'isRemoveRowDT' => true,
            'message' => 'Employee record and related data successfully deleted.'
        ]);
    }

    public function updatePersonalData($employee_no) {
     //   dd(123);
        $personal = EmployeePersonal::where('employee_no', $employee_no)->first();
        $account = EmployeeAccount::where('employee_no', $employee_no)->first();
        $update = EmployeeUpdatePersonal::where('employee_no', $employee_no)->first();

         // ---- HANDLE PROFILE PICTURE ----
        if ($this->profile) {
            // Target folder: storage/app/public/employees/{employee_no}
            $folder = storage_path("app/public/employees/{$employee_no}");

            // Create folder if it doesn't exist
            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }

            // Final path
            $filename = 'profile.' . $this->profile->extension();
            $path = $folder . '/' . $filename;

            // Move the uploaded file from temp to final folder
            $this->profile->move($folder, $filename);

            // Save relative storage path to DB
            $personal->profile = "storage/employees/{$employee_no}/{$filename}";
        }

        if ($personal && $update && $account) {
            $fields = [
                'profile', 'firstname', 'middlename', 'lastname', 'suffix', 'birthday', 'age',
                'civil_status', 'sex', 'citizenship', 'citizenship_type', 'country',
                'present_address', 'present_province', 'present_city',
                'permanent_address', 'permanent_province', 'permanent_city',
                'mobile_number', 'tel_no', 'height', 'weight', 'blood_type',
                'gsis_no', 'pagibig_no', 'philhealth_no', 'sss_no', 'tin_no'
            ];

            Log::info('Personal data BEFORE SAVE', [
                'employee_no' => $employee_no,
                'original_personal' => $personal->only([
                     'firstname', 'middlename', 'lastname', 'suffix', 'birthday', 'age',
                    'civil_status', 'sex', 'citizenship', 'citizenship_type', 'country',
                    'present_address', 'present_province', 'present_city',
                    'permanent_address', 'permanent_province', 'permanent_city',
                    'mobile_number', 'tel_no', 'height', 'weight', 'blood_type',
                    'gsis_no', 'pagibig_no', 'philhealth_no', 'sss_no', 'tin_no'
                ]),
                'update_personal' => $update?->only([
                     'firstname', 'middlename', 'lastname', 'suffix', 'birthday', 'age',
                    'civil_status', 'sex', 'citizenship', 'citizenship_type', 'country',
                    'present_address', 'present_province', 'present_city',
                    'permanent_address', 'permanent_province', 'permanent_city',
                    'mobile_number', 'tel_no', 'height', 'weight', 'blood_type',
                    'gsis_no', 'pagibig_no', 'philhealth_no', 'sss_no', 'tin_no'
                    ]),
            ]);

            foreach ($fields as $field) {
                $personal->$field = $update->$field ?? $personal->$field;
            }
            $personal->save();

            Log::info('Account email update', [
                'employee_no' => $employee_no,
                'old_email' => $account->email,
                'new_email' => $update->email,
            ]);

            $account->email = $update->email;
            $account->save();
        }
    }

    protected function batchUpdate($employee_no, $updateModel, $targetModel, $uniqueFields, $fillableFields) {
        $updates = $updateModel::where('employee_no', $employee_no)->get();

        foreach ($updates as $update) {
            $query = ['employee_no' => $employee_no];
            foreach ($uniqueFields as $field) {
                $query[$field] = $update->$field;
            }

            $data = ['employee_no' => $employee_no];
            foreach ($fillableFields as $field) {
                $data[$field] = $update->$field ?? null;
            }

            Log::info('Batch update BEFORE SAVE', [
                'employee_no' => $employee_no,
                'model' => $targetModel,
                'match_query' => $query,
                'save_data' => $data,
            ]);

            $targetModel::updateOrCreate($query, $data);
        }
    }

    public function updateEducationData($employee_no) {
        $this->batchUpdate($employee_no, EmployeeUpdateEducation::class, EmployeeEducation::class, ['school_name'], [
            'level', 'school_name', 'course', 'from_year', 'to_year', 'highest_level', 'year_graduated', 'scholarship_honors', 'documents'
        ]);
    }

    public function updateParentsData($employee_no)
{
    // Fetch existing (approved) parents data
    $original = EmployeeParents::where('employee_no', $employee_no)->first();

    // Fetch pending update parents data
    $updates = EmployeeUpdateParents::where('employee_no', $employee_no)->get();

    // 1️⃣ Log ORIGINAL vs UPDATE parent data
    Log::info('PARENTS DATA BEFORE SAVE', [
        'employee_no' => $employee_no,
        'original_parents' => $original?->only([
            'spouse_surname', 'spouse_firstname', 'spouse_middlename', 'spouse_suffix',
            'father_surname', 'father_firstname', 'father_middlename', 'father_suffix',
            'father_business_name', 'father_business_address', 'father_tel_no',
            'mother_surname', 'mother_firstname', 'mother_middlename', 'mother_occupation',
            'mother_business_name', 'mother_business_address', 'mother_tel_no',
        ]),
        'update_parents' => $updates->map(fn ($u) => $u->only([
            'spouse_surname', 'spouse_firstname', 'spouse_middlename', 'spouse_suffix',
            'father_surname', 'father_firstname', 'father_middlename', 'father_suffix',
            'father_business_name', 'father_business_address', 'father_tel_no',
            'mother_surname', 'mother_firstname', 'mother_middlename', 'mother_occupation',
            'mother_business_name', 'mother_business_address', 'mother_tel_no',
        ]))->toArray(),
    ]);

    // 2️⃣ Log FIELD-BY-FIELD CHANGES (diff)
    if ($original) {
        foreach ($updates as $update) {
            $diff = [];

            foreach ($update->getAttributes() as $key => $value) {
                if (
                    array_key_exists($key, $original->getAttributes()) &&
                    $original->$key !== $value
                ) {
                    $diff[$key] = [
                        'old' => $original->$key,
                        'new' => $value,
                    ];
                }
            }

            if (!empty($diff)) {
                Log::info('PARENTS FIELD CHANGES DETECTED', [
                    'employee_no' => $employee_no,
                    'changes' => $diff,
                ]);
            }
        }
    }

    // 3️⃣ Proceed with actual saving
    $this->batchUpdate(
        $employee_no,
        EmployeeUpdateParents::class,
        EmployeeParents::class,
        ['spouse_surname'],
        [
            'spouse_surname', 'spouse_firstname', 'spouse_middlename', 'spouse_suffix',
            'spouse_occupation', 'spouse_business_name_employer', 'spouse_business_address', 'spouse_contact_no',
            'father_surname', 'father_firstname', 'father_middlename', 'father_suffix',
            'mother_surname', 'mother_firstname', 'mother_middlename'
        ]
    );
}


    public function updateChildrenData($employee_no) {
        $this->batchUpdate($employee_no, EmployeeUpdateChildren::class, EmployeeChildren::class, ['firstname'], [
            'firstname', 'middlename', 'lastname', 'birthdate', 'documents'
        ]);
    }

    public function updateEmploymentData($employee_no) {
        // employee_employment_history table does not have company_name column
        $this->batchUpdate($employee_no, EmployeeUpdateEmploymentHistory::class, EmployeeEmploymentHistory::class, ['position', 'from_year', 'to_year'], [
            'position', 'department', 'monthly_salary', 'salary_pay_grade', 'employment_status',
            'isGovernment', 'from_year', 'to_year', 'documents'
        ]);
    }

    public function updateCivilServiceData($employee_no) {
        $this->batchUpdate($employee_no, EmployeeUpdateCivilService::class, EmployeeCivilService::class, ['license_no'], [
            'certification', 'rating', 'date_exam', 'place_exam', 'license_no', 'date_validity', 'documents'
        ]);
    }

    public function updateTrainingData($employee_no) {
        $this->batchUpdate($employee_no, EmployeeUpdateTrainings::class, EmployeeTrainings::class, ['name'], [
            'type', 'name', 'date_from', 'date_to', 'consumed_hours', 'sponsored_by', 'documents'
        ]);
    }

    public function updateOthersData($employee_no) {
        $this->batchUpdate($employee_no, EmployeeUpdateOtherWorks::class, EmployeeOtherWorks::class, ['organization'], [
            'organization', 'address', 'date_from', 'date_to', 'consumed_hours', 'position', 'documents'
        ]);
    }

    public function updateSkillsData($employee_no) {
            $this->batchUpdate($employee_no, EmployeeUpdateSkillsHobbies::class, EmployeeSkillsHobbies::class, ['name'], [
                'name', 'recognition', 'organization', 'documents'
        ]);
    }


    public function getTabsHasChanges()
    {
        $models = [
            'personal' => EmployeeUpdatePersonal::class,
            'family' => EmployeeUpdateParents::class,
            'children' => EmployeeUpdateChildren::class,
            'education' => EmployeeUpdateEducation::class,
            'employment-history' => EmployeeUpdateEmploymentHistory::class,
            'civil-service' => EmployeeUpdateCivilService::class,
            'trainings' => EmployeeUpdateTrainings::class,
            'other-works' => EmployeeUpdateOtherWorks::class,
            'skills' => EmployeeUpdateSkillsHobbies::class,
        ];

        $typesWithRecords = collect();

        foreach ($models as $type => $model) {
            if ($model::whereNotNull('employee_no')->exists()) {
                $typesWithRecords->push($type);
            }
        }

        return $typesWithRecords->toArray() ?? []; 
    }

    public function render()
    {
        return view('livewire.admin.ess.profile-approval.show');
    }
}
