<?php

namespace App\Livewire\Admin\Dashboard;

use App\Http\Controllers\Admin\Settings\HRIS\EmploymentTypeController;
use App\Models\CompanyInformation;
use App\Models\EmployeeAtro;
use App\Models\EmployeeBusinessSlip;
use App\Models\EmployeeOffsetApplication;
use App\Models\EmployeeTimelogs;
use App\Models\EmployeeInformation;
use App\Models\EmployeeLeave;
use App\Models\EmployementTypes;
use App\Models\JobApplicants;
use App\Models\OtherDeductions;
use App\Models\OtherEarnings;
use Carbon\Carbon;
use Faker\Provider\ar_EG\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Index extends Component
{

    public $product;
    public $stats;
    public $now;
    public $trails;
    public $companyInfo;

    public function mount() {
        $this->now = Carbon::now();
        $this->loadRecords();
    }

    public function loadRecords()
    {
        $recruitmentCounts = JobApplicants::groupBy('status')
            ->select('status', DB::raw('count(*) as total'))
            ->pluck('total', 'status')->toArray();

        $employeeCounts = EmployementTypes::withCount('employees')->get();

        // Format the result for easier readability (optional)
        $employeeCounts = $employeeCounts->map(function ($type) {
            return [
                'employment_type' => $type->name,
                'employee_count' => $type->employees_count,
            ];
        });
            
        $leaveCounts = EmployeeLeave::groupBy('status')
            ->select('status', DB::raw('count(*) as total'))
            ->pluck('total', 'status')->toArray();

        
        $obsCounts = EmployeeBusinessSlip::groupBy('status')
            ->select('status', DB::raw('count(*) as total'))
            ->pluck('total', 'status')->toArray();

        $atroCounts = EmployeeAtro::groupBy('status')
            ->select('status', DB::raw('count(*) as total'))
            ->pluck('total', 'status')->toArray();

        $offsetCounts = EmployeeOffsetApplication::where('isDeleted', false)
            ->groupBy('status')
            ->select('status', DB::raw('count(*) as total'))
            ->pluck('total', 'status')->toArray();

        $deductions = OtherDeductions::all();

        // Work anniversaries, new hires & interns this month (date_hired month = current month)
        $workAnniversariesThisMonth = EmployeeInformation::with(['personal', 'positions', 'employment_type'])
            ->whereNotNull('date_hired')
            ->whereMonth('date_hired', $this->now->month)
            ->get()
            ->map(function ($emp) {
                $name = $emp->personal
                    ? trim($emp->personal->firstname . ' ' . $emp->personal->lastname)
                    : $emp->employee_no;
                $years = $emp->date_hired ? $this->now->diffInYears(Carbon::parse($emp->date_hired)) : 0;
                $typeName = $emp->employment_type->name ?? null;
                $isIntern = $typeName && stripos($typeName, 'intern') !== false;
                return [
                    'name'       => $name,
                    'position'   => $emp->positions->name ?? '—',
                    'date'       => Carbon::parse($emp->date_hired)->format('M d'),
                    'years'      => $years,
                    'is_new'     => $years === 0,
                    'type_label' => $years === 0 ? ($isIntern ? 'Intern' : 'New hire') : null,
                ];
            });

        // Birthdays this month (personal.birthday month = current month)
        $birthdaysThisMonth = EmployeeInformation::with(['personal', 'positions'])
            ->whereHas('personal', function ($q) {
                $q->whereNotNull('birthday')->whereMonth('birthday', $this->now->month);
            })
            ->get()
            ->map(function ($emp) {
                $name = $emp->personal
                    ? trim($emp->personal->firstname . ' ' . $emp->personal->lastname)
                    : $emp->employee_no;
                return [
                    'name'     => $name,
                    'position' => $emp->positions->name ?? '—',
                    'date'     => $emp->personal && $emp->personal->birthday
                        ? Carbon::parse($emp->personal->birthday)->format('M d')
                        : '—',
                ];
            });

        // Clock in / out summary for today, based on raw timelog records.
        // We derive employee-level status from timelog "status" (internal) or "status1" (external) flags:
        //  - 0 = clock in
        //  - 1 = clock out
        $clockinoutLogs = EmployeeTimelogs::whereDate('timestamp', Carbon::today())->get();

        $statusField = config('app.external_timelogs') ? 'status1' : 'status';
        $clockedInCount = 0;
        $inProgressCount = 0;
        $clockedOutCount = 0;

        $clockinoutLogs
            ->groupBy('employee_id')
            ->each(function ($logsPerEmployee) use (&$clockedInCount, &$inProgressCount, &$clockedOutCount, $statusField) {
                $hasIn = $logsPerEmployee->contains($statusField, 0);
                $hasOut = $logsPerEmployee->contains($statusField, 1);

                if ($hasIn) {
                    $clockedInCount++;
                }

                if ($hasIn && !$hasOut) {
                    $inProgressCount++;
                }

                if ($hasOut) {
                    $clockedOutCount++;
                }
            });
        
        $this->companyInfo = $this->getCompanyInformation();

        $payrollCounts = DB::table('payroll_salary')
                ->groupBy('status')
                ->select('status', DB::raw('count(*) as total'))
                ->pluck('total', 'status')
                ->toArray();

        $this->stats = [
            'recruitment' => [
                'pending' => $recruitmentCounts['pending'] ?? 0,
                'interview' => $recruitmentCounts['interview'] ?? 0,
                'placement' => $recruitmentCounts['placement'] ?? 0,
                'onboarding' => $recruitmentCounts['onboarding'] ?? 0,
                'hired' => $recruitmentCounts['hired'] ?? 0,
                'rejected' => $recruitmentCounts['disapproved'] ?? 0
            ],
            'employee' => $employeeCounts,
            'clockinout' => [
                'clockin'    => $clockedInCount,
                'inprogress' => $inProgressCount,
                'clockout'   => $clockedOutCount,
            ],
            'leave' => [
                'pending' => $leaveCounts['pending'] ?? 0,
                'granted' => $leaveCounts['approved'] ?? 0,
                'rejected' => $leaveCounts['disapproved'] ?? 0,
            ],
            'obs' => [
                'pending' => $obsCounts['pending'] ?? 0,
                'granted' => $obsCounts['approved'] ?? 0,
                'rejected' => $obsCounts['disapproved'] ?? 0,
            ],
            'atro' => [
                'pending' => $atroCounts['pending'] ?? 0,
                'granted' => $atroCounts['approved'] ?? 0,
                'rejected' => $atroCounts['disapproved'] ?? 0,
            ],
            'offset' => [
                'pending' => $offsetCounts['pending'] ?? 0,
                'granted' => $offsetCounts['approved'] ?? 0,
                'rejected' => $offsetCounts['disapproved'] ?? 0,
            ],
            'deductions' => $deductions,
            'work_anniversaries_this_month' => $workAnniversariesThisMonth->values()->all(),
            'birthdays_this_month'          => $birthdaysThisMonth->values()->all(),
            'payroll' => [
                'approved' => $payrollCounts['approved'] ?? 0,
                'pending'  => $payrollCounts['pending'] ?? 0,
            ],
        ];
        
        $this->getTrails();
    }
   

    private function getTrails() {
        $directory = storage_path('logs/trails');
        
        if (!File::exists($directory)) {
            $this->trails = [];
            return;
        }

        $files = File::files($directory);

        
        $this->trails = collect($files)
            ->sortByDesc(fn($file) => $file->getFilename())
            ->take(5) // ⬅️ limit to 5
            ->map(fn($file) => $file->getFilename())
            ->toArray();
    }

    private function getCompanyInformation() {
        $companyInfo = CompanyInformation::with('type')->first();
        return $companyInfo;
    }

    public function download(string $log) {
        $directory = storage_path('logs/trails');
        $filePath = $directory . DIRECTORY_SEPARATOR . $log;

        if (!File::exists($filePath)) {
            session()->flash('error', 'Log file does not exist.');
            return;
        }

        return response()->download($filePath);
    }

    public function render()
    {
        return view('livewire.admin.dashboard.index');
    }
}