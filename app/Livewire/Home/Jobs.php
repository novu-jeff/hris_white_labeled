<?php

namespace App\Livewire\Home;

use App\Models\JobApplicants;
use App\Models\JobPosts;
use App\Models\SavedJobs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithPagination;

class Jobs extends Component
{
    use WithPagination;

    public $user_id;
    public $record_info;
    public $applied_job_ids = [];
    public $saved_job_ids = [];
    public $search_query;
    public $search_term;
    public $search_result = [];
    public $isEmptySearch = false;

    protected $paginationTheme = 'bootstrap';
    public $entries = 5;
    public $search = '';

    public function mount()
    {
        $user = Auth::guard('applicant')->user();
        $this->user_id = $user->id ?? null;

        if ($user) {
            $this->applied_job_ids = $user->applied->pluck('job_id')->toArray();
            $this->saved_job_ids = $user->saved_jobs->pluck('job_id')->toArray();
        }

        if ($this->search_query) {
            $this->find();
        }
    }

    public function show_more($id)
    {
        try {
            $record = JobPosts::with('applicants')->find($id);
            if (is_null($record)) {
                return $this->dispatch('alert', [
                    'status' => 'error',
                    'title' => 'Oops!',
                    'message' => 'Unknown job selected'
                ]);
            }

            $record->applicant_ids = $record->applicants->pluck('user_id')->toArray();
            $this->record_info = $record;
        } catch (\Exception $e) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Oops!',
                'message' => 'Error occurred: ' . $e->getMessage()
            ]);
        }
    }

    public function apply(int $job_id)
    {
        if (!Auth::guard('applicant')->check()) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Account Required!',
                'message' => 'You must create an account before applying to any jobs. To register, you can visit <a href="' . route('home.register') . '">here.</a>'
            ]);
        }

        $validator = Validator::make([
            'job_id' => $job_id,
            'user_id' => $this->user_id
        ], [
            'job_id' => 'required|exists:job_posts,id',
            'user_id' => 'required|exists:applicant_users,id'
        ]);

        if ($validator->fails()) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops!',
                'message' => 'Error occurred: ' . $validator->errors()
            ]);
        }

        if (JobApplicants::where('user_id', $this->user_id)->where('job_id', $job_id)->exists()) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Already Applied!',
                'message' => 'You have already applied to this job, please wait for the employer\'s response.'
            ]);
        }

        DB::beginTransaction();

        try {
            JobApplicants::create([
                'user_id' => $this->user_id,
                'applicant_no' => generate_code('APP'),
                'job_id' => $job_id,
                'status' => 'pending'
            ]);

            DB::commit();

            $this->applied_job_ids[] = $job_id;

            $this->dispatch('alert', [
                'status' => 'success',
                'title' => 'Congratulations!',
                'message' => 'Your application has been sent to the employer. Please wait for further instructions.'
            ]);

            $this->dispatch('sample');
        } catch (\Exception $e) {
            DB::rollBack();

            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Oops!',
                'message' => 'Error occurred: ' . $e->getMessage()
            ]);
        }
    }

    public function save_job(int $id)
    {
        if (!Auth::guard('applicant')->check()) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Account Required!',
                'message' => 'You must create an account before saving any jobs. To register, you can visit <a href="' . route('home.register') . '">here.</a>'
            ]);
        }

        $job_exists = JobPosts::where('id', $id)->exists();
        $saved_already = SavedJobs::where('user_id', $this->user_id)->where('job_id', $id)->exists();

        if (!$job_exists) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops!',
                'message' => 'The job does not exist!'
            ]);
        }

        DB::beginTransaction();

        try {
            if (!$saved_already) {
                SavedJobs::create([
                    'user_id' => $this->user_id,
                    'job_id' => $id
                ]);
            } else {
                SavedJobs::where('user_id', $this->user_id)->where('job_id', $id)->delete();
            }

            DB::commit();

            $this->saved_job_ids = SavedJobs::where('user_id', $this->user_id)->pluck('job_id')->toArray();
        } catch (\Exception $e) {
            DB::rollBack();

            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops!',
                'message' => 'Error occurred: ' . $e->getMessage()
            ]);
        }
    }

    public function placeholder()
    {
        return view('livewire.home.placeholder.jobs');
    }

    public function find()
    {
        $this->search_query = is_array($this->search_query ?? null) ? '' : (string) ($this->search_query ?? '');
        $this->isEmptySearch = $this->search_query === '';
        $this->dispatch('navigateToSearch', $this->search_query);
        $this->search_term = $this->search_query;
    }

    public function render()
    {
        $model = JobPosts::with('applicants', 'employment_type');

        $searchQuery = is_array($this->search_query ?? null) ? '' : (string) ($this->search_query ?? '');
        if ($this->search_term !== null && $this->search_term !== '') {
            $this->resetPage();

            $model->where(function ($query) use ($searchQuery) {
                $query->where('position', 'like', '%' . $searchQuery . '%')
                    ->orWhere('company_name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('location', 'like', '%' . $searchQuery . '%')
                    ->orWhere('setup', 'like', '%' . $searchQuery . '%')
                    ->orWhereHas('employment_type', function ($q) use ($searchQuery) {
                        $q->where('name', 'like', '%' . $searchQuery . '%');
                    })
                    ->orWhere('min_salary', 'like', '%' . $searchQuery . '%')
                    ->orWhere('max_salary', 'like', '%' . $searchQuery . '%')
                    ->orWhere('slots', 'like', '%' . $searchQuery . '%');
            });
        }

        $records = $model->latest()->paginate($this->entries);

        $this->search_result = [
            'isEmpty' => $records->total() === 0,
            'parameter' => $this->search_term,
            'total' => $records->total()
        ];

        return view('livewire.home.jobs', [
            'records' => $records
        ]);
    }
}
