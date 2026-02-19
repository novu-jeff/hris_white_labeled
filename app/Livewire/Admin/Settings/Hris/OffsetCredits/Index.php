<?php

namespace App\Livewire\Admin\Settings\Hris\OffsetCredits;

use App\Models\EmployeeInformation;
use App\Models\OffsetCredits;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $entries = 10;
    public $search = '';
    public $credits = [];
    public $as_of = [];

    public function mount(): void
    {
        $this->loadRecords();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedEntries(): void
    {
        $this->resetPage();
    }

    public function loadRecords(): void
    {
        $all = OffsetCredits::query()->get()->keyBy('employee_no');

        foreach ($all as $employeeNo => $row) {
            $this->credits[$employeeNo] = (float) ($row->credits ?? 0);
            $this->as_of[$employeeNo] = $row->as_of ?? null;
        }
    }

    public function save(string $employeeNo): void
    {
        if (Gate::denies('write offset-credits')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!',
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        $this->validate([
            "credits.$employeeNo" => 'required|numeric|min:0',
            "as_of.$employeeNo" => 'nullable|date',
        ], [
            "credits.$employeeNo.required" => '*required',
            "credits.$employeeNo.numeric" => '*number only',
            "credits.$employeeNo.min" => '*must be 0 or greater',
            "as_of.$employeeNo.date" => '*invalid date',
        ]);

        OffsetCredits::updateOrCreate(
            ['employee_no' => $employeeNo],
            [
                'credits' => (float) ($this->credits[$employeeNo] ?? 0),
                'as_of' => $this->as_of[$employeeNo] ?? null,
            ]
        );

        $this->dispatch('alert', [
            'status' => 'success',
            'title' => 'Saved!',
            'showAlert' => true,
            'message' => "Offset credits updated for {$employeeNo}.",
        ]);
    }

    public function render()
    {
        $model = EmployeeInformation::with('personal')->whereNotNull('employee_no');

        if ($this->search) {
            $search = $this->search;
            $model->where(function ($query) use ($search) {
                $query->where('employee_no', 'like', '%' . $search . '%')
                    ->orWhereHas('personal', function ($subQuery) use ($search) {
                        $subQuery->whereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ['%' . $search . '%']);
                    });
            });
        }

        $records = $model->paginate($this->entries);

        return view('livewire.admin.settings.hris.offset-credits.index', [
            'records' => $records,
        ]);
    }
}
