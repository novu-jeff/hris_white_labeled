<?php

namespace App\Livewire\Employee;

use App\Models\CompanyInformation;
use App\Models\EmployeeAnnouncements;
use App\Models\EmployeeAtro;
use App\Models\EmployeeBusinessSlip;
use App\Models\EmployeeLeaveCard;
use App\Models\OffsetCredits;
use App\Models\EmployeeTimeAdjustments;
use App\Models\Holiday;
use App\Models\SalaryItemsPayroll;
use App\Models\LeaveType;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Carbon\Carbon;

use App\Models\EmployeeInformation;
use App\Models\EmployeeTimelogs;
use App\Models\EmployeeLeaveDates;
use App\Services\DailyTimeRecordService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
    public $employmentTypeName;

    public $selectedYear;
    public $selectedMonth;
    public $payslips;

    public $latestLogs;
    public $dtrDate;
    public $logs = null;

    public $workAnniversariesThisMonth = [];
    public $birthdaysThisMonth = [];
    public $upcomingEvents = [];
    public $teamTimelogs = [];

    public $showSalary = false;
    public $showSalaryPasswordPrompt = false;
    public $salaryPassword = '';
    public $dashboardCardOrder = null;


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
        })->values();

        $offsetCredits = (float) (OffsetCredits::where('employee_no', $employee_no)->value('credits') ?? 0);
        $this->leaveBalances->push([
            'code' => 'OFFSET',
            'name' => 'Offset Credits',
            'balance' => $offsetCredits,
        ]);

        $employee = Auth::user()->information->load('employment_type');

        $this->employeeInfo = $employee;
        $this->dateHired = $employee->date_hired ?? 'N/A';

        // Work anniversaries, new hires & interns this month (for dashboard card)
        // "New hire" / "Intern" only when hired in current calendar year and same month (first month at company)
        $now = Carbon::now();
        $this->workAnniversariesThisMonth = EmployeeInformation::with(['personal', 'positions', 'employment_type'])
            ->whereNotNull('date_hired')
            ->whereMonth('date_hired', $now->month)
            ->get()
            ->map(function ($emp) use ($now) {
                $name = $emp->personal
                    ? trim($emp->personal->firstname . ' ' . $emp->personal->lastname)
                    : $emp->employee_no;
                $hireDate = $emp->date_hired ? Carbon::parse($emp->date_hired) : null;
                // Anniversary month uses year delta (not exact day) so Feb 28 still counts in Feb.
                $years = $hireDate ? max(0, ((int) $now->year - (int) $hireDate->year)) : 0;
                $hireYear = $hireDate ? (int) $hireDate->format('Y') : 0;
                $isFirstMonth = $hireYear === (int) $now->year;
                $typeName = $emp->employment_type->name ?? null;
                $isIntern = $typeName && stripos($typeName, 'intern') !== false;
                return [
                    'name'       => $name,
                    'position'   => $emp->positions->name ?? '—',
                    'date'       => $hireDate ? $hireDate->format('M d') : '—',
                    'years'      => $years,
                    'is_new'     => $isFirstMonth,
                    'type_label' => $isFirstMonth ? ($isIntern ? 'Intern' : 'New hire') : null,
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

        $this->employmentTypeName = $employee->employment_type->name ?? '—';

        $this->loadUpcomingEvents();
        $this->getDTR();
        $this->loadTeamTimelogs();

        $order = Auth::user()->dashboard_card_order;
        $this->dashboardCardOrder = is_array($order) ? $order : null;
    }

    /**
     * Team members (same section) with today's clock in/out or leave/offset status.
     */
    private function loadTeamTimelogs(): void
    {
        $employee_no = Auth::user()->employee_no;
        $user = EmployeeInformation::where('employee_no', $employee_no)->first();
        if (!$user || !$user->section_id) {
            $this->teamTimelogs = [];
            return;
        }
        $today = now()->toDateString();
        $sectionId = $user->section_id;

        $teammates = EmployeeInformation::with(['personal', 'positions'])
            ->where('section_id', $sectionId)
            ->where('isDeleted', false)
            ->where('status', 'active')
            ->whereHas('account')
            ->get();

        $leaveToday = DB::table('employee_leave_dates')
            ->join('employee_leave', 'employee_leave.id', '=', 'employee_leave_dates.employee_leave_id')
            ->leftJoin('leave_types', 'leave_types.id', '=', 'employee_leave.leave_id')
            ->where('employee_leave_dates.date', $today)
            ->where('employee_leave.status', 'approved')
            ->select('employee_leave.employee_no', 'leave_types.name as leave_type_name', 'leave_types.code as leave_code')
            ->get()
            ->groupBy('employee_no');

        $offsetToday = DB::table('employee_offset_applications')
            ->where('offset_date_from', $today)
            ->where('status', 'approved')
            ->where('isDeleted', false)
            ->pluck('employee_no')
            ->flip()
            ->toArray();

        $timelogsByEmployee = EmployeeTimelogs::whereDate('timestamp', $today)
            ->orderBy('timestamp')
            ->get()
            ->groupBy('employee_id');

        $result = [];
        foreach ($teammates as $emp) {
            $no = $emp->employee_no;
            $name = $emp->personal
                ? trim($emp->personal->firstname . ' ' . ($emp->personal->middlename ? $emp->personal->middlename . ' ' : '') . $emp->personal->lastname)
                : $no;

            $status = null;
            $statusType = 'timelog'; // timelog | leave | offset

            if (!empty($offsetToday[$no])) {
                $status = 'Offset';
                $statusType = 'offset';
            } elseif (isset($leaveToday[$no])) {
                $first = $leaveToday[$no]->first();
                $label = $first->leave_type_name ?? $first->leave_code ?? 'Leave';
                if (stripos($label, 'vacation') !== false || ($first->leave_code ?? '') === 'VL') {
                    $status = 'On vacation';
                } elseif (stripos($label, 'sick') !== false || ($first->leave_code ?? '') === 'SL') {
                    $status = 'Sick Leave';
                } else {
                    $status = $label;
                }
                $statusType = 'leave';
            } elseif (isset($timelogsByEmployee[$no]) && $timelogsByEmployee[$no]->isNotEmpty()) {
                $logs = $timelogsByEmployee[$no];
                $first = $logs->first();
                $clockIn = Carbon::parse($first->timestamp)->format('g:i A');
                $clockOut = $logs->count() > 1 ? Carbon::parse($logs->last()->timestamp)->format('g:i A') : '—';
                $status = $clockIn . ' / ' . $clockOut;
            }

            $result[] = [
                'employee_no' => $no,
                'name'       => $name,
                'status'     => $status ?? '—',
                'status_type'=> $statusType,
            ];
        }
        $this->teamTimelogs = $result;
    }

    private function loadUpcomingEvents(): void
    {
        $today = Carbon::today();

        $events = Holiday::where('isDeleted', false)
            ->get()
            ->map(function ($holiday) use ($today) {
                $rawDate = trim((string) ($holiday->date ?? ''));

                if (preg_match('/^\d{2}-\d{2}$/', $rawDate)) {
                    $eventDate = Carbon::createFromFormat('Y-m-d', $today->year . '-' . $rawDate);
                    if ($eventDate->lt($today)) {
                        $eventDate->addYear();
                    }
                } else {
                    try {
                        $eventDate = Carbon::parse($rawDate);
                    } catch (\Throwable $e) {
                        return null;
                    }
                }

                $type = strtolower((string) $holiday->type);
                $isSpecial = in_array($type, ['special-non-working', 'special-working', 'company'], true);
                $tagLabel = match ($type) {
                    'special-non-working' => 'Special Non-Working',
                    'special-working' => 'Special Working',
                    'company' => 'Company Event',
                    'regular' => 'Regular Holiday',
                    default => Str::title(str_replace(['-', '_'], ' ', (string) $holiday->type)),
                };
                $typeLabel = Str::title(str_replace(['-', '_'], ' ', (string) $holiday->type));

                return [
                    'name' => $holiday->name,
                    'type' => $holiday->type,
                    'type_label' => $typeLabel,
                    'tag_label' => $tagLabel,
                    'is_special_event' => $isSpecial,
                    'event_date' => $eventDate->toDateString(),
                    'date_label' => $eventDate->format('M d, Y'),
                    'days_away' => $today->diffInDays($eventDate),
                ];
            })
            ->filter()
            ->sortBy('event_date')
            ->take(8)
            ->values()
            ->all();

        $this->upcomingEvents = $events;
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


    public function requestSalaryReveal(): void
    {
        if ($this->showSalary) {
            $this->showSalary = false;
            return;
        }

        $this->resetErrorBag('salaryPassword');
        $this->salaryPassword = '';
        $this->showSalaryPasswordPrompt = true;
    }

    public function verifySalaryPassword(): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        if (!Hash::check((string) $this->salaryPassword, (string) $user->password)) {
            $this->addError('salaryPassword', 'Incorrect password.');
            return;
        }

        $this->showSalaryPasswordPrompt = false;
        $this->salaryPassword = '';
        $this->showSalary = true;
    }

    public function cancelSalaryPassword(): void
    {
        $this->showSalaryPasswordPrompt = false;
        $this->salaryPassword = '';
        $this->resetErrorBag('salaryPassword');
    }

    /**
     * Save dashboard card order (left and right column card IDs) for the logged-in employee.
     */
    public function saveDashboardCardOrder(array $left, array $right): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }
        $this->dashboardCardOrder = ['left' => $left, 'right' => $right];
        $user->dashboard_card_order = $this->dashboardCardOrder;
        $user->save();
        $this->skipRender();
    }

    public function render()
    {
        return view('livewire.employee.dashboard');
    }
}
