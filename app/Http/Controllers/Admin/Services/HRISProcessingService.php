<?php

namespace App\Http\Controllers\Admin\Services;

use App\Helper\Generate;
use App\Http\Controllers\Controller;
use App\Mail\SendEmployeeAccount;
use App\Mail\SendExistingEmployeeAccount;
use App\Models\ApplicantUsers;
use App\Models\EmployeeAccount;
use App\Models\EmployeeChildren;
use App\Models\EmployeeCivilService;
use App\Models\EmployeeEducation;
use App\Models\EmployeeEmploymentHistory;
use App\Models\EmployeeInformation;
use App\Models\EmployeeOtherWorks;
use App\Models\EmployeeParents;
use App\Models\EmployeePersonal;
use App\Models\EmployeeSkillsHobbies;
use App\Models\EmployeeTrainings;
use App\Models\LeaveCredits;
use App\Models\LeaveType;
use App\Models\Positions;
use App\Models\Tranche;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Log;

use function PHPUnit\Framework\isEmpty;

class HRISProcessingService extends Controller
{

    public function save(bool $isFirstTime = false, string $employee_no, ?string $job_id = null, ?string $target, ?array $data = null) 
    {    

        $record = ApplicantUsers::with(['applied' => function ($query) use ($job_id) {
                $query->where('id', $job_id)->with('offer');
            }])
            ->whereHas('applied', function ($query) use ($job_id) {
                $query->where('id', $job_id);
            })
            ->first();
        
        if ($isFirstTime && $record) {

            $data = [
                'employee_information' => [
                    'salary' => $record->applied[0]->offer->salary
                ],
                'employee_account' => [
                    'applicant_id' => $record->id,
                    'email' => $record->email,
                    'firstname' => $record->firstname,
                    'lastname' => $record->lastname,
                ],
                'employee_personal' => [
                    'profile' => $record->image,
                    'firstname' => $record->firstname,
                    'middlename' => $record->middlename,
                    'lastname' => $record->lastname,
                    'birthday' => $record->birthday,
                    'civil_status' => $record->civil_status,
                    'sex' => $record->sex,
                    'mobile_number' => $record->phone_no,
                    'tel_no' => $record->tel_no,
                    'email' => $record->email,
                    'birth_certificate' => $record->birth_certificate,
                    'marriage_certificate' => $record->marriage_certificate,
                ],
            ];

            $record = $this->employee_information($employee_no, $data , true);            
            $this->employee_account($record->employee_no, $data['employee_account'], true);
            $this->employee_personal($record->employee_no, $data['employee_personal'], true);

        } else {

            switch ($target) {
                case 'information':
                    $data['employee_account']['email'] = $data['employee_personal']['email'];
                    $this->employee_information($employee_no, $data['employee_information'], false);
                    break;
                case 'account':
                    $this->employee_account($employee_no, $data['employee_account'], false);
                    break;
                case 'personal':
                    $this->employee_personal($employee_no, $data, false);
                    break;
                case 'family':
                    $this->employee_parents($employee_no, $data, false);
                    break;
                case 'children':
                    $this->employee_children($employee_no, $data);
                    break;
                case 'education':
                    $this->employee_education($employee_no, $data);
                    break;
                case 'employment_history':
                    $this->employee_employment_history($employee_no, $data);
                    break;
                case 'civil_service':
                    $this->employee_civil_service($employee_no, $data);
                    break;
                case 'trainings':
                    $this->employee_trainings($employee_no, $data);
                    break;
                case 'other_works':
                    $this->employee_others($employee_no, $data);
                    break;
                case 'skills':
                    $this->employee_skills($employee_no, $data);
                    break;
                default:
                    throw new \Exception("Unknown target: {$target}");
            }

        }
    }

    public function employee_information(string $employee_no, array $data, bool $isFirstTime = false)  {
        
        if ($isFirstTime) {

            $generate = new Generate;

            $employee_no = $generate->employee_no();
            
            $record = EmployeeInformation::create([
                'employee_no' => $employee_no,
                'bsd_no' => $data['employee_information']['biometrics_id'] ?? '',
                'date_hired' => Carbon::now()->format('Y-m-d'),
            ]);

            return $record;
        }

        $record = EmployeeInformation::where('employee_no', $employee_no)
            ->first();
\Log::info('Saving data', $data);
        $getdata = $this->handleSalary($data);

       
        $salary = $getdata['salary'];   
        $wtax = $getdata['w_tax'];

\Log::info('Saving employee information data', $data, ['salary' => $salary, 'w_tax' => $wtax]);
        if ($record) {
            $record->fill([
                'section_id' => $data['section_id'] ? $data['section_id'] : null,
                'position_id' => $data['position_id'],
                'job_completion' => $data['job_completion'],
                'bsd_no' => $data['biometrics_id'],
                'shift_id' => $data['shift_schedule'] ? $data['shift_schedule'] : null,
                'schedule_id' => $data['employee_schedule'] ? $data['employee_schedule'] : null,
                'date_resignation' => $data['date_resignation'] ?? null,
                'employment_type_id' => $data['type'],
                'status' => $data['status'],
                'salary_method' => $data['salary_method'],
                'salary' => $salary,
                'payroll_account_number' => $data['payroll_account_number'],
            ]);

            return $record->save();
        }

        return false;
    }

    public function employee_account(string $employee_no, array $data, bool $isFirstTime = false)
    {
        if ($isFirstTime) {
            $generate = new Generate;

            $record = EmployeeAccount::create([
                'employee_no'   => $employee_no,
                'applicant_id'  => $data['applicant_id'],
                'email_id'      => $generate->email($employee_no, $data['firstname'], $data['lastname']),
                'email'         => $data['email'],
            ]);

            $record->assignRole('employee');

            return $record;
        }

        $record = EmployeeAccount::with('personal')->where('employee_no', $employee_no)->first();

        if ($record) {
            if (!empty($data['personal_email'])) {
                $record->email = $data['personal_email'];
            }

            if (empty($record->email_id)) {
                $generate = new Generate;
                $firstname = $data['firstname'] ?? ($record->personal->firstname ?? null);
                $lastname = $data['lastname'] ?? ($record->personal->lastname ?? null);
                $record->email_id = $generate->email($employee_no, $firstname, $lastname);
            }

            if (!empty($data['password'])) {
                $record->password = Hash::make($data['password']);
            }

            $record->save();

            if (!$record->hasRole('employee')) {
                $record->assignRole('employee');
            }

            if (!empty($data['notify_user'])) {
                $firstname = $record->personal->firstname ?? '';
                $lastname  = $record->personal->lastname ?? '';
                $fullname  = trim("$firstname $lastname");

                $data['email']        = $record->email;
                $data['email_id']     = $record->email_id;
                $data['fullname']     = $fullname !== '' ? $fullname : 'Employee ' . $record->employee_no;
                $data['employee_no']  = strtoupper($record->employee_no);

                Mail::to($data['email'])->send(new SendExistingEmployeeAccount($data));
            }
        }

        return $record;
    }


    public function employee_personal(string $employee_no, array $data, bool $isFirstTime = false)
    {
        $path = 'documents/' . $employee_no;

        $birth_certificate = $this->uploadFile($employee_no, 'birth_certificate', $path, $data['birth_certificate'] ?? null);
        $marriage_certificate = $this->uploadFile($employee_no, 'marriage_certificate', $path, $data['marriage_certificate'] ?? null);

        $template = [
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
            'birth_certificate' => $birth_certificate,
            'marriage_certificate' => $marriage_certificate,
            'solo_parent' => isset($data['solo_parent']) && strtolower($data['solo_parent']) === 'yes',
            'present_address' => $data['present_address'] ?? null,
            'present_province' => $data['present_province'] ?? null,
            'present_city' => $data['present_city'] ?? null,
            'permanent_address' => $data['permanent_address'] ?? null,
            'permanent_province' => $data['permanent_province'] ?? null,
            'permanent_city' => $data['permanent_city'] ?? null,
            'mobile_number' => $data['mobile_number'] ?? null,
            'tel_no' => $data['tel_no'] ?? null,
            'height' => $data['height'] ?? null,
            'weight' => $data['weight'] ?? null,
            'blood_type' => $data['blood_type'] ?? null,
            'gsis_no' => $data['gsis_no'] ?? null,
            'pagibig_no' => $data['pagibig_no'] ?? null,
            'philhealth_no' => $data['philhealth_no'] ?? null,
            'sss_no' => $data['sss_no'] ?? null,
            'tin_no' => $data['tin_no'] ?? null,
        ];

        if ($isFirstTime) {
            EmployeePersonal::create($template);
        } else {
            $record = EmployeePersonal::where('employee_no', $employee_no)->first();

            if (!$record) {
                $info = EmployeeInformation::where('employee_no', $employee_no)->first();
                if ($info) {
                    $record = new EmployeePersonal();
                    $record->employee_no = $info->employee_no;
                }
            }

            if ($record) {
                $record->fill($template);
                $record->save();
            }
        }

        if (!empty($data['email'])) {
            $account = EmployeeAccount::firstOrNew(['employee_no' => $employee_no]);

            if (empty($account->email_id)) {
                $generate = new Generate;
                $firstname = $data['firstname'] ?? null;
                $lastname = $data['lastname'] ?? null;
                $account->email_id = $generate->email($employee_no, $firstname, $lastname);
            }

            $account->email = $data['email'];
            $account->save();
        }

        return true;
    }

    public function employee_parents(string $employee_no, ?array $data = null, bool $isFirstTime = false) {

        return EmployeeParents::updateOrCreate(
                [
                    'employee_no' => $employee_no
                ],
                [
                    'spouse_surname' => $data['spouse_surname'],
                    'spouse_firstname' => $data['spouse_firstname'],
                    'spouse_middlename' => $data['spouse_middlename'],
                    'spouse_suffix' => $data['spouse_suffix'],
                    'spouse_occupation' => $data['spouse_occupation'],
                    'spouse_business_name_employer' => $data['spouse_business_name_employer'],
                    'spouse_business_address' => $data['spouse_business_address'],
                    'spouse_contact_no' => $data['spouse_contact_no'],
                    'father_surname' => $data['father_surname'],
                    'father_firstname' => $data['father_firstname'],
                    'father_middlename' => $data['father_middlename'],
                    'father_suffix' => $data['father_suffix'],
                    'mother_surname' => $data['mother_surname'],
                    'mother_firstname' => $data['mother_firstname'],
                    'mother_middlename' => $data['mother_middlename'],
                ]);

    }

    public function employee_children(string $employee_no, array $data) {

        $path = 'documents/' . $employee_no;

        $existingIds = EmployeeChildren::where('employee_no', $employee_no)
            ->pluck('id')
            ->toArray();

        $dataIds = array_column($data, 'id');

        $missingIds = array_diff($existingIds, $dataIds);

        if (!empty($missingIds)) {
            EmployeeChildren::whereIn('id', $missingIds)->delete();
        }

        foreach ($data as $item) {
            if (isset($item['id'])) {
                if($item['documents'] instanceof TemporaryUploadedFile) {
                    $documents = $this->uploadFile($employee_no, 'documents', $path, $item['documents'] ?? null);
                    $record = EmployeeChildren::where('id', $item['id'])
                        ->where('employee_no', $employee_no)
                        ->first();
                    if ($record) {
                        $record->fill([
                            'firstname' => $item['firstname'],
                            'middlename' => $item['middlename'],
                            'lastname' => $item['lastname'],
                            'birthdate' => $item['birthdate'],
                            'documents' => $documents
                        ])->save();
                    }
                } else {
                    $record = EmployeeChildren::where('id', $item['id'])
                        ->where('employee_no', $employee_no)
                        ->first();
                    if ($record) {
                        $record->fill([
                            'firstname' => $item['firstname'],
                            'middlename' => $item['middlename'],
                            'lastname' => $item['lastname'],
                            'birthdate' => $item['birthdate'],
                        ])->save();
                    }
                }
            } else {
                $documents = $this->uploadFile($employee_no, 'documents', $path, $item['documents'] ?? null);
                EmployeeChildren::create([
                    'employee_no' => $employee_no,
                    'firstname' => $item['firstname'],
                    'middlename' => $item['middlename'],
                    'lastname' => $item['lastname'],
                    'birthdate' => $item['birthdate'],
                    'documents' => $documents
                ]);
            }
        }
    }

    public function employee_education(string $employee_no, array $data) {

        $path = 'documents/' . $employee_no;

        $existingIds = EmployeeEducation::where('employee_no', $employee_no)
            ->pluck('id')
            ->toArray();

        $dataIds = array_column($data, 'id');

        $missingIds = array_diff($existingIds, $dataIds);

        if (!empty($missingIds)) {
            EmployeeEducation::whereIn('id', $missingIds)->delete();
        }

        foreach ($data as $item) {
            if (isset($item['id'])) {
                if($item['documents'] instanceof TemporaryUploadedFile) {
                    $documents = $this->uploadFile($employee_no, 'documents', $path, $item['documents'] ?? null);
                    $record = EmployeeEducation::where('id', $item['id'])
                        ->where('employee_no', $employee_no)
                        ->first();
                    if ($record) {
                        $record->fill([
                            'employee_no' => $employee_no,
                            'level' => $item['level'],
                            'school_name' => $item['school_name'],
                            'course' => $item['course'],
                            'from_year' => $item['from_year'],
                            'to_year' => $item['to_year'],
                            'documents' => $documents
                        ])->save();
                    }
                } else {
                    $record = EmployeeEducation::where('id', $item['id'])
                        ->where('employee_no', $employee_no)
                        ->first();
                    if ($record) {
                        $record->fill([
                            'employee_no' => $employee_no,
                            'level' => $item['level'],
                            'school_name' => $item['school_name'],
                            'course' => $item['course'],
                            'from_year' => $item['from_year'],
                            'to_year' => $item['to_year'],
                        ])->save();
                    }
                }
            } else {
                $documents = $this->uploadFile($employee_no, 'documents', $path, $item['documents'] ?? null);
                EmployeeEducation::create([
                    'employee_no' => $employee_no,
                    'level' => $item['level'],
                    'school_name' => $item['school_name'],
                    'course' => $item['course'],
                    'from_year' => $item['from_year'],
                    'to_year' => $item['to_year'],
                    'documents' => $documents
                ]);
            }
        }

    }

    public function employee_employment_history(string $employee_no, array $data) {

        $path = 'documents/' . $employee_no;

        $existingIds = EmployeeEmploymentHistory::where('employee_no', $employee_no)
            ->pluck('id')
            ->toArray();

        $dataIds = array_column($data, 'id');

        $missingIds = array_diff($existingIds, $dataIds);

        if (!empty($missingIds)) {
            EmployeeEmploymentHistory::whereIn('id', $missingIds)->delete();
        }

        foreach ($data as $item) {
            if (isset($item['id'])) {
                if($item['documents'] instanceof TemporaryUploadedFile) {
                    $documents = $this->uploadFile($employee_no, 'documents', $path, $item['documents'] ?? null);
                    $record = EmployeeEmploymentHistory::where('id', $item['id'])
                        ->where('employee_no', $employee_no)
                        ->first();
                    if ($record) {
                        $record->fill([
                            'employee_no' => $employee_no,
                            'position' => $item['position'],
                            'department' => $item['department'],
                            'monthly_salary' => $item['monthly_salary'],
                            'employment_status' => $item['employment_status'],
                            'isGovernment' => $item['isGovernment'],
                            'from_year' => $item['from_year'],
                            'to_year' => $item['to_year'],
                            'documents' => $documents
                        ])->save();
                    }
                } else {
                    $record = EmployeeEmploymentHistory::where('id', $item['id'])
                        ->where('employee_no', $employee_no)
                        ->first();
                    if ($record) {
                        $record->fill([
                            'employee_no' => $employee_no,
                            'position' => $item['position'],
                            'department' => $item['department'],
                            'monthly_salary' => $item['monthly_salary'],
                            'employment_status' => $item['employment_status'],
                            'isGovernment' => $item['isGovernment'],
                            'from_year' => $item['from_year'],
                            'to_year' => $item['to_year'],
                        ])->save();
                    }
                }
            } else {
                $documents = $this->uploadFile($employee_no, 'documents', $path, $item['documents'] ?? null);
                EmployeeEmploymentHistory::create([
                    'employee_no' => $employee_no,
                    'position' => $item['position'],
                    'department' => $item['department'],
                    'monthly_salary' => $item['monthly_salary'],
                    'employment_status' => $item['employment_status'],
                    'isGovernment' => $item['isGovernment'],
                    'from_year' => $item['from_year'],
                    'to_year' => $item['to_year'],
                    'documents' => $documents
                ]);
            }
        }

    }

    public function employee_civil_service(string $employee_no, array $data) {


        $path = 'documents/' . $employee_no;

        $existingIds = EmployeeCivilService::where('employee_no', $employee_no)
            ->pluck('id')
            ->toArray();

        $dataIds = array_column($data, 'id');

        $missingIds = array_diff($existingIds, $dataIds);

        if (!empty($missingIds)) {
            EmployeeCivilService::whereIn('id', $missingIds)->delete();
        }

        foreach ($data as $item) {
            if (isset($item['id'])) {
                if($item['documents'] instanceof TemporaryUploadedFile) {
                    $documents = $this->uploadFile($employee_no, 'documents', $path, $item['documents'] ?? null);
                    $record = EmployeeCivilService::where('id', $item['id'])
                        ->where('employee_no', $employee_no)
                        ->first();
                    if ($record) {
                        $record->fill([
                            'employee_no' => $employee_no,
                            'certification' => $item['certification'],
                            'rating' => $item['rating'],
                            'date_exam' => $item['date_exam'],
                            'place_exam' => $item['place_exam'],
                            'license_no' => $item['license_no'],
                            'date_validity' => $item['date_validity'],
                            'documents' => $documents
                        ])->save();
                    }
                } else {
                    $record = EmployeeCivilService::where('id', $item['id'])
                        ->where('employee_no', $employee_no)
                        ->first();
                    if ($record) {
                        $record->fill([
                            'employee_no' => $employee_no,
                            'certification' => $item['certification'],
                            'rating' => $item['rating'],
                            'date_exam' => $item['date_exam'],
                            'place_exam' => $item['place_exam'],
                            'license_no' => $item['license_no'],
                            'date_validity' => $item['date_validity'],
                        ])->save();
                    }
                }
            } else {
                $documents = $this->uploadFile($employee_no, 'documents', $path, $item['documents'] ?? null);
                EmployeeCivilService::create([
                    'employee_no' => $employee_no,
                    'certification' => $item['certification'],
                    'rating' => $item['rating'],
                    'date_exam' => $item['date_exam'],
                    'place_exam' => $item['place_exam'],
                    'license_no' => $item['license_no'],
                    'date_validity' => $item['date_validity'],
                    'documents' => $documents
                ]);
            }
        }
    }

    public function employee_trainings(string $employee_no, array $data) {


        $path = 'documents/' . $employee_no;

        $existingIds = EmployeeTrainings::where('employee_no', $employee_no)
            ->pluck('id')
            ->toArray();

        $dataIds = array_column($data, 'id');

        $missingIds = array_diff($existingIds, $dataIds);

        if (!empty($missingIds)) {
            EmployeeTrainings::whereIn('id', $missingIds)->delete();
        }

        foreach ($data as $item) {
            if (isset($item['id'])) {
                if($item['documents'] instanceof TemporaryUploadedFile) {
                    $documents = $this->uploadFile($employee_no, 'documents', $path, $item['documents'] ?? null);
                    $record = EmployeeTrainings::where('id', $item['id'])
                        ->where('employee_no', $employee_no)
                        ->first();
                    if ($record) {
                        $record->fill([
                            'employee_no' => $employee_no,
                            'type' => $item['type'],
                            'name' => $item['name'],
                            'date_from' => $item['date_from'],
                            'date_to' => $item['date_to'],
                            'consumed_hours' => $item['consumed_hours'],
                            'sponsored_by' => $item['sponsored_by'],
                            'documents' => $documents
                        ])->save();
                    }
                } else {
                    $record = EmployeeTrainings::where('id', $item['id'])
                        ->where('employee_no', $employee_no)
                        ->first();
                    if ($record) {
                        $record->fill([
                            'employee_no' => $employee_no,
                            'type' => $item['type'],
                            'name' => $item['name'],
                            'date_from' => $item['date_from'],
                            'date_to' => $item['date_to'],
                            'consumed_hours' => $item['consumed_hours'],
                            'sponsored_by' => $item['sponsored_by'],
                        ])->save();
                    }
                }
            } else {
                $documents = $this->uploadFile($employee_no, 'documents', $path, $item['documents'] ?? null);
                EmployeeTrainings::create([
                    'employee_no' => $employee_no,
                    'type' => $item['type'],
                    'name' => $item['name'],
                    'date_from' => $item['date_from'],
                    'date_to' => $item['date_to'],
                    'consumed_hours' => $item['consumed_hours'],
                    'sponsored_by' => $item['sponsored_by'],
                    'documents' => $documents
                ]);
            }
        }
    }

    public function employee_others(string $employee_no, array $data) {

        $path = 'documents/' . $employee_no;

        $existingIds = EmployeeOtherWorks::where('employee_no', $employee_no)
            ->pluck('id')
            ->toArray();

        $dataIds = array_column($data, 'id');

        $missingIds = array_diff($existingIds, $dataIds);

        if (!empty($missingIds)) {
            EmployeeOtherWorks::whereIn('id', $missingIds)->delete();
        }

        foreach ($data as $item) {
            if (isset($item['id'])) {
                if($item['documents'] instanceof TemporaryUploadedFile) {
                    $documents = $this->uploadFile($employee_no, 'documents', $path, $item['documents'] ?? null);
                    $record = EmployeeOtherWorks::where('id', $item['id'])
                        ->where('employee_no', $employee_no)
                        ->first();
                    if ($record) {
                        $record->fill([
                            'employee_no' => $employee_no,
                            'organization' => $item['organization'],
                            'date_from' => $item['date_from'],
                            'date_to' => $item['date_to'],
                            'consumed_hours' => $item['consumed_hours'],
                            'position' => $item['position'],
                            'documents' => $documents
                        ])->save();
                    }
                } else {
                    $record = EmployeeOtherWorks::where('id', $item['id'])
                        ->where('employee_no', $employee_no)
                        ->first();
                    if ($record) {
                        $record->fill([
                            'employee_no' => $employee_no,
                            'organization' => $item['organization'],
                            'date_from' => $item['date_from'],
                            'date_to' => $item['date_to'],
                            'consumed_hours' => $item['consumed_hours'],
                            'position' => $item['position'],
                        ])->save();
                    }
                }
            } else {
                $documents = $this->uploadFile($employee_no, 'documents', $path, $item['documents'] ?? null);
                EmployeeOtherWorks::create([
                    'employee_no' => $employee_no,
                    'organization' => $item['organization'],
                    'date_from' => $item['date_from'],
                    'date_to' => $item['date_to'],
                    'consumed_hours' => $item['consumed_hours'],
                    'position' => $item['position'],
                    'documents' => $documents
                ]);
            }
        }
    }

    public function employee_skills(string $employee_no, array $data) {

        $path = 'documents/' . $employee_no;

        $existingIds = EmployeeSkillsHobbies::where('employee_no', $employee_no)
            ->pluck('id')
            ->toArray();

        $dataIds = array_column($data, 'id');

        $missingIds = array_diff($existingIds, $dataIds);

        if (!empty($missingIds)) {
            EmployeeSkillsHobbies::whereIn('id', $missingIds)->delete();
        }

        foreach ($data as $item) {
            if (isset($item['id'])) {
                if($item['documents'] instanceof TemporaryUploadedFile) {
                    $documents = $this->uploadFile($employee_no, 'documents', $path, $item['documents'] ?? null);
                    $record = EmployeeSkillsHobbies::where('id', $item['id'])
                        ->where('employee_no', $employee_no)
                        ->first();
                    if ($record) {
                        $record->fill([
                            'employee_no' => $employee_no,
                            'name' => $item['name'],
                            'recognition' => $item['recognition'],
                            'organization' => $item['organization'],
                            'documents' => $documents
                        ])->save();
                    }
                } else {
                    $record = EmployeeSkillsHobbies::where('id', $item['id'])
                        ->where('employee_no', $employee_no)
                        ->first();
                    if ($record) {
                        $record->fill([
                            'employee_no' => $employee_no,
                            'name' => $item['name'],
                            'recognition' => $item['recognition'],
                            'organization' => $item['organization'],
                        ])->save();
                    }
                }
            } else {
                $documents = $this->uploadFile($employee_no, 'documents', $path, $item['documents'] ?? null);
                EmployeeSkillsHobbies::create([
                    'employee_no' => $employee_no,
                    'name' => $item['name'],
                    'recognition' => $item['recognition'],
                    'organization' => $item['organization'],
                    'documents' => $documents
                ]);
            }
        }

    }



    public function handleSalary(array $data)
{
    \Log::info('Handling salary for data', $data);

    // Default values (IMPORTANT)
    $data['salary'] = $data['salary'] ?? 0;
    $data['w_tax']  = $data['w_tax']  ?? 0;

    $eligible    = $data['type'] ?? null;
    $position_id = $data['position_id'] ?? null;
    $step_id     = $data['step_id'] ?? null;

    if (in_array($eligible, [1, 2]) && $position_id && $step_id) {

        $salaryGrade = Positions::where('id', $position_id)->value('salary_grade');

        if ($salaryGrade) {

            $stepColumn     = "step_{$step_id}";
            $stepColumnTax  = "step_{$step_id}_wtax";

            $activeTranche = Tranche::with(['items' => function ($query) use ($salaryGrade, $stepColumn, $stepColumnTax) {
                    $query->where('salary_grade', $salaryGrade)
                          ->select('id', 'tranche_id', 'salary_grade', $stepColumn, $stepColumnTax);
                }])
                ->where('eligible', $eligible)
                ->first();

            if ($activeTranche && $activeTranche->items->isNotEmpty()) {
                $item = $activeTranche->items->first();

                $data['salary'] = $item->$stepColumn ?? 0;
                $data['w_tax']  = $item->$stepColumnTax ?? 0;
            }
        }
    }

    // ✅ ALWAYS return data
    return $data;
}


    private function uploadFile($employee_no, $identifier, $path, $file)
    {
        if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            $record = EmployeePersonal::with([
                'children', 'employment_history', 'civil_service', 'trainings', 'others', 'skills'
            ])->where('employee_no', $employee_no)->first();
        
            if ($record) {
                if (!empty($record->$identifier)) {
                    Storage::disk('public')->delete("$path/{$record->$identifier}");
                }
        
                if ($identifier === 'documents') {
                    foreach (['children', 'employment_history', 'civil_service', 'trainings', 'others', 'skills'] as $relation) {
                        if ($record->$relation && !empty($record->$relation->$identifier)) {
                            Storage::disk('public')->delete("$path/{$record->$relation->$identifier}");
                        }
                    }
                }
            }
            
            $filename = uniqid(time()) . '.' . $file->getClientOriginalExtension();
            $file->storeAs($path, $filename, 'public');
        
            return $filename;
        }
        
        $record = EmployeePersonal::where('employee_no', $employee_no)->first();
        return $record->$identifier ?? null;
        
    }
    

}