<?php

namespace App\Livewire\Admin\Job\Applicant;

use App\Helper\Generate;
use App\Http\Controllers\Admin\Services\HRISProcessingService;
use App\Mail\SendEmployeeAccount;
use App\Mail\SendJobOffer;
use App\Models\EmployeeAccount;
use App\Models\Interview;
use App\Models\JobApplicants;
use App\Models\JobApplicantsInterview;
use App\Models\JobApplicantsOffer;
use App\Models\JobApplicantsRequirements;
use App\Models\JobRequirements;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{

    use WithPagination;
    use WithFileUploads;

    public $user_id;
    public $status;
    public $applicant_information;
    public $selected_id;
    public $interview;
    public $selected_interview = [];
    public $applicant_responses;
    public $job_offer = [];
    public $requirements;
    public $selected_requirements = [];

    protected $paginationTheme = 'bootstrap';
    public $entries = 10;
    public $search = '';

    protected $listeners = [
        'ckeditor', 
        'set_placement', 
        'send_offer', 
        'set_onboarding',
        'set_hired',
        'reject',
        'delete'
    ];

    public function ckeditor($data) {
        $this->job_offer['body'] = $data;
    }

    # view applicants

    public function view_applicant(int $id) {

        $record = JobApplicants::with(['applicant.skills.skills', 'job'])
            ->where('id', $id);

        if(!$record->exists()) {
            
        }

        $this->applicant_information = $record->first()->applicant;
        $this->dispatch('showModal', [
            'modal' => 'applicant_info'
        ]);

    }

    # view responses from the interview
    public function view_responses(int $id) {
        $records = JobApplicants::with([
            'interview.details',
            'interview.items.options',
            'interview.items.answers' => function ($query) use ($id) {
                $jobApplicantUserId = JobApplicants::where('id', $id)->value('user_id');
                $query->where('user_id', $jobApplicantUserId);
            }
        ])
        ->where('id', $id)
        ->first();    
        $this->applicant_responses = $records;
        $this->dispatch('showModal', [
            'modal' => 'applicant_responses'
        ]);
    }

    public function download_requirement(int $id) {
        
        $record = JobApplicantsRequirements::with('applicant.applicant', 'applicant.job')
            ->where('id', $id)
            ->first();

        if (is_null($record)) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops!',
                'message' => 'Requirement does not exist'
            ]);
        }

        // Define folder structure based on nested applicant details
        $folder = strtolower(
            $record->applicant['applicant']['firstname'] . '_' . 
            $record->applicant['applicant']['lastname'] . '_' . 
            $record->applicant['applicant']['id']
        );

        // Define the file path based on the job slug and requirement attachment
        $path = 'users/applicant/' . $folder . '/' . $record->applicant['job']['slug'] . '/requirements/' . $record->attachment;

        if (!Storage::disk('public')->exists($path)) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops!',
                'message' => 'Requirement file does not exist'
            ]);
        }

        return response()->download(Storage::disk('public')->path($path));

        
    }

    public function set_action(string $action, int $id) {

        if (Gate::denies('write applicants')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!', 
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        if($this->validate_action($action)) {
            $this->selected_id = $id;
            switch($action) {
                case 'process':
                    $this->process($id);
                    break;
                case 'rejected':
                    $this->reject($id);
                    break;
                case 'delete':
                    $this->delete($id);
                    break;
                case 'navigate':
                    $this->navigate($id);
                    break;
                default: 
                    return redirect()->route('job.applicants.index');
            }
        }
    }

    public function process(int $id) {
        $model = JobApplicants::find($id);
        $current_status = $model->status;

        $this->selected_id = $id;  

        if($current_status == 'pending') {
            $this->set_interview();
        } elseif($current_status == 'interview') {
            $this->set_placement(true);
        } elseif($current_status == 'placement') {
            $this->set_onboarding(true);
        } elseif($current_status == 'onboarding') {
            $this->set_hired(true);
        } else {
            dd('why are you here?');
        }
    }

    # set requirements checklist
    public function set_checklist($isSaved, int $id = null) {
        if(!$isSaved) {

            $records = JobRequirements::all();
            $submittedRequirements = JobApplicantsRequirements::where('job_applicants_id', $id)
                ->get();

            $this->requirements = $records;

            $this->selected_id = $id;
            $this->selected_requirements = $submittedRequirements;

            return $this->dispatch('showModal', [
                'modal' => 'applicant_requirements',
                'plugins' => [
                    'ckeditor'
                ]
            ]);
        }
            
        JobApplicantsRequirements::where('job_applicants_id', $this->selected_id)
            ->delete();


        foreach ($this->selected_requirements as $key => $item) {
            
            if($item === true) {

                $record = JobRequirements::find($key);     

                if ($record) {
                    JobApplicantsRequirements::insert([
                        'job_applicants_id' => $this->selected_id,
                        'requirement_id' => $record->id,
                    ]);
                } else {
                    return $this->dispatch('alert', [
                        'status' => 'error',
                        'title' => 'Oops', 
                        'isRemoveRowDT' => false,
                        'showAlert' => true,
                        'message' => 'The selected requirement does not exist.'
                    ]);
                }
            }
            
        }
        
        return $this->dispatch('alert', [
            'id' => $this->selected_id,
            'status' => 'success',
            'title' => 'Success!', 
            'isRemoveRowDT' => false,
            'isReloadDT' => false,
            'message' => 'Requirements were marked successfully.' 
        ]);

    }

    # set an interview / test to applicant
    public function set_interview(bool $isNotify = true) {
        
        if($isNotify) {
            $this->interview = Interview::get();
            $this->interview = $this->interview->isNotEmpty() ? $this->interview : null;
            $this->dispatch('showModal', [
                'modal' => 'select_interview'
            ]);
        } else {

            $model = JobApplicants::find($this->selected_id);
            
            if(empty($this->selected_interview)) {
                return $this->dispatch('alert', [
                    'showAlert' => true,
                    'status' => 'error',
                    'title' => 'Oops', 
                    'isRemoveRowDT' => false,
                    'message' => 'Please select atleast one interview'
                ]);
            }
            
            foreach($this->selected_interview as $key => $item) {
                $record = Interview::where('id', $key);
                if($record->exists()) {
                    JobApplicantsInterview::insert([
                        'job_applicants_id' => $this->selected_id,
                        'job_interview_id' => $key
                    ]);
                } else {
                    return $this->dispatch('alert', [
                        'showAlert' => true,
                        'status' => 'error',
                        'title' => 'Oops', 
                        'isRemoveRowDT' => false,
                        'message' => 'The selected interview does not exists'
                    ]);
                }
            }

            $model->update([
                'status' => 'interview',
            ]);

            $this->dispatch('alert', [
                'id' => $this->selected_id,
                'showAlert' => true,
                'status' => 'success',
                'title' => 'Success!', 
                'isRemoveRowDT' => true,
                'message' => 'Application has been moved to interview.' 
            ]);

        }
    }

    # set application to placement
    public function set_placement(bool $isNotify = true) {
        if($isNotify) {
            $title = 'Are you sure to continue?';
            $message = 'The action cannot be undone or reverted!';
            $action = 'set_placement';
            $this->notify($title, $message, $action);
        } else {

            $model = JobApplicants::find($this->selected_id);
            $model->update([
                'status' => 'placement',
            ]);
            return $this->dispatch('alert', [
                'id' => $this->selected_id,
                'showAlert' => true,
                'status' => 'success',
                'title' => 'Yey!', 
                'message' => 'Application has been moved to placement.',
                'isRemoveRowDT' => true,
            ]);
        }
    }

    # set application to onboarding
    public function set_onboarding(bool $isNotify = true) {

        $model = JobApplicants::with('offer')->find($this->selected_id);

        if(is_null($model->offer)) {
            return $this->dispatch('alert', [
                'id' => $this->selected_id,
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops!', 
                'message' => 'Unable to set to onboarding. No job offer has been sent to the applicant. Please send one first.',
                'isRemoveRowDT' => false,
            ]);
        }

        if($isNotify) {
            $title = 'Are you sure to continue?';
            $message = 'The action cannot be undone or reverted!';
            $action = 'set_onboarding';
            $this->notify($title, $message, $action);
        } else {

            $model->update([
                'status' => 'onboarding',
            ]);
    
            $this->dispatch('alert', [
                'id' => $this->selected_id,
                'showAlert' => true,
                'status' => 'success',
                'title' => 'Success!', 
                'isRemoveRowDT' => true,
                'message' => 'Application has been moved to onboarding.' 
            ]);
        }
    
    }

    # set application to hired
    public function set_hired(bool $isNotify = true) {

        $model = JobApplicants::with('job', 'requirements')->find($this->selected_id);

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'The action cannot be undone or reverted!';
            $action = 'set_hired';

            $this->notify($title, $message, $action);

        } else {

            if($this->create_employee()) {


                $model->job->slots -= 1;
                $model->job->save();

                $model->update([
                    'status' => 'hired',
                ]);

                $this->dispatch('alert', [
                    'id' => $this->selected_id,
                    'showAlert' => true,
                    'status' => 'success',
                    'title' => 'Success!', 
                    'isRemoveRowDT' => true,
                    'message' => 'Applicant has been hired!' 
                ]);
            }
        }
    
    }

    # send offer in under placement
    public function send_offer($isSaved, ? int $id = null) {

        if(!$isSaved) {

            $this->selected_id = $id;  
            
            $records = JobApplicants::with('applicant', 'job.employment_type', 'offer')->find($id);

            $this->job_offer = [
                'min_salary' => $records->job->min_salary,
                'max_salary' => $records->job->max_salary,
            ];

            $data = [
                'fullname' => $records->applicant->firstname . ' ' . $records->applicant->lastname,
                'slug' => $records->job->slug,
                'position' => $records->job->position,
                'company_name' => $records->job->company_name,
                'location' => $records->job->location,
                'setup' => $records->job->setup,
                'type' => $records->job->employment_type->name ?? 'Unknown',
            ];

            $this->job_offer['subject'] = 'Job Offer for ' . ucwords($data['position']) . ' Position at ' . $data['company_name'];

            $this->job_offer['body'] = '
                <p>Hello <b>' . ucwords($data["fullname"]) . ',</b></p>
                <p>
                    We are pleased to extend an offer for you to join <strong>' . ucwords($data["company_name"]) . '</strong> as our new <strong>' . $data["position"] . '</strong>. 
                    Based on your impressive skills, experience, and interview performance, we are confident that you will make a valuable addition to our team.
                </p>

                <h4><b>Job Details:</b></h4>
                <ul>
                    <li><strong>Company</strong>: ' . ucwords($data["company_name"]) . '</li>
                    <li><strong>Location</strong>: ' . ucwords($data["location"]) . '</li>
                    <li><strong>Position</strong>: ' . ucwords($data["position"]) . '</li>
                    <li><strong>Work Setup</strong>: ' . ucwords($data["setup"]) . '</li>
                    <li><strong>Employment Type</strong>: ' . ucwords(str_replace("-", " ", $data["type"])) . '</li>
                    <li><strong>Job Applied</strong>: <a href="' . route('home.view-job', ['slug' => $data['slug']]) . '">'.route('home.view-job', ['slug' => $data['slug']]).'</a></li>
                </ul>

                <h4><b>Offer Details:</b></h4>
                <ul>
                    <li>Please refer to the attached job offer for details regarding salary and benefits.</li>
                    <li>A separate email will be sent to you with your employee account information once your hiring process is complete.</li>
                </ul>

                <p>We’re excited to welcome you to a supportive, growth-oriented environment where you will have the opportunity to make a meaningful impact on our projects and culture. We believe your expertise will be instrumental in achieving our team’s goals.</p>

                <h4><b>Next Steps:</b></h4>
                <p>Please review the attached document, which includes the full terms and conditions of the offer. To confirm your acceptance, simply sign the attached offer letter and return it by <strong>uploading it to our website under profile and placement tab</strong>.</p>

                <p>If you have any questions regarding the offer or the details of your employment, feel free to reach out to support@' . env("COMPANY_DOMAIN") . '.</p>

                <p>Congratulations again, ' . ucwords($data["fullname"]) . '! We look forward to the opportunity to work together and are excited about the contributions you’ll bring to our team.</p>
                <p style="margin-bottom: 0px">Warm regards,
                    <br>
                    <b>HR Department</b>
                </p>
            ';

            return $this->dispatch('showModal', [
                'modal' => 'applicant_job_offer',
                'plugins' => [
                    'ckeditor'
                ]
            ]);
        }

        
        DB::beginTransaction();

        try {
                
            $records = JobApplicants::with('applicant', 'job', 'offer')->find($this->selected_id);
    
            $rules = [
                'job_offer.subject' => 'required',
                'job_offer.body' => 'required',
                'job_offer.attachment' => 'required|file|mimes:docx,doc,pdf',
                'job_offer.starting_date' => 'required|after_or_equal:today',
                'job_offer.salary' => 'required|numeric|between:' . $this->job_offer['min_salary'] . ',' . $this->job_offer['max_salary'],
            ];
            
            $messages = [
                'job_offer.subject.required' => 'The subject is required.',
                'job_offer.body.required' => 'The body is required.',
                'job_offer.attachment.required' => 'Please attach a document.',
                'job_offer.attachment.file' => 'The attachment must be a file.',
                'job_offer.attachment.mimes' => 'The attachment must be a file of type: docx, doc, or pdf.',
                'job_offer.starting_date.required' => 'The starting date is required.',
                'job_offer.starting_date.after_or_equal' => 'The starting date must be today or a future date.',
                'job_offer.salary.required' => 'The salary is required.',
                'job_offer.salary.numeric' => 'The salary must be a numeric value.',
                'job_offer.salary.between' => 'The salary must be between ' . $this->job_offer['min_salary'] . ' and ' . $this->job_offer['max_salary'] . '.',
            ];
    
            $this->validate($rules, $messages);

            $folder = strtolower($records->applicant->firstname . '_' . $records->applicant->lastname . '_' . $records->applicant->id);
    
            $attachment = $this->job_offer['attachment'];
            $extension = $attachment->getClientOriginalExtension();
            $filename = 'job_offer_' . str_replace(' ', '_', $records->job->position 
                . '_' . time()) 
                . '.' . $extension;
    
            $attachment->storeAs('users/applicant/' . $folder . '/' . $records->job->slug . '/offers', strtolower($filename), 'public');
    
            JobApplicantsOffer::insert([
                'job_applicants_id' => $this->selected_id,
                'subject' => $this->job_offer['subject'],
                'body' => $this->job_offer['body'],
                'attachment' => strtolower($filename),
                'starting_date' => $this->job_offer['starting_date'],
                'salary' => $this->job_offer['salary']
            ]); 
    
            $path = Storage::disk('public')->path('users/applicant/' . $folder. '/' . $records->job->slug .'/offers/' . strtolower($filename));
    
            $data = [
                'subject' => $this->job_offer['subject'],
                'body' => $this->job_offer['body'],
                'attachment' => $path,
                'position' => $records->job->position,
                'company_name' => $records->job->company_name
            ];
    
            Mail::to($records->applicant->email)
                ->send(new SendJobOffer($data));
    
            DB::commit();

            return $this->dispatch('alert', [
                'id' => $this->selected_id,
                'showAlert' => true,
                'status' => 'success',
                'title' => 'Yey!', 
                'message' => 'Job offer has been sent to the applicant.',
                'isRemoveRowDT' => false,
                'isReloadDT' => true,
            ]);

        } catch (ValidationException $e) {
            $validationErrors = $e->validator->errors()->all(); 

            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops!',
                'message' => ($validationErrors[0] ?? 'Unknown validation error'),
                'isRemoveRowDT' => false,
                'isReloadDT' => true,
            ]);

        } catch (\Exception $e) {
            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops!',
                'message' => 'Error: ' . $e->getMessage(),
                'isRemoveRowDT' => false,
                'isReloadDT' => true,
            ]);
        }
        

    }

    public function download_offer(int $id) {

        $record = JobApplicants::with('applicant', 'job', 'offer')
            ->where('id', $id)
            ->first();


        if(is_null($record->offer) || empty($record->offer->signed_attachment)) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops!',
                'message' => 'Job offer does not exists'
            ]);
        }

        $folder = strtolower($record->applicant->firstname . '_' . $record->applicant->lastname . '_' . $record->applicant->id);
        $path = 'users/applicant/'.$folder. '/' . $record->job->slug .'/offers/' . $record->offer->signed_attachment;


        if(!Storage::disk('public')->exists($path)) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops!',
                'message' => 'Job offer does not exists'
            ]);
        } 

        return response()->download(Storage::disk('public')->path($path));

    }

    public function notify(string $title, string $message, string $action) {
        $this->dispatch('showConfirmation', [
            'title' => $title,
            'message' => $message,
            'action' => $action
        ]);
    }

    public function reject(bool $isNotify = true) {

        if($isNotify) {
            $title = 'Are you sure to continue?';
            $message = 'The action cannot be undone or reverted!';
            $action = 'reject';
            $this->notify($title, $message, $action);
        } else {

            $record = JobApplicants::find($this->selected_id);

            if(!$record) {
                return $this->dispatch('alert', [
                    'id' => $this->selected_id,
                    'showAlert' => true,
                    'status' => 'error',
                    'title' => 'Oops!', 
                    'isRemoveRowDT' => false,
                    'message' => 'Job application does not exists' 
                ]);
            }

            $record->update([
                'status' => 'rejected'
            ]);
         
            
            $this->dispatch('alert', [
                'id' => $record->id,
                'showAlert' => true,
                'status' => 'success',
                'title' => 'Success', 
                'isRemoveRowDT' => true,
                'message' => 'Application has been rejected'
            ]);

        }

    }

    public function delete(bool $isNotify = true) {

        if($isNotify) {
            $title = 'Are you sure to continue?';
            $message = 'The action cannot be undone or reverted!';
            $action = 'delete';
            $this->notify($title, $message, $action);
        } else {

            $record = JobApplicants::where('id', $this->selected_id);

            if(!$record) {
                return $this->dispatch('alert', [
                    'id' => $this->selected_id,
                    'showAlert' => true,
                    'status' => 'error',
                    'title' => 'Oops!', 
                    'isRemoveRowDT' => false,
                    'message' => 'Job application does not exists' 
                ]);
            }

            $record->delete();
            
            $this->dispatch('alert', [
                'id' => $this->selected_id,
                'showAlert' => true,
                'status' => 'success',
                'title' => 'Success!', 
                'isRemoveRowDT' => true,
                'message' => 'Application has been deleted!.' 
            ]);

        }
    
    }

    public function navigate(string $id) {

        $employee = EmployeeAccount::where('applicant_id', $id)->first();

        if(!$employee) {
            return redirect()->route('job.applicants.index', ['status' => 'hired']);
        }

        $employee_no = $employee->employee_no;

        return redirect()->route('hris.show', ['employee_no' => $employee_no]);

    }
    
    public function validate_action(string $action) {
        $allowed = ['process', 'rejected', 'delete', 'navigate'];
        if(in_array($action, $allowed)) {
            return true;
        }
        return false;
    }

    public function create_employee() {

        $process = new HRISProcessingService();
        
        DB::beginTransaction();
        
        $record = JobApplicants::with('applicant', 'job')->find($this->selected_id);

        if(!$record) {

            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops!', 
                'isRemoveRowDT' => true,
                'message' => 'Error: ' . 'Job applicant id does not exists'
            ]);
        }

        try {

            $process->save(true, $record->user_id, $this->selected_id);

            $account = EmployeeAccount::where('applicant_id', $record->applicant->id)
                ->first();

            $generate = new Generate;
            $password = $generate->password();

            $account->password = $password['hashed'];
            $account->save();
            
            $data = [
                'is_newly_hired' => true,
                'employee_no' => null,
                'firstname' => $record->applicant->firstname ?? '',
                'fullname' => $record->applicant->firstname . ' ' . $record->applicant->lastname,
                'slug' => $record->job->slug,
                'position' => $record->job->position,
                'company_name' => $record->job->company_name,
                'location' => $record->job->location,
                'setup' => $record->job->setup,
                'type' => $record->job->type,
                'starting_date' => $record->offer->starting_date,
                'salary' => $record->offer->salary, 
                'email' => $account->email,
                'password' => $password['plain'],
            ];

            Mail::to($record->applicant->email)
                ->send(new SendEmployeeAccount($data));

            DB::commit();
            
            return true;
        
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops!', 
                'isRemoveRowDT' => false,
                'message' => 'Error: ' . $e->getMessage() 
            ]);
        }

    }

    public function updated($propertyName) {

        if ($propertyName === 'job_offer.attachment') {
            
            if (isset($this->job_offer['attachment'])) {
                
                $file = $this->job_offer['attachment'];

                if ($file instanceof \Illuminate\Http\UploadedFile) {

                    $extension = strtolower($file->getClientOriginalExtension());

                    if (in_array($extension, ['pdf'])) {
                        $filename = $file->store('public/temp'); 
                        $url = Storage::url($filename); 
    
                        return $this->job_offer['attachment_preview'] = $url;
                    } 

                    $this->dispatch('alert', [
                        'showAlert' => true,
                        'status' => 'error',
                        'title' => 'Oops!', 
                        'isRemoveRowDT' => false,
                        'message' => 'Attachment must be PDF.'
                    ]);
                  

                } else {    
                    $this->dispatch('alert', [
                        'showAlert' => true,
                        'status' => 'error',
                        'title' => 'Oops!', 
                        'isRemoveRowDT' => false,
                        'message' => 'Error: Invalid File'
                    ]);
                }
            }
        }
        
    }
    
    public function render()
    {

        $model = JobApplicants::with(['applicant', 'job', 'offer', 'requirements'])
            ->where('status', $this->status);

        if ($this->search) {

            $this->resetPage(); 

            $records = $model->where('applicant_no', 'like', '%' . $this->search . '%')
                ->orWhereHas('job', function($query) {
                    $query->where('company_name', 'like', '%' . $this->search . '%')
                        ->orWhere('position', 'like', '%' . $this->search . '%');
                });
        }

        $records = $model->latest()->paginate($this->entries);

        return view('livewire.admin.job.applicant.index', [
            'records' => $records
        ]);
    }
}
