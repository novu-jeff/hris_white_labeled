<?php

namespace App\Livewire\Employee;

use App\Models\CompanyInformation;
use App\Models\EmployeeAnnouncements;
use App\Models\EmployeeAtro;
use App\Models\EmployeeBusinessSlip;
use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveCard;
use App\Models\EmployeeTimeAdjustments;
use App\Models\SalaryItemsPayroll;
use App\Models\LeaveType;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Carbon\Carbon;

use App\Models\EmployeeInformation;
use App\Models\EmployeeTimelogs;
use App\Services\DailyTimeRecordService;

class Dashboard extends Component
{

    public $announcements;
    public $companyInfo;
    public $applications;
    public $latestPayslip;
    public $leaveBalances;       // whole leave type list with balances
    public $currentMonthCard;    // exact month leave record (VL & SL)
    public $currentMonth;        // NOVEMBER

    public $employeeInfo;
    public $positionName;
    public $shiftName;
    public $dateHired;
    public $breaktime;

    public $selectedYear;
    public $selectedMonth;
    public $payslips;

    public $latestLogs;
    public $dtrDate;
    public $logs = null;

    public $workAnniversariesThisMonth = [];
    public $birthdaysThisMonth = [];

    public $showSalary = false;


    public bool $isForRCOnly;

    protected $dailyTimeRecordService;

    public function mount() {
        
        $this->loadRecords();
        $this->checkAllowed();

        
    }

    public function loadRecords() {

       

        $employee_no = Auth::user()->employee_no;

        

        $this->announcements = EmployeeAnnouncements::where('isDeleted', false)
            ->latest()
            ->take(1)
            ->get();
        $this->companyInfo = $this->getCompanyInformation();

       /* $this->latestPayslip = SalaryItemsPayroll::where('employee_no', $employee_no)
        ->latest()
        ->first();*/
       $this->getLogs();
       

         $this->payslips = \DB::table('payroll_salary_items as i')
        ->join('payroll_salary as p', 'p.id', '=', 'i.payroll_id')
        ->where('i.employee_no', $employee_no)
        ->where('p.status', 'approved')
        ->select('i.*', 'p.payroll_date', 'p.cut_off_period')
        ->orderBy('p.payroll_date', 'desc')
        ->get();

    $this->latestPayslip = $this->payslips->first();


        /**
         * -----------------------------
         * CURRENT MONTH LEAVE CARD
         * -----------------------------
         */
        $this->currentMonth = strtoupper(now()->format('F')); 
        $year = now()->year;

         $this->dtrDate = Carbon::parse($this->currentMonth . ' ' . $year);

        $this->currentMonthCard = EmployeeLeaveCard::where('employee_no', $employee_no)
            ->where('year', $year)
            ->where('period', $this->currentMonth)
            ->first();

        /**
         * -----------------------------
         * ONLY VL AND SL LEAVE TYPES WITH BALANCES
         * -----------------------------
         */
        $leaveTypes = LeaveType::whereIn('code', ['VL', 'SL'])->get();

        $this->leaveBalances = $leaveTypes->map(function ($type) {
            $balance = 0;

            if ($this->currentMonthCard) {
                $balance = match($type->code) {
                    'VL' => $this->currentMonthCard->vl_bal,
                    'SL' => $this->currentMonthCard->sl_bal,
                    default => 0,
                };
            }

            return [
                'code'    => $type->code,
                'name'    => $type->name,
                'balance' => $balance,
            ];
        });

        $employee = Auth::user()->information;

        $this->employeeInfo = $employee;
        $this->dateHired = $employee->date_hired ?? 'N/A';

        // Work anniversaries, new hires & interns this month (for dashboard card)
        $now = Carbon::now();
        $this->workAnniversariesThisMonth = EmployeeInformation::with(['personal', 'positions', 'employment_type'])
            ->whereNotNull('date_hired')
            ->whereMonth('date_hired', $now->month)
            ->get()
            ->map(function ($emp) use ($now) {
                $name = $emp->personal
                    ? trim($emp->personal->firstname . ' ' . $emp->personal->lastname)
                    : $emp->employee_no;
                $years = $emp->date_hired ? $now->diffInYears(Carbon::parse($emp->date_hired)) : 0;
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
            })
            ->values()
            ->all();

        $this->birthdaysThisMonth = EmployeeInformation::with(['personal', 'positions'])
            ->whereHas('personal', function ($q) use ($now) {
                $q->whereNotNull('birthday')->whereMonth('birthday', $now->month);
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
            })
            ->values()
            ->all();

        $this->positionName = $employee->positions->name ?? 'No Position Assigned';

        $shift = $employee->shift;
        $this->shiftName = $shift && $shift->shift_duration && $shift->work_hours
            ? $shift->shift_duration . ' ' . $shift->work_hours . ' Hours'
            : 'No Shift Schedule';

        $this->breaktime = ($shift && $shift->break_out && $shift->break_in)
            ? Carbon::parse($shift->break_out)->format('h:i A') . ' - ' . Carbon::parse($shift->break_in)->format('h:i A')
            : 'No Breaktime Assigned';

        $this->getDTR();

       
    }

    public function checkAllowed() {
        
        $product = config('app.product');

        if($product == 'government') {
            if(Auth::user()->information->employment_type_id !== 1) {
                return $this->isForRCOnly = false;
            }
            return $this->isForRCOnly = true;
        } 

        return $this->isForRCOnly = true;
    }

    private function getCompanyInformation() {
        $companyInfo = CompanyInformation::with('type')->first();
        return $companyInfo;
    }

    private function getLogs()
    {
        $employee_no = Auth::user()->employee_no;

        $logs = EmployeeTimelogs::where('employee_id', $employee_no)
            ->whereDate('timestamp', now())
            ->orderBy('timestamp', 'asc')
            ->get();

        // Initialize
        $this->latestLogs = [
            'clock_in'  => null,
            'break_out' => null,
            'break_in'  => null,
            'clock_out' => null,
        ];

        $index = 0;

        foreach ($logs as $log) {



            // Format to TIME ONLY
            $log->formatted_time = Carbon::parse($log->timestamp)->format('h:i A');

            // Assign based on ORDER of logs
            if ($index === 0) {
                $this->latestLogs['clock_in'] = $log;
            } elseif ($index === 1) {
                $this->latestLogs['break_out'] = $log;
            } elseif ($index === 2) {
                $this->latestLogs['break_in'] = $log;
            } elseif ($index === 3) {
                $this->latestLogs['clock_out'] = $log;
            }

            $index++;
        }
    }

    private function getDTR() {

         $employee_no = Auth::user()->employee_no;

         $this->dailyTimeRecordService = app(DailyTimeRecordService::class);

         $monthDate = $this->dtrDate->format('m-Y');
         $logs = $this->dailyTimeRecordService->getDailyTimeRecord($employee_no, $monthDate);

         $this->logs = [
                
                'dtr' => $logs
            ];
    }

    private function getCurrentAndPastDTR()
    {
        $allLogs = $this->logs['dtr']['logs']; // full logs array keyed by date
        $today = now()->format('Y-m-d');

        $dtrToShow = [];

        foreach ($allLogs as $date => $day) {
            // Only show today and past days
            if ($date <= $today) {
                $dtrToShow[$date] = $day;
            }
        }

        ksort($dtrToShow); // optional: sort ascending by date
        return $dtrToShow;
    }


    // Toggle salary visibility
    public function toggleSalary()
    {
        $this->showSalary = !$this->showSalary;
    }

    public function render()
    {
        return view('livewire.employee.dashboard');
    }
}
