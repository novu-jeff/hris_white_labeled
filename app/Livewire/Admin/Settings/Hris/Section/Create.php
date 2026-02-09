<?php

namespace App\Livewire\Admin\Settings\Hris\Section;

use App\Models\Branches;
use App\Models\Departments;
use App\Models\Sections;
use App\Models\EmployeeInformation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Create extends Component
{

    public array $fields;
    public $branches;
    public $departments;

    public function mount() {
        $this->loadRecords();
    }

    public function loadRecords() {
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
    }

    protected function rules() {
        return [
            'fields.name' => 'required|unique:sections,name',
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

            Sections::create([
                'code' => $this->fields['code'],
                'name' => $this->fields['name'],
                'branch_id' => $this->fields['branch'],
                'department_id' => $this->fields['department'],
                'supervisor_id' => $this->fields['supervisor'] ?? null,
            ]);

            DB::commit();

            $this->dispatch('alert', [
                'status' => 'success',
                'title' => 'Success!', 
                'showAlert' => true,
                'message' => 'Section ' . strtoupper($this->fields['name']) . ' was added successfully.'
            ]);

            $this->reset('fields');
            
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
        return view('livewire.admin.settings.hris.section.create');
    }
}
