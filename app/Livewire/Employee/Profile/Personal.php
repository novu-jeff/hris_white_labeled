<?php

namespace App\Livewire\Employee\Profile;

use App\Models\EmployeeAccount;
use App\Models\EmployeeInformation;
use App\Models\EmployeePersonal;
use App\Models\EmployeeUpdatePersonal;
use App\Notifications\Notifications;
use Auth;
use Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;

class Personal extends Component
{
    use WithFileUploads;

   // public array $records;
    
    public $employee_id;
    public $employee_no;
    public array $countries;
    public bool $isDualCitizenship = false;
    public bool $isFromUpdate = false;
    public bool $isMarried = false;
    public bool $hasBirthCert = false;
    public bool $hasMarriageCert = false;
    public $originalData;
    public $records;

    protected $listeners = ['save'];

    public function mount() {
        $this->loadRecords();
        $this->loadCountries();
    }

    public function loadRecords() {

        $user = Auth::guard('employee')->user() ?? Auth::user();
        if (!$user) {
            // Session expired / wrong guard - redirect to employee login.
            return redirect()->route('employee.login');
        }

        $this->employee_no = $user->employee_no;
        $this->employee_id = $user->id;

        $updated = EmployeeUpdatePersonal::where('employee_no', $this->employee_no)->first();
        $stored = EmployeePersonal::where('employee_no', $this->employee_no)->first();

        $data = !empty($updated) ? $updated : $stored;

        $this->originalData = $data;
        $this->records = $this->formatRecords($data);

    }

    public function loadCountries() {

        if (Cache::has('countries')) {
            return $this->countries = Cache::get('countries');
        }

        try {
            $response = Http::timeout(10)->withOptions(['cookies' => false])
                ->get('https://restcountries.com/v3.1/all?fields=name');
            $countries = $response->json();

            // Validate response structure
            if (!is_array($countries)) {
                throw new \Exception('Invalid API response');
            }

            usort($countries, fn($a, $b) => strcmp($a['name']['common'], $b['name']['common']));

            Cache::put('countries', $countries, now()->addHours(24));

            $this->countries = $countries;
            return $this->countries;
        } catch (\Exception $e) {
            logger()->error('Failed to load countries: ' . $e->getMessage());
            return $this->countries = [];
        }
    }

    public function select_change(string $property) {

        if($property == 'citizenship') {
            if($this->records['citizenship'] == 'dual_citizenship') {
                $this->isDualCitizenship = true;
            } else {
                $this->isDualCitizenship = false;
            }
        }

        if($property == 'civil_status') {
            if($this->records['civil_status'] == 'married') {
                $this->isMarried = true;
            } else {
                $this->isMarried = false;
            }
        }
    }

    protected function formatRecords($data) {


        if($data->birth_certificate) {
            $this->hasBirthCert = true;
        } 

        if($data->marriage_certificate) {
            $this->hasMarriageCert = true;
        } 
    
        $email = $data->email ? $data->email  : $data->account['email'];
    
        $fields = [
            'profile', 'firstname', 'middlename', 'lastname', 'suffix', 'birthday',
            'civil_status', 'sex', 'citizenship', 'citizenship_type', 'country',
            'present_address', 'present_province', 'present_city', 'permanent_address',
            'permanent_province', 'permanent_city', 'mobile_number', 'tel_no', 'height',
            'weight', 'blood_type', 'gsis_no', 'pagibig_no', 'philhealth_no', 'sss_no',
            'tin_no', 'email' 
        ];
    
        $formattedPersonal = array_combine(
            $fields, 
            array_map(fn($field) => $field === 'email' ? $email : ($data[$field] ?? null), $fields)
        );

    
        return $formattedPersonal;
    }

    protected function rules(?string $employee_no = null) {
        return [
            'records.firstname' => 'required|string|max:255',
            'records.lastname' => 'required|string|max:255',
            'records.suffix' => 'nullable|in:jr,sr,I,II,III,IV,V',
            'records.civil_status' => 'nullable|in:single,married,divorced,seperated,widowed,anulled',
            'records.sex' => 'nullable|in:male,female',
            'records.citizenship_type' => 'nullable|required_with:records.citizenship',
            'records.country' => 'required_if:records.citizenship,dual_citizenship',
            'records.mobile_number' => 'nullable|regex:/^09\d{9}$/',
            'records.email' => [
                'required',
                Rule::unique('employee_account', 'email')->ignore($employee_no, 'employee_no')
            ],
        ];
    }

    protected function messages() {
        return [
           
            'records.firstname.required' => 'The first name is required.',
            'records.lastname.required' => 'The last name is required.',
            'records.suffix.in' => 'The suffix must be one of the following: jr, sr, I, II, III, IV, or V.',
            'records.civil_status.in' => 'The civil status must be one of the following: single, married, divorced, separated, widowed, or annulled.',
            'records.sex.in' => 'The sex must be either male or female.',
            'records.citizenship_type.required_with' => 'The citizenship type is required when citizenship is provided.',
            'records.country.required_if' => 'The country is required when citizenship is dual citizenship.',
            'records.mobile_number.regex' => 'The mobile number format is invalid. It should start with 09 and be followed by 9 digits.',
            'records.email.email' => 'The email must be a valid email address.',
            'records.email.required' => 'The email is required.',
            'records.email.unique' => 'The email is already taken.',
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

    public function save(bool $isNotify = true) 
{
    if (!$this->hasChanges()) {
        return $this->dispatch('alert', [
            'status' => 'info',
            'title' => 'Please be informed!',
            'isRemoveRowDT' => false,
            'showAlert' => true,
            'message' => 'Unable to save because no changes were made. Feel free to edit or update your information first before saving. Thank you!'
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
        $this->validate($this->rules($employee_no));
    } catch (ValidationException $e) {
        $this->setErrorActiveTabAccordions($e->validator->errors()->keys());
        throw $e;
    }

    if ($isNotify) {
        $this->dispatch('showConfirmation', [
            'title' => 'Are you sure to continue?',
            'message' => 'Yes, I am sure that all the information I have provided is accurate and true. This ensures that there will be no issues as we proceed.',
            'action' => 'save'
        ]);
        return;
    }

        Log::info('Employee Personal SAVE payload', [
            'employee_no' => $this->employee_no,
            'records' => $this->records,
        ]);

    DB::beginTransaction();



    try {
        $data = $this->records;
//dd($data);
        // ---- HANDLE PROFILE PHOTO ----
        if (!empty($data['profile']) && method_exists($data['profile'], 'store')) {
            //dd(123);    
                $folder = 'employees/tempo/' . $employee_no;
                $fileName = 'profile.' . $data['profile']->getClientOriginalExtension();
                $filePath = $data['profile']->storeAs($folder, $fileName, 'public');
                $data['profile'] = $filePath; // save path for DB
         } elseif (!isset($data['profile'])) {
            $data['profile'] = null;
        }

        Log::info('EmployeeUpdatePersonal DB payload', [
            'employee_no' => $employee_no,
            'data' => [
                'profile' => $data['profile'] ?? null,
                'firstname' => $data['firstname'] ?? null,
                'middlename' => $data['middlename'] ?? null,
                'lastname' => $data['lastname'] ?? null,
                'suffix' => $data['suffix'] ?? null,
                'birthday' => $data['birthday'] ?? null,
                'civil_status' => $data['civil_status'] ?? null,
                'sex' => $data['sex'] ?? null,
                'citizenship' => $data['citizenship'] ?? null,
                'citizenship_type' => $data['citizenship_type'] ?? null,
                'country' => $data['country'] ?? null,
                'present_address' => $data['present_address'] ?? null,
                'present_province' => $data['present_province'] ?? null,
                'present_city' => $data['present_city'] ?? null,
                'permanent_address' => $data['permanent_address'] ?? null,
                'permanent_province' => $data['permanent_province'] ?? null,
                'permanent_city' => $data['permanent_city'] ?? null,
                'mobile_number' => $data['mobile_number'] ?? null,
                'tel_no' => $data['tel_no'] ?? null,
                'email' => $data['email'] ?? null,
                'height' => $data['height'] ?? null,
                'weight' => $data['weight'] ?? null,
                'blood_type' => $data['blood_type'] ?? null,
                'gsis_no' => $data['gsis_no'] ?? null,
                'pagibig_no' => $data['pagibig_no'] ?? null,
                'philhealth_no' => $data['philhealth_no'] ?? null,
                'sss_no' => $data['sss_no'] ?? null,
                'tin_no' => $data['tin_no'] ?? null,
            ]
        ]);

        // ---- SAVE TO EMPLOYEE_UPDATE_PERSONAL ----
        EmployeeUpdatePersonal::updateOrCreate(
            ['employee_no' => $employee_no],
            [
                'employee_no' => $employee_no,
                'profile' => $data['profile'] ?? null,
                'firstname' => $data['firstname'] ?? null,
                'middlename' => $data['middlename'] ?? null,
                'lastname' => $data['lastname'] ?? null,
                'suffix' => $data['suffix'] ?? null,
                'birthday' => $data['birthday'] ?? null,
                'civil_status' => $data['civil_status'] ?? null,
                'sex' => $data['sex'] ?? null,
                'citizenship' => $data['citizenship'] ?? null,
                'citizenship_type' => $data['citizenship_type'] ?? null,
                'country' => $data['country'] ?? null,
                'present_address' => $data['present_address'] ?? null,
                'present_province' => $data['present_province'] ?? null,
                'present_city' => $data['present_city'] ?? null,
                'permanent_address' => $data['permanent_address'] ?? null,
                'permanent_province' => $data['permanent_province'] ?? null,
                'permanent_city' => $data['permanent_city'] ?? null,
                'mobile_number' => $data['mobile_number'] ?? null,
                'tel_no' => $data['tel_no'] ?? null,
                'email' => $data['email'] ?? null,
                'height' => $data['height'] ?? null,
                'weight' => $data['weight'] ?? null,
                'blood_type' => $data['blood_type'] ?? null,
                'gsis_no' => $data['gsis_no'] ?? null,
                'pagibig_no' => $data['pagibig_no'] ?? null,
                'philhealth_no' => $data['philhealth_no'] ?? null,
                'sss_no' => $data['sss_no'] ?? null,
                'tin_no' => $data['tin_no'] ?? null,
            ]
        );

        DB::commit();

        // ---- SUCCESS NOTIFICATION ----
        $this->dispatch('alert', [
            'status' => 'success',
            'title' => 'Success!', 
            'isRemoveRowDT' => false,
            'isReloadDT' => false,
            'message' => "Your profile is now pending for HR's approval. We'll notify you once it's approved. Thank you!"
        ]);

        $user = EmployeeAccount::with('personal')->find($this->employee_id);
        $personal = $user->personal ?? EmployeePersonal::where('employee_no', $this->employee_no)->first();
        $name = $personal ? trim($personal->firstname . ' ' . $personal->lastname) : '';
        $display = $name !== '' ? e($name) . ' (' . e($this->employee_no) . ')' : e($this->employee_no);
        $message = "Employee <strong>{$display}</strong> has submitted updated <strong>profile information</strong>.";
        $redirect = route('ess.approval-profile.show', ['employee_no' => $user->employee_no, 'form' => 'details']);
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


    public function render()
    {
        return view('livewire.employee.profile.personal');
    }
}
