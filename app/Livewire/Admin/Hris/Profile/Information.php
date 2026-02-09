<?php

namespace App\Livewire\Admin\Hris\Profile;

use App\Http\Controllers\Admin\Services\HRISProcessingService;
use App\Http\Controllers\Admin\Services\OtherServices;
use App\Services\ContributionsService;
use App\Models\Branches;
use App\Models\EmployeeInformation;
use App\Models\EmployeeSchedule;
use App\Models\EmployementTypes;
use App\Models\Positions;
use App\Models\Sections;
use App\Models\ShiftSchedule;
use App\Models\Tranche;
use Dotenv\Exception\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Information extends Component
{

    public $form;
    public $employee_no;
    public object $branches;
    public object $sections;
    public object $positions;
    public object $employmentTypes;
    public object $shiftSchedule;
    public object $employeeSchedule;
    public object $salaryGrade;
    public bool $isGovernment = false;
    public array $records;
    public $selectedBranchId = null;
    public array $deductions = [];
    public ?int $internTypeId = null;

    protected $listeners = ['save'];

     public function mount() {
        $this->loadRecords();
        $this->handleSalary();
    }

    public function loadRecords() {

        if (empty($this->employee_no)) {
            return redirect()->route('hris.index');
        }

        $this->branches = Branches::orderBy('name')->get();
        $this->sections = collect([]);
        $this->positions = collect([]);
        $this->employmentTypes = EmployementTypes::all();
        $this->internTypeId = EmployementTypes::where('name', 'Interns')->value('id');

        $this->shiftSchedule = ShiftSchedule::all();
        $this->employeeSchedule = EmployeeSchedule::all();

        $otherServices = new OtherServices();
        $earnings = $otherServices->earnings($this->employee_no) ?? [];
        $deductions = $otherServices->deductions($this->employee_no) ?? [];
        $leaveCredits = $otherServices->leaves($this->employee_no) ?? [];

        $data = EmployeeInformation::with([
            'department',
            'personal.gsis_item',
            'account',
            'education',
            'parents',
            'children',
            'employment_history',
            'civil_service',
            'trainings',
            'others',
            'skills'
        ])->where('employee_no', $this->employee_no)->first();

        if (!$data) {
            $this->records = [];
            return;
        }

        if($data->isTransferingEmp) {
            return redirect()
                ->route('hris.index')
                ->with([
                    'dispatch' => 'isTransfering'
                ]);
        }

        $this->records = [
            'employee_information' => $this->formatInformation($data),
            'employee_personal' => $this->formatPersonal($data),
            'other_earnings' => $earnings,
            'other_deductions' => $deductions,
            'leaveCredits' => $leaveCredits
        ];

        // Calculate statutory deductions
        $this->calculateDeductions($data);

        // Initialize selected branch based on current section assignment
        if (!empty($data->section_id)) {
            $this->selectedBranchId = Sections::where('id', $data->section_id)->value('branch_id');
        }

        $this->refreshSections();

        if (!empty($data->section_id)) {
            $this->select_change('section');
        }

        if (!empty($data->personal) && $data->personal->citizenship === 'dual_citizenship') {
            $this->select_change('citizenship');
        }

        if (!empty($data->personal) && $data->personal->civil_status === 'married') {
            $this->select_change('civil_status');
        }
    }

    public function select_change(string $property) {
        if($property == 'section') {
            $section_id = $this->records['employee_information']['section_id'];
            $record = Sections::with('branch', 'department')->where('id', $section_id)->first();
            
            if($record) {
                $this->selectedBranchId = $record->branch_id;
                $this->refreshSections();
                $this->records['employee_information']['branch'] = $record->branch->name ?? '';
                $this->records['employee_information']['department'] = $record->department->name ?? '';
            } else {
                $this->records['employee_information']['branch'] = '';
                $this->records['employee_information']['department'] = '';
            }
        }
    }

    public function updatedSelectedBranchId($value): void
    {
        $this->refreshSections();

        // If the currently selected section doesn't belong to the selected branch, clear it.
        $sectionId = $this->records['employee_information']['section_id'] ?? null;
        if (!$sectionId) {
            $this->records['employee_information']['branch'] = '';
            $this->records['employee_information']['department'] = '';
            return;
        }

        $belongs = Sections::where('id', $sectionId)
            ->where('branch_id', $this->selectedBranchId)
            ->exists();

        if (!$belongs) {
            $this->records['employee_information']['section_id'] = null;
            $this->records['employee_information']['branch'] = '';
            $this->records['employee_information']['department'] = '';
        }
    }

    private function refreshSections(): void
    {
        $query = Sections::query();

        if (!empty($this->selectedBranchId)) {
            $query->where('branch_id', $this->selectedBranchId);
        }

        $this->sections = $query->orderBy('name')->get();
    }

  public function handleSalary()
{

     if (
        empty($this->records) ||
        !isset($this->records['employee_information'])
    ) {
        return;
    }
    $eligible = $this->records['employee_information']['type'] ?? '';
    $position_id = $this->records['employee_information']['position_id'] ?? '';
    $product = config('app.product');

    if ($product === 'government') {
        $this->isGovernment = true;

        if ($eligible != 3 && !empty($position_id)) {

            $this->positions = Positions::where('type', $eligible)->get();

            // Get salary_grade for this position
            $salaryGrade = Positions::where('id', $position_id)->value('salary_grade');

            // Get the latest tranche for this eligible type
            $latestTranche = Tranche::with(['items' => function ($query) use ($salaryGrade) {
                $query->where('salary_grade', $salaryGrade);
            }])
            ->where('eligible', $eligible)
            ->orderByDesc('created_at')
            ->first();

            $salary = 0;
            $wtax = 0;
            $latestStep = 1;

            if ($latestTranche && $latestTranche->items->isNotEmpty()) {
                $item = $latestTranche->items->first(); // the item for this salary grade

                // Loop through steps to find the highest non-zero salary
                for ($i = 1; $i <= 8; $i++) {
                    $stepColumn = "step_" . $i;
                    $stepColumnTax = "step_" . $i . "_wtax";
                    if (!empty($item->$stepColumn) && $item->$stepColumn > 0) {
                        $latestStep = $i;
                        $salary = $item->$stepColumn; // take the latest step salary
                        $wtax = $item->$stepColumnTax;
                    }
                }
            }

            // Set the latest step and salary
            $this->records['employee_information']['step_id'] = $latestStep;
            $this->records['employee_information']['salary'] = $salary;
            $this->records['employee_information']['w_tax'] = $wtax;

        } else {
            // For job order or missing data
            $employeeInfo = EmployeeInformation::where('employee_no', $this->employee_no)
            ->select('salary', 'w_tax')
            ->first();

            $this->records['employee_information']['salary'] = $employeeInfo->salary ?? 0;
            $this->records['employee_information']['w_tax']  = $employeeInfo->w_tax ?? 0;
        }

    } else {
        // Non-government
        $this->positions = Positions::all();
        $this->isGovernment = false;
    }

    // Intern without "has salary": force salary to 0 (both government and non-government)
    if ($this->isIntern() && empty($this->records['employee_information']['has_salary'] ?? false)) {
        $this->records['employee_information']['salary'] = 0;
    }
}

    public function isIntern(): bool
    {
        if ($this->internTypeId === null || empty($this->records['employee_information']['type'])) {
            return false;
        }
        return (string) $this->records['employee_information']['type'] === (string) $this->internTypeId;
    }

    protected function salaryValidationRule(): string
    {
        if ($this->isIntern() && empty($this->records['employee_information']['has_salary'] ?? false)) {
            return 'nullable|numeric|min:0';
        }
        return 'required|numeric|gt:1000';
    }

    public function formatInformation($data) {
        return [
            'id' => $data->id,
            'employee_id' => format_id($data->id, 6),
            'employee_no' => $data->employee_no,
            'biometrics_id' => $data->bsd_no,
            'shift_schedule' => $data->shift_id,
            'employee_schedule' => $data->schedule_id,
            'section_id' => $data->section_id,
            'position_id' => $data->position_id,
            'job_completion' => $data->job_completion,
            'step_id' => $data->step_id ?? '1',
            'date_hired' => $data->date_hired,
            'service_duration' => relative_time_duration($data->date_hired),
            'date_resignation' => $data->date_resignation,
            'type' => $data->employment_type_id,
            'status' => $data->status,
            'salary_method' => $data->salary_method,
            'salary' => $data->salary,
            'has_salary' => (bool) ($data->has_salary ?? false),
            'allowance' => $data->allowance ?? null,
            'payroll_account_number' => $data->payroll_account_number,
        ];
    }

    protected function formatPersonal($data)
    {
        $personal = $data->personal ?? null;
        $account = $data->account ?? null;

        if ($personal && $personal->birth_certificate) {
            $this->hasBirthCert = true;
        }

        if ($personal && $personal->marriage_certificate) {
            $this->hasMarriageCert = true;
        }

        return [
            'profile' => $personal->profile ?? null,
            'firstname' => $personal->firstname ?? null,
            'middlename' => $personal->middlename ?? null,
            'lastname' => $personal->lastname ?? null,
            'suffix' => $personal->suffix ?? null,
            'birthday' => $personal->birthday ?? null,
            'civil_status' => $personal->civil_status ?? null,
            'sex' => $personal->sex ?? null,
            'citizenship' => $personal->citizenship ?? null,
            'citizenship_type' => $personal->citizenship_type ?? null,
            'solo_parent' => $personal && $personal->solo_parent ? 'yes' : 'no',
            'country' => $personal->country ?? null,
            'present_address' => $personal->present_address ?? null,
            'present_province' => $personal->present_province ?? null,
            'present_city' => $personal->present_city ?? null,
            'permanent_address' => $personal->permanent_address ?? null,
            'permanent_province' => $personal->permanent_province ?? null,
            'permanent_city' => $personal->permanent_city ?? null,
            'mobile_number' => $personal->mobile_number ?? null,
            'tel_no' => $personal->tel_no ?? null,
            'email' => $account->email ?? null,
            'height' => $personal->height ?? null,
            'weight' => $personal->weight ?? null,
            'blood_type' => $personal->blood_type ?? null,
            'gsis_no' => $personal->gsis_no ?? null,
            'pagibig_no' => $personal->pagibig_no ?? null,
            'philhealth_no' => $personal->philhealth_no ?? null,
            'sss_no' => $personal->sss_no ?? null,
            'tin_no' => $personal->tin_no ?? null,
        ];
    }


    protected function rules(?string $employee_no = null) {
        return [
            'records.employee_information.biometrics_id' => [
                'required',
                Rule::unique('employee_information', 'bsd_no')->ignore($employee_no, 'employee_no')
            ],
            'records.employee_information.status' => 'required|in:active,inactive',
            'records.employee_information.date_hired' => 'required|date',
            'records.employee_information.job_completion' => 'required_if:records.employee_information.type,3|nullable|date',
            

            'records.employee_information.section_id' => 'required|exists:sections,id',
            'records.employee_information.type' => 'required|exists:employment_types,id',
            'records.employee_information.position_id' => 'required_if:records.employee_information.type,1,2|nullable|exists:positions,id|required_without:records.employee_information.type',

            'records.employee_information.step_id' => 'required|in:1,2,3,4,5,6,7,8',
            'records.employee_information.salary' => $this->salaryValidationRule(),
            'records.employee_information.allowance' => 'nullable|numeric|min:0',
            'records.employee_information.salary_method' => 'nullable|in:cash,bank transfer,paycheck,e-wallet',
        ];
    }

    protected function messages() {
        return [
            'records.employee_information.employee_no.required' => 'The employee no is required.',
            'records.employee_information.employee_no.unique' => 'The employee no is already taken.',
            'records.employee_information.biometrics_id.required' => 'The biometrics ID is required.',
            'records.employee_information.biometrics_id.unique' => 'The biometrics ID is already taken.',
            'records.employee_information.type.in' => 'The selected employment type does not exists.',
            'records.employee_information.status.required' => 'The account status is required.',
            'records.employee_information.status.in' => 'The status must be either active or inactive.',
            'records.employee_information.date_hired.required' => 'The date hired is required',
            'records.employee_information.date_hired.date' => 'The date hired must be valid date',
            'records.employee_information.section_id.required' => 'The section is required.',
            'records.employee_information.section_id.exists' => 'The selected section does not exist.',
            'records.employee_information.position_id.required_if' => 'The position field is required when employee type is not job order.',
            'records.employee_information.position_id.exists' => 'The selected position is invalid.',
            'records.employee_information.position_id.required_without' => 'The position is required unless an employee type is provided.',
            'records.employee_information.job_completion.required_if' => 'The job completion date is required when employee type is job order.',
            'records.employee_information.job_completion.date' => 'The job completion must be a valid date.',
            'records.employee_information.step_id.required' => 'The tranche step is required.',
            'records.employee_information.step_id.in' => 'The tranche step is invalid.',
            'records.employee_information.salary.required' => 'The basic salary is required',
            'records.employee_information.salary.numeric' => 'The basic salary must be numbers',
            'records.employee_information.salary.gt' => 'The basic salary must be greater than 1000',
            'records.employee_information.allowance.numeric' => 'The allowance must be a number',
            'records.employee_information.allowance.min' => 'The allowance must be 0 or greater',
            'records.employee_information.salary_method.in' => 'The salary method must be one of the following: cash, bank transfer, paycheck, or e-wallet.',
            'records.employee_information.type.required' => 'The employment type is required',
            'records.employee_information.type.exists' => 'The selected employment type does not exists.',
        ];
    }

    public function save(bool $isNotify = true) {

        if (Gate::denies('write hris')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!', 
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        $id = $this->employee_no;

        try {
            $this->validate($this->rules($id));
        } catch (ValidationException $e) {
            $errors = $e->validator->errors()->keys();
            $this->setErrorActiveTabAccordions($errors);
            $this->dispatch('scrollToError', $errors);
            throw $e;
        }

        if($isNotify) {
            $title = 'Are you sure to continue?';
            $message = 'The action cannot be undone or reverted!';
            $action = 'save';
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

            return;
        }

        $record = EmployeeInformation::where('employee_no', $id)->first();
        if (!$record) {
            return $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Oops',
                'isRemoveRowDT' => false,
                'showAlert' => true,
                'message' => 'Error: You\'re saving a non-existent employee!'
            ]);
        }

        DB::beginTransaction();

        // Normalize empty strings to null for nullable fields (preserve 0 as valid value)
        if (isset($this->records['employee_information']['allowance']) && $this->records['employee_information']['allowance'] === '') {
            $this->records['employee_information']['allowance'] = null;
        }

        \Log::debug('saving employee information', [
            'employee_no' => $id,
            'records' => $this->records,
        ]);

        try {

            \Log::debug("before processing employee information save", [ "employee_no" => $id, "records" => $this->records, ]); 

            $process = new HRISProcessingService;
            $process->save(false, $id, $id, 'information', $this->records);

            DB::commit();

            return $this->dispatch('alert', [
                'status' => 'success',
                'title' => 'Success!',
                'isRemoveRowDT' => false,
                'isReloadDT' => false,
                'message' => 'Employee ' . strtoupper($id) . ' records saved successfully.',
                'redirect' => '_stay'
            ]);

        } catch (\Exception $e) {
            \Log::debug('error saving employee information', [
                'employee_no' => $id,
                'error' => $e->getMessage(),
            ]);
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

    private function calculateDeductions($data)
    {
        $salary = $data->salary ?? 0;
        $contributionService = new ContributionsService();

        // Calculate statutory deductions
        $sssData = $contributionService->computeSSS((float)$salary);
        $hdmfData = $contributionService->computePagibig((float)$salary);
        $philhealthData = $contributionService->computePhilHealth((float)$salary);
        $totalContributions = ($sssData['employee_share'] ?? 0) + ($hdmfData['employee_share'] ?? 0) + ($philhealthData['employee_share'] ?? 0);
        $taxableIncome = max(0, $salary - $totalContributions);
        $tax = ContributionsService::computeWithholdingTax($taxableIncome);
        $sss = $sssData;
        $hdmf = $hdmfData;
        $philhealth = $philhealthData;

        $this->deductions = [
            'tax' => number_format($tax, 2),
            'sss' => number_format($sss['employee_share'] ?? 0, 2),
            'hdmf' => number_format($hdmf['employee_share'] ?? 0, 2),
            'philhealth' => number_format($philhealth['employee_share'] ?? 0, 2),
        ];
    }

    public function render()
    {
        return view('livewire.admin.hris.profile.information');
    }
}
