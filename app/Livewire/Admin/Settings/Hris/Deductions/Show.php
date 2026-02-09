<?php

namespace App\Livewire\Admin\Settings\Hris\Deductions;

use App\Models\EmployeeDeductions;
use App\Models\EmployeeInformation;
use App\Models\OtherDeductions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public $id;
    public $employee_no;
    public $entries = 10;
    public $search = '';
    public $deductionName;
    
    public $amounts = [];
    public $valid_until = [];
    public $selected_id;

    protected $paginationTheme = 'bootstrap';
    protected $listeners = ['remove'];

    public function mount()
    {
        $this->loadRecords();
    }

    public function loadRecords()
    {
        $deduction = OtherDeductions::find($this->id);
        
        if (!$deduction) {
            return redirect()->route('other-deductions.index');
        }

        $this->deductionName = $deduction->name;

        $employees = EmployeeInformation::with('personal')->get();

        foreach ($employees as $employee) {
            $employeeDeduction = EmployeeDeductions::where('deduction_id', $this->id)
                ->where('employee_no', $employee->employee_no)
                ->first();

            $this->amounts[$employee->employee_no] = $employeeDeduction ? $employeeDeduction->amount : 0;
            $this->valid_until[$employee->employee_no] = $employeeDeduction ? $employeeDeduction->valid_until : null;
        }
    }

    protected function rules(string $employee_no)
    {
        return [
            "amounts.$employee_no" => 'nullable|numeric|min:0',
            "valid_until.$employee_no" => 'nullable|date',
        ];
    }

    protected function messages(string $employee_no)
    {
        return [
            "amounts.$employee_no.numeric" => 'The amount must be a number.',
            "amounts.$employee_no.min" => 'The amount must be 0 or greater.',
            "valid_until.$employee_no.date" => 'The valid until must be a valid date.',
        ];
    }

    public function save(string $employee_no)
    {
        if (Gate::denies('write employee-deductions')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!',
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        $this->validate($this->rules($employee_no), $this->messages($employee_no));

        try {
            DB::beginTransaction();

            $amount = $this->amounts[$employee_no] ?? 0;
            
            // Only save if amount is greater than 0
            if ($amount > 0) {
                EmployeeDeductions::updateOrCreate(
                    [
                        'deduction_id' => $this->id,
                        'employee_no' => $employee_no,
                    ],
                    [
                        'amount' => $amount,
                        'valid_until' => $this->valid_until[$employee_no] ?? null,
                    ]
                );
            } else {
                // Remove deduction if amount is 0 or empty
                EmployeeDeductions::where('deduction_id', $this->id)
                    ->where('employee_no', $employee_no)
                    ->delete();
                    
                $this->amounts[$employee_no] = 0;
                $this->valid_until[$employee_no] = null;
            }

            DB::commit();

            $this->dispatch('alert', [
                'status' => 'success',
                'title' => 'Saved!',
                'showAlert' => true,
                'message' => 'Deduction for employee ' . strtoupper($employee_no) . ' saved successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Error!',
                'showAlert' => true,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function remove(bool $isNotify = true, ?string $employee_no = null)
    {
        if (Gate::denies('write employee-deductions')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!',
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        if ($isNotify) {
            $title = 'Are you sure to continue?';
            $message = 'You are about to delete deduction record for employee <b>#' . strtoupper($employee_no) . '</b>. This action cannot be undone!';
            $action = 'remove';

            $this->selected_id = $employee_no;

            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);
        } else {
            $record = EmployeeDeductions::where('deduction_id', $this->id)
                ->where('employee_no', $this->selected_id)
                ->first();

            if ($record) {
                $record->delete();
                
                $this->amounts[$this->selected_id] = 0;
                $this->valid_until[$this->selected_id] = null;

                $this->dispatch('alert', [
                    'status' => 'success',
                    'title' => 'Deleted!',
                    'showAlert' => true,
                    'message' => 'Deduction record for employee ' . strtoupper($this->selected_id) . ' has been deleted successfully.'
                ]);
            } else {
                $this->dispatch('alert', [
                    'showAlert' => true,
                    'status' => 'error',
                    'title' => 'Error!',
                    'message' => 'Error: Record does not exist.'
                ]);
            }
        }
    }

    public function render()
    {
        $records = EmployeeInformation::with('personal');

        if ($this->search) {
            $this->resetPage();
            $records = $records->where(function ($query) {
                $query->where('employee_no', 'like', '%'.$this->search.'%')
                    ->orWhereHas('personal', function ($q) {
                        $q->where('firstname', 'like', '%'.$this->search.'%')
                          ->orWhere('lastname', 'like', '%'.$this->search.'%');
                    });
            });
        }

        $records = $records->paginate($this->entries);

        return view('livewire.admin.settings.hris.deductions.show', [
            'records' => $records,
        ]);
    }
}
