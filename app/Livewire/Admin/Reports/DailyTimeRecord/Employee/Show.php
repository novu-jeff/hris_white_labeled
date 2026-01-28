<?php

namespace App\Livewire\Admin\Reports\DailyTimeRecord\Employee;

use App\Http\Controllers\Admin\Services\LeaveCardService;
use App\Models\EmployeeInformation;
use App\Models\CompanyInformation;
use App\Models\EmployeeTimelogs;
use App\Services\DailyTimeRecordService;
use App\Models\ShiftSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Show extends Component
{
    public $records;
    public $logs = null;
    public $company;
    public $product;
    public $dtrDate;
    public $officialTime;
    public $monthDate;
    public $employee_no;
    public $errors;
    public $hasLeaveCard;
    public $bsd_emp_identical;
    public $modalLogs = [];
    public $showLunch;

    protected $dailyTimeRecordService;
    protected $leaveCardService;

    public function initializeService()
    {
        $this->dailyTimeRecordService = app(DailyTimeRecordService::class);
        $this->leaveCardService = app(LeaveCardService::class);
    }

    public function mount($employee_no, $month, $year)
    {
        $this->product = config('app.product');
        $this->company = CompanyInformation::first()->name ?? 'No Comapany Name';
        $this->bsd_emp_identical = config('app.bsd_emp_identical');
        $this->showLunch = filter_var(config('app.lunch_tracking', true), FILTER_VALIDATE_BOOLEAN);

        $this->initializeService();

        try {

            $employeeShift = ShiftSchedule::first(); // replace with employee-specific shift if needed
//dd($employeeShift->shift_duration );


            $this->officialTime = [
                'current_time' => Carbon::now()->format('h:i A'),
                'shift_duration' => $employeeShift->shift_duration ?? 'N/A',
            ];

            $this->dtrDate = Carbon::parse($month . ' ' . $year);
            $this->monthDate = $this->dtrDate->format('Y-m');
           // $this->officialTime = Carbon::now()->format('h:i A');
            $this->employee_no = $employee_no;

            $data = $this->getEmployeeInfo($employee_no);
            $bio_id = !$this->bsd_emp_identical ? $data->bsd_no : $data->employee_no;

            $monthDate = $this->dtrDate->format('m-Y');
            $logs = $this->dailyTimeRecordService->getDailyTimeRecord($employee_no, $monthDate);

            $hasLeaveCard = $this->leaveCardService->getLeaveCard($employee_no);
            $this->hasLeaveCard = $hasLeaveCard->isNotEmpty() ? true : false;
            $this->logs = [
                'employee_account' => [
                    'bsd_no' => $bio_id,
                    'employee_no' => $data->personal->employee_no,
                    'firstname' => $data->personal->firstname,
                    'middlename' => $data->personal->middlename,
                    'lastname' => $data->personal->lastname,
                    'position' => $data->positions->name ?? 'N/A',
                    'section' => $data->section->name ?? 'N/A',
                ],
                'dtr' => $logs
            ];


        } catch (\Exception $e) {
            $this->errors = array_merge($this->errors ?? [], explode("\n", trim($e->getMessage())));
        }
    }

    private function getEmployeeInfo(string $employee_no) {
        $data = EmployeeInformation::with('personal', 'section', 'positions')->where('employee_no', $employee_no)
            ->first();

        if(!is_null($data)) {
            return $data;
        }
    }

    public function changeMonth($action, $value = null)
    {

        if($action == 'control') {
            $currentDate = $this->dtrDate;
            $currentDate = $currentDate->addMonths($value);
        }

        if($action == 'date') {
            $currentDate = Carbon::parse($this->monthDate);
        }

        return redirect()->route('dtr.show', [
            'id' => $this->employee_no,
            'month' => $currentDate->format('F'),
            'year' => $currentDate->format('Y')
        ]);
    }

    public function showLogs()
    {
        try {
            $this->modalLogs = $this->getLogs();
            $this->dispatch('showModal', ['modal' => 'logs_modal']);
        } catch (\Exception $e) {
            Log::error('Error loading logs for admin DTR', [
                'employee_no' => $this->employee_no,
                'month' => $this->dtrDate->format('F Y'),
                'error' => $e->getMessage()
            ]);
            $this->modalLogs = [];
            $this->dispatch('showModal', ['modal' => 'logs_modal']);
        }
    }

    private function getLogs()
    {
        $data = $this->getEmployeeInfo($this->employee_no);
        if (!$data) {
            return [];
        }

        $bio_id = !$this->bsd_emp_identical ? $data->bsd_no : $data->employee_no;
        
        // Get the month and year from dtrDate
        $month = $this->dtrDate->month;
        $year = $this->dtrDate->year;

        $records = EmployeeTimelogs::with('employee.personal')
            ->where('employee_id', $bio_id)
            ->whereMonth('timestamp', $month)
            ->whereYear('timestamp', $year)
            ->orderBy('timestamp')
            ->get();

        return $records
            ->groupBy(fn($record) => optional(Carbon::parse($record->timestamp))->format('j/n/Y') . '|' . ($record->employee_id ?? 'undefined'))
            ->filter()
            ->map(function ($logs, $key) {
                [$date, $employee_id] = explode('|', $key);
                $logs = $logs->sortBy('timestamp')->values();

                $formatLog = fn($log) => [
                    'time' => optional(Carbon::parse($log->timestamp))->format('H:i:s'),
                    'captured_image' => $log->captured_image,
                    'captured_location' => $log->captured_location,
                    'accomplishment' => $log->accomplishment ?? null
                ];

                $baseData = [
                    'date' => $date,
                    'bsd_no' => $employee_id,
                    'employee' => optional($logs->first())->employee,
                    'origin' => optional($logs->first())->origin,
                ];

                $count = $logs->count();
                $lastHasAccomplishment = !empty(optional($logs->last())->accomplishment);

                if ($count === 2 && $lastHasAccomplishment) {
                    return array_merge($baseData, ['logs' => [
                        $formatLog($logs[0]),
                        [],
                        [],
                        $formatLog($logs[1]),
                    ]]);
                }

                if ($count === 3 && $lastHasAccomplishment) {
                    return array_merge($baseData, ['logs' => [
                        $formatLog($logs[0]),
                        $formatLog($logs[1]),
                        [],
                        $formatLog($logs[2]),
                    ]]);
                }

                return array_merge($baseData, ['logs' => $logs->map($formatLog)->values()->all()]);
            })
            ->sortByDesc(fn($item) => Carbon::createFromFormat('j/n/Y', $item['date']))
            ->values()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.admin.reports.daily-time-record.employee.show');
    }
}
