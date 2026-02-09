<?php

namespace App\Livewire\Admin\Reports\DailyTimeRecord;

use App\Models\EmployeeAccount;
use App\Models\EmployeeTimelogs;
use App\Models\EmployeeInformation;
use App\Models\EmployementTypes;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{

    use WithPagination;

    protected $paginationTheme = 'bootstrap';
    public $selectedType;
    public $entries = 10;
    public $search = '';
    public $monthYear;
    public $employmentTypes;

    public function mount() {
        $this->monthYear = Carbon::now();
        $this->employmentTypes = EmployementTypes::all();
    }

    public function render()
    {
        $query = EmployeeInformation::with('personal');

        // Sort by latest time in/out when timelogs are on the same DB as employee_information.
        $timelogsConnection = (new EmployeeTimelogs)->getConnectionName();
        $infoConnection = (new EmployeeInformation)->getConnectionName();
        if ($timelogsConnection === $infoConnection) {
            $query->addSelect([
                'employee_information.*',
                'last_timelog' => EmployeeTimelogs::query()
                    ->selectRaw('MAX(timestamp)')
                    ->whereRaw('timelogs.employee_id COLLATE utf8mb4_unicode_ci = employee_information.employee_no COLLATE utf8mb4_unicode_ci'),
            ])->orderByDesc('last_timelog');
        }

        if ($this->selectedType !== null) {
            if ($this->selectedType === 'unassigned') {
                $query->whereNull('employment_type_id');
            } else {
                $query->where('employment_type_id', $this->selectedType);
            }
        }

        if ($this->search) {

            $this->resetPage();

            $query->where(function ($q) {
                $q->where('employee_no', 'like', '%' . $this->search . '%')
                ->orWhereHas('personal', function ($subQuery) {
                    $subQuery->whereRaw(
                        "CONCAT(firstname, ' ', lastname) LIKE ?",
                        ['%' . $this->search . '%']
                    );
                });
            });
        }

        return view('livewire.admin.reports.daily-time-record.index', [
            'records' => $query->paginate($this->entries),
        ]);
    }
}
