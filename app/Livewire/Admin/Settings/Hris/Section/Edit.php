<?php

namespace App\Livewire\Admin\Settings\Hris\Section;

use App\Models\Branches;
use App\Models\Departments;
use App\Models\Sections;
use App\Models\EmployeeInformation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Edit extends Component
{

    public int $id;
    public array $fields;
    public $branches;
    public $departments;
    public $employees;

    public function mount() {
        $this->loadRecords($this->id);
    }

    public function loadRecords(int $id) {
        $branches = Branches::all();
        $departments = Departments::all();
        $employees = EmployeeInformation::with('personal', 'account')
            ->where('status', 'active')
            ->where('isDeleted', false)
            ->whereHas('personal')
            ->whereHas('account')
            ->get();

        $this->branches = $branches;
        $this->departments = $departments;
        $this->employees = $employees;

        $records = Sections::find($id);

        if(!$records) {
            return redirect()->route('section.index');
        }

        return $this->fields = [
            'name' => $records->name,
            'code' => $records->code,
            'branch' => $records->branch_id,
            'department' => $records->department_id,
            'supervisor' => $records->supervisor_id
        ];

    }

    protected function rules() {
        return [
            'fields.name' => [
                'required',
                Rule::unique('positions', 'name')
                    ->ignore($this->id)
            ],
            'fields.code' => 'required',
            'fields.branch' => 'required|exists:branches,id',
            'fields.department' => 'required|exists:departments,id',
            'fields.supervisor' => 'nullable|exists:employee_account,employee_no'
        ];
    }

    public function messages() {
        return [
            'fields.name.required' => 'The section name is required.',
            'fields.name.unique' => 'The section name has already been taken.',
    
            'fields.code.required' => 'The section code is required.',
    
            'fields.branch.required' => 'The branch is required.',
            'fields.branch.exists' => 'The selected branch is invalid or does not exist.',
    
            'fields.department.required' => 'The department is required.',
            'fields.department.exists' => 'The selected department is invalid or does not exist.',
            'fields.supervisor.exists' => 'The selected supervisor is invalid or does not exist.',
        ];
    }
    

    public function save() {
        
        if (Gate::denies('write sections')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!', 
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        $this->validate();

        DB::beginTransaction();

        try {

            $section = Sections::find($this->id);
            $section->code = $this->fields['code'];
            $section->name = $this->fields['name'];
            $section->branch_id = $this->fields['branch'];
            $section->department_id = $this->fields['department'];
            $section->supervisor_id = $this->fields['supervisor'] ?? null;
            $section->save();

            DB::commit();

            $this->dispatch('alert', [
                'status' => 'success',
                'title' => 'Success!', 
                'showAlert' => true,
                'message' => 'Section ' . strtoupper($this->fields['name']) . ' was updated successfully.'
            ]);
            
        } catch (\Exception $e) {

            DB::rollBack();

            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Oops!', 
                'showAlert' => true,
                'message' => 'Error occured: ' . $e->getMessage()
            ]);
        }

    }

    public function render()
    {
        return view('livewire.admin.settings.hris.section.edit');
    }
}