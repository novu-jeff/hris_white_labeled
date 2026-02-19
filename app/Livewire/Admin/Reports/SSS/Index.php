<?php

namespace App\Livewire\Admin\Reports\SSS;

use App\Models\EmployeeInformation;
use App\Models\Sections;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $entries = 9999999;
    public $search = '';
    public $year;

    public $sections;
    public $selectedSection = '';

    public $total_employee_share = 0;
    public $total_employer_share = 0;
    public $total_contribution = 0;
    public $total_ec = 0;
    public $employee_count = 0;

    public function mount()
    {
        $this->year = now()->year;
        $this->sections = Sections::orderBy('name')->get();
    }

    public function render()
    {
        $contributionsService = app(\App\Services\ContributionsService::class);

        $query = EmployeeInformation::with('account', 'personal', 'section');

        if (!empty($this->search)) {
            $this->resetPage();
            $query->where(function ($q) {
                $q->where('employee_no', 'like', '%' . $this->search . '%')
                  ->orWhereHas('personal', function ($subQuery) {
                      $subQuery->whereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ['%' . $this->search . '%']);
                  });
            });
        }

        if (!empty($this->selectedSection)) {
            $query->whereHas('section', function ($q) {
                $q->where('id', $this->selectedSection);
            });
        }

        $paginated = $query->paginate($this->entries);

        // Reset totals before accumulation
        $this->total_employee_share = 0;
        $this->total_employer_share = 0;
        $this->total_contribution = 0;
        $this->total_ec = 0;

        $records = tap($paginated)->each(function ($record) use ($contributionsService) {
            $record->employee_share = 0;
            $record->employer_share = 0;
            $record->total = 0;
            $record->msc = 0;
            $record->ec = 0;
            $record->status = 'Employed';

             # Check if employee resigned on or before current month
            if ($record->date_resignation || $record->salary == 0) {
                $resigned = \Carbon\Carbon::parse($record->date_resignation);
                $current = now();

                # If resigned before or during the current month and year
                if ($resigned->year < $current->year || ($resigned->year == $current->year && $resigned->month <= $current->month) || $record->salary == 0) {
                    $record->salary = 0;
                    $record->status = 'No earnings';
                }
            }

            if ($record->salary) {
                $msc = $contributionsService->computeSalary($record->salary);
                $contribution = $contributionsService->computeSSS($msc);

                $record->employee_share = $contribution['employee_share'];
                $record->employer_share = $contribution['employer_share'];
                $record->ec = $contribution['ec'] ?? 0;
                $record->total = $contribution['total'];
                $record->msc = $contribution['msc'] ?? 0;

                $this->total_employee_share += $record->employee_share;
                $this->total_employer_share += $record->employer_share;
                $this->total_ec += $record->ec;
                $this->total_contribution += $record->total;
            }
        });

        $this->employee_count = $records->count();

         // Group by section with subtotals
        $groupedRecords = collect();

        $records->getCollection()
            ->groupBy(fn($record) => $record->section->name ?? 'NO DEPARTMENT')
            ->each(function ($group, $sectionName) use (&$groupedRecords) {
                $groupedRecords->push([
                    'section' => $sectionName,
                    'records' => $group,
                    'subtotal_employee_share' => $group->sum('employee_share'),
                    'subtotal_employer_share' => $group->sum('employer_share'),
                    'subtotal_ec' => $group->sum('ec'),
                    'subtotal_total' => $group->sum('total'),
                ]);
            });

        return view('livewire.admin.reports.s-s-s.index', [
            'groupedRecords' => $groupedRecords,
            'paginator' => $records,
            'sections' => $this->sections,
        ]);
    }
}
