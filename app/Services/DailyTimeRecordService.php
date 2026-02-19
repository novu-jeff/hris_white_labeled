<?php

namespace App\Services;

use App\Models\EmployeeAUT;
use App\Models\EmployeeTimelogs;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\EmployeePersonal;

class DailyTimeRecordService {
    
    protected $bsd_emp_identical;

    public function __construct()
    {
        $this->bsd_emp_identical = config('app.bsd_emp_identical');
    }

    /**
     * Retrieve and compute the Daily Time Record (DTR) of an employee for a given date range or month.
     *
     * @param string $employee_no The employee number.
     * @param array|string $dateInput Either:
     *      - An array with two elements [startDate, endDate] (e.g. ['2025-07-01', '2025-07-31']), or
     *      - A string in the format 'mm-YYYY' (e.g. '07-2025').
     *
     * @return array An associative array containing:
     *      - 'logs' => array of daily formatted logs
     *      - 'summary' => overall attendance summary for the period
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException If the input date format is invalid.
     */
    public function getDailyTimeRecord($employee_no, $dateInput)
    {
        try {

           
            # Case 1: Date Range Input (array with 2 elements)
            if (is_array($dateInput) && count($dateInput) === 2) {
                $startDate = Carbon::parse($dateInput[0])->startOfDay()->toDateTimeString();
                $endDate = Carbon::parse($dateInput[1])->endOfDay()->toDateTimeString();
            }
            # Case 2: Month-Year Input (e.g. "07-2025")
            elseif (is_string($dateInput)) {
                $monthCarbon = Carbon::createFromFormat('m-Y', $dateInput);
                $startDate = $monthCarbon->startOfMonth()->toDateTimeString();
                $endDate = $monthCarbon->endOfMonth()->endOfDay()->toDateTimeString();
            } else {
                abort(400, 'Invalid date format. Provide MM-YYYY or an array with two dates.');
            }
        } catch (\Exception $e) {
            abort(400, 'Invalid date input. ' . $e->getMessage());
        }

        # get bsd number
        $bsd_no = $this->bsd_emp_identical ? $employee_no : $this->getBsdNo($employee_no);

        $today = now()->toDateString();

        $logs = EmployeeTimelogs::where('employee_id', $bsd_no)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->orderBy('timestamp')
            ->get();

       

        $employee = EmployeePersonal::where('employee_no', $employee_no)
            ->first()
            ->toArray() ?? [];

        $logs = $this->processLogs($employee, $logs);
        $dtr = $this->computeDTR($employee_no, $logs, $dateInput);

        return [
            'logs' => $dtr['formated_logs'],
            'summary' => $dtr['summary'],
        ];
    }

    /**
     * Computes the Daily Time Record (DTR) for a given employee over a specified date range.
     *
     * This function processes time logs and employee schedule data to determine:
     * - Daily attendance status
     * - Absences, leaves, rest days, holidays (legal/special), and work during holidays
     * - Tardiness, undertime, and overtime (duration and frequency)
     *
     * Input date range can be either:
     * - A string in 'MM-YYYY' format
     * - An array with two dates [start_date, end_date]
     *
     * The function returns both detailed logs and a summary of metrics.
     *
     * @param string $employee_no  The employee number identifier.
     * @param array $logs          Array of daily logs keyed by date (Y-m-d format).
     * @param string|array $dateInput Either a date range array or a 'MM-YYYY' string.
     *
     * @return array [
     *     'formated_logs' => array of daily log entries with computed fields,
     *     'summary' => summary computation
     * ]
     */
    private function computeDTR($employee_no, $logs, $dateInput) 
    {
      
        try {
          
            # Parse date input to get start and end date
            if (is_array($dateInput) && count($dateInput) === 2) {
                $startDate = Carbon::parse($dateInput[0])->startOfDay();
                $endDate = Carbon::parse($dateInput[1])->endOfDay();
            } elseif (is_string($dateInput)) {
                # Handle MM-YYYY format
                $monthCarbon = Carbon::createFromFormat('m-Y', $dateInput);
                $startDate = $monthCarbon->copy()->startOfMonth();
                $endDate = $monthCarbon->copy()->endOfMonth();
            } else {
                # Invalid date input
                abort(400, 'Invalid date input. Provide MM-YYYY or an array with two dates.');
            }
        } catch (\Exception $e) {
          
            # Catch parsing errors
            abort(400, 'Invalid date input format. ' . $e->getMessage());
        }

        // dd($logs);
        # Get today's date
        $today = Carbon::today();
        $formattedLogs = [];

        # current schedule
        $weeklySchedule = $this->getWeeklySchedule($employee_no);
        $countWorkingDays = $this->countWorkingDays($weeklySchedule);

        # Default shift schedule (fallback when a date has no logs or no schedule_id)
        $defaultEmployeeSchedule = null;
        try {
            $defaultEmployeeSchedule = $this->getShiftSchedule($employee_no);
        } catch (\Exception $e) {
            $defaultEmployeeSchedule = $this->getShiftScheduleById(1);
        }
        $isTimelogExempted = $this->isTimelogExemptedEmployee($employee_no);
        $isEmployeeSupport = $this->isSupportShift($defaultEmployeeSchedule);

        # Counters
        $absences = 0;
        $workedDays = 0;
        $restDays = 0;
        $legalHolidays = 0;
        $specialHolidays = 0;
        $workedOnLegalHolidays = 0;
        $workedOnSpecialHolidays = 0;

        $total_tardiness_perminutes = 0;
        $total_tardiness_freq = 0;
        $total_undertime_minutes = 0;
        $total_undertime_freq = 0;
        $total_overtime_perminutes = 0;
        $total_overtime_freq = 0;
        $total_night_shift_minutes = 0;

        $leavesCount = 0;

        $leaves = $this->getTotalLeaves($employee_no, $dateInput);
        $leavesCount += $leaves['count'];
        $leavesCollection = collect($leaves['dates']);

        $offsets = $this->getApprovedOffsets($employee_no, $dateInput);
        $offsetCollection = collect($offsets);
        $offsetDateFromSet = $offsetCollection->pluck('offset_date_from')->filter()->flip();
        $offsetDateToSet = $offsetCollection->pluck('offset_date_to')->filter()->flip();

        $overtime = $this->getTotalOvertime($employee_no, $dateInput);

        $total_overtime_perminutes = $overtime['raw_minutes'];
        $total_overtime_freq = $overtime['count'];

        $overtimeCollection = collect($overtime['dates']);

        for ($date = $startDate->copy(); $date <= $endDate->copy(); $date->addDay()){
            $dateString = $date->toDateString();
            $isFuture = $date->gt($today);
            $remarks = [];
            $isLeave = false;

            $dateLogs = $logs[$dateString] ?? null;

            # schedules - always use employee's assigned schedule for rest day determination
            $weeklySchedule = $this->getWeeklySchedule($employee_no);

            # Per-date shift schedule for support/varying shifts: use shift from that day's log, else employee default
            $employeeSchedule = $defaultEmployeeSchedule;
            if ($dateLogs !== null) {
                $dateShiftId = $dateLogs['shift_id'] ?? $dateLogs['schedule_id'] ?? null;
                if ($dateShiftId) {
                    try {
                        $employeeSchedule = $this->getShiftScheduleById($dateShiftId);
                    } catch (\Exception $e) {
                        // keep default
                    }
                }
            }

            $lunchTracking = filter_var(config('app.lunch_tracking', true), FILTER_VALIDATE_BOOLEAN);
            $isSupportShiftForRules = $isEmployeeSupport || $this->isSupportShift($employeeSchedule);
            $is_break_required = !$isSupportShiftForRules && $lunchTracking && ($employeeSchedule->is_breaktime_required ?? false);

            $date_is_in_logs = isset($logs[$dateString]) && !empty($logs[$dateString]);

            $dayName = strtolower(Carbon::parse($dateString)->format('l'));
            $isScheduled = (int) ($weeklySchedule->$dayName ?? 0) === 1;
            $isOffsetDate = $offsetDateFromSet->has($dateString);
            $isOffsetActivityDate = $offsetDateToSet->has($dateString);
            $hasApprovedOffset = $isOffsetDate || $isOffsetActivityDate;

            if ($isTimelogExempted && $isScheduled && !$isFuture) {
                $workedDays++;
                $formattedLogs[$dateString] = $this->buildExemptedRecord($dateString);
                continue;
            }

            #overtime 
            $matchLeave = $leavesCollection->first(function ($leave) use ($dateString) {
                return $leave->date === $dateString;
            });

            if($matchLeave){
                $isLeave  = true;
            }

            $checkAttendance = $this->checkAttendance(
                $dateString,
                $date_is_in_logs,
                $weeklySchedule,
                $dayName,
                $isFuture,
                $isLeave,
                $isOffsetDate,
                $defaultEmployeeSchedule
            );
            
            if ($checkAttendance['isAbsent']) $absences++;
            if ($checkAttendance['isWorkedDays']) $workedDays++;
            if ($checkAttendance['isRestDays']) $restDays++;
            if ($checkAttendance['isLegalHolidays']) $legalHolidays++;
            if ($checkAttendance['isSpecialHolidays']) $specialHolidays++;
            if ($checkAttendance['isWorkedOnLegalHolidays']) $workedOnLegalHolidays++;
            if ($checkAttendance['isWorkedOnSpecialHolidays']) $workedOnSpecialHolidays++;

            $remarks = array_merge($remarks, $checkAttendance['remarks']);
            
            # leaves
            foreach ($leaves['dates'] as $leaveDate) {

                if($dateString  == $leaveDate->date) {
                    $remarks[] = 'Leave';
                }

            }

            if ($hasApprovedOffset) {
                $remarks[] = 'Offset';
            }

            #overtime 
            $matchedOvertime = $overtimeCollection->first(function ($ot) use ($dateString) {
                return $ot->date === $dateString;
            });

            if (isset($logs[$dateString])) {
                $formattedLogs[$dateString] = $logs[$dateString];

                # aut 
                $aut = $this->undertimeAndTardiness($employee_no, $employeeSchedule, $dateLogs, $dateString, $isEmployeeSupport);

                # Assign the correct values to formatted logs
                $formattedLogs[$dateString]['aut']['tardiness']['minutes'] = $aut['tardiness_minutes'];
                $formattedLogs[$dateString]['aut']['undertime']['minutes'] = $aut['undertime_minutes'];

                # Accumulate totals
                $total_tardiness_perminutes += $aut['tardiness_minutes'];
                $total_tardiness_freq += $aut['tardiness_freq'];
                $total_undertime_minutes += $aut['undertime_minutes'];
                $total_undertime_freq += $aut['undertime_freq'];

                # overtime store
                if ($matchedOvertime) {
                    $start = Carbon::parse($matchedOvertime->start_time);
                    $end = Carbon::parse($matchedOvertime->end_time);
                    $overtimeMinutes = $start->diffInMinutes($end);
                }  else {
                    $overtimeMinutes  = 0;
                }

                $formattedLogs[$dateString]['aut']['overtime']['minutes'] = $overtimeMinutes;

                $nightMinutes = $this->computeNightShiftMinutes($dateString, $dateLogs);
                $total_night_shift_minutes += $nightMinutes;
                $formattedLogs[$dateString]['aut']['night_shift_minutes'] = $nightMinutes;

                # merge aut remarks to global remarks
                $remarks = array_merge($remarks, $aut['remarks']);

                $dayRemarks = array_values(array_unique(array_filter($remarks)));
                if (empty($dayRemarks)) {
                    $dayRemarks = ['Completed'];
                }

                $formattedLogs[$dateString]['remarks'] = array_merge(
                    $formattedLogs[$dateString]['remarks'] ?? [],
                    $dayRemarks
                );

                $formattedLogs[$dateString]['isFuture'] = $isFuture;
                $formattedLogs[$dateString]['is_break_required'] = $is_break_required;
            } else {
                $formattedLogs[$dateString] = [
                    'bsd_no' => null,
                    'clock_in' => null,
                    'lunch_in' => null,
                    'lunch_out' => null,
                    'clock_out' => null,
                    'origin' => null,
                    'aut' => null,
                    'total_aut' => null,
                    'employee_no' => null,
                    'workOnHoliday' => null,
                    'isFuture' => $isFuture,
                    'remarks' => array_values(array_unique(array_filter($remarks))),
                    'is_break_required' => $is_break_required,
                ];
            }
        }

        $summary = [
            'leaves'                        => $leavesCount,
            'worked_days'                   => $workedDays,
            'absences'                      => $absences,
            'overtime'                      => $total_overtime_freq,
            'overtime_minues'               => $total_overtime_perminutes,
            'total_days_of_work'            => $workedDays + $absences,
            'less_aut'                      => 0,
            'tardiness_freq'                => $total_tardiness_perminutes,
            'tardiness'                     => $total_tardiness_freq,
            'undertime_freq'                => $total_undertime_freq,
            'undertime'                     => $total_undertime_minutes,
            'rest_days'                     => $restDays,
            'legal_hol'                     => $legalHolidays,
            'worked_on_legal_holidays'      => $workedOnLegalHolidays,
            'special_hol'                   => $specialHolidays,
            'worked_on_special_holidays'    => $workedOnSpecialHolidays,
            'workingDaysPerWeek'            => $countWorkingDays,
            'night_shift_minutes'           => $total_night_shift_minutes
        ];

        $data  =  [
            'formated_logs' => $formattedLogs,
            'summary' => $summary
        ];

        return $data;

    }

    /**
     * Retrieve the BSD number of a specific employee.
     *
     * This function queries the `employee_information` table and returns
     * the `bsd_no` value associated with the given employee number.
     *
     * @param  string  $employee_no  The employee number.
     * @return string|null           The BSD number, or null if not found.
     */
    public function getBsdNo($employee_no)
    {
        return DB::table('employee_information')
                ->where('employee_no', $employee_no)
                ->value('bsd_no');
    }

    /**
     * Retrieve the assigned shift schedule for a given employee number.
     *
     * This function queries the `shift_schedule` table joined with the `employee_information` table
     * using the employee number to find the associated shift. It throws an exception if no shift is assigned.
     *
     * @param  string  $employeeNo  The employee number.
     * @return object               The shift schedule record.
     * @throws \Exception           If no shift schedule is assigned to the employee.
     */
    public function getShiftSchedule($employeeNo)
    {
        $employee_shift = DB::table('shift_schedule')
                        ->leftJoin('employee_information', 'shift_schedule.id', '=', 'employee_information.shift_id')
                        ->select('shift_schedule.*')
                        ->where('employee_information.employee_no', $employeeNo)
                        ->first();

        if (!$employee_shift) {
            throw new \Exception("No shift schedule assigned", 1);
        }

        return $employee_shift;
    }

    /**
     * Retrieve the shift schedule based on the given shift ID.
     *
     * This function queries the `shift_schedule` table using the provided shift ID
     * and returns the corresponding record. If no matching record is found,
     * an exception is thrown.
     *
     * @param  int  $shift_id  The ID of the shift schedule.
     * @return object          The shift schedule record.
     * @throws \Exception      If no shift schedule is found for the given ID.
     */
    public function getShiftScheduleById($shift_id)
    {
        $shift = DB::table('shift_schedule')
                        ->where('id', $shift_id)
                        ->first();

        if (!$shift) {
            throw new \Exception("No shift schedule assigned", 1);
        }

        return $shift;
    }

    /**
     * Whether the given shift schedule is a support shift (only 8 hours required for undertime).
     * Uses shift_schedule.id for reliable detection (e.g. Support Shift id = 4).
     *
     * @param  object  $schedule  Shift schedule record (from shift_schedule table).
     * @return bool
     */
    private function isSupportShift($schedule): bool
    {
        if (!$schedule) {
            return false;
        }
        $supportShiftId = (int) config('app.support_shift_schedule_id', 4);
        if (isset($schedule->id) && (int) $schedule->id === $supportShiftId) {
            return true;
        }
        $duration = strtolower(trim($schedule->shift_duration ?? ''));
        $name = $schedule->name ?? '';
        return $duration === 'support' || stripos($name, 'support') !== false;
    }

    /**
     * Retrieve the weekly schedule assigned to a specific employee.
     *
     * This function joins the `employee_schedules` and `employee_information` tables
     * using the schedule ID, and fetches the weekly schedule for the provided employee number.
     * Throws an exception if no schedule is found.
     *
     * @param  string  $employee_no  The employee number.
     * @return object                The weekly schedule record.
     * @throws \Exception            If no schedule is assigned to the employee.
     */
    public function getWeeklySchedule($employee_no)
    {
        $weeklySchedule = DB::table('employee_schedules')
            ->leftJoin('employee_information', 'employee_schedules.id', '=', 'employee_information.schedule_id')
            ->select('employee_schedules.*')
            ->where('employee_information.employee_no', $employee_no)
            ->first();

        if (!$weeklySchedule) {
            $weeklySchedule = DB::table('employee_schedules')->where('name', 'Default Schedule')->first();
        }
        if (!$weeklySchedule) {
            throw new \Exception("No Employee Schedule", 1);
        }

        return $weeklySchedule;
    }

    /**
     * Retrieve the weekly schedule based on the given schedule ID.
     *
     * This function queries the `employee_schedules` table using the provided schedule ID
     * and returns the corresponding weekly schedule record. If no record is found,
     * an exception is thrown.
     *
     * @param  int  $schedule_id  The ID of the employee's weekly schedule.
     * @return object             The weekly schedule record.
     * @throws \Exception         If no schedule is found for the given ID.
     */
    public function getWeeklyScheduleById($schedule_id)
    {
        $weeklySchedule = $schedule_id
            ? DB::table('employee_schedules')->where('id', $schedule_id)->first()
            : null;

        if (!$weeklySchedule) {
            $weeklySchedule = DB::table('employee_schedules')->where('name', 'Default Schedule')->first();
        }
        if (!$weeklySchedule) {
            throw new \Exception("No Employee Schedule", 1);
        }

        return $weeklySchedule;
    }

    /**
     * Calculate the total number of leaves taken by an employee within a given period.
     *
     * Supports two formats for the date input:
     * - A string in "MM-YYYY" format to filter leaves within a specific month and year.
     * - An array with two elements [startDate, endDate] to filter leaves within a date range.
     *
     * Only leaves with a status of "approved" are considered.
     * Duration is calculated as:
     * - 1.0 for "wholeday"
     * - 0.5 for others (assumed to be "halfday")
     *
     * @param  string       $employeeNo   The employee number.
     * @param  string|array $dateInput    The date input (either "MM-YYYY" or [startDate, endDate]).
     * @return array                      Returns an array with:
     *                                    - 'count': Number of leave records
     *                                    - 'dates': Collection of leave dates
     *                                    - 'duration': Total duration of leave in days
     */
    private function getTotalLeaves($employeeNo, $dateInput)
    {
        $WHOLEDAY = 1;
        $HALFDAY = 0.5;
        $DURATION = 0;

        $query = DB::table('employee_leave_dates')
            ->leftJoin('employee_leave', 'employee_leave_dates.employee_leave_id', '=', 'employee_leave.id')
            ->select('employee_leave.duration', 'employee_leave_dates.date')
            ->where('employee_leave.employee_no', $employeeNo)
            ->where('employee_leave.status', 'approved');

        # Handle MM-YYYY
        if (is_string($dateInput) && preg_match('/^\d{2}-\d{4}$/', $dateInput)) {
            [$month, $year] = explode('-', $dateInput);
            $query->whereMonth('employee_leave_dates.date', (int) $month)
                ->whereYear('employee_leave_dates.date', (int) $year);

        # Handle date range
        } elseif (is_array($dateInput) && count($dateInput) === 2) {
            [$startDate, $endDate] = $dateInput;
            $query->whereBetween('employee_leave_dates.date', [$startDate, $endDate]);
        }

        $leaveDates = $query->get();

        foreach ($leaveDates as $date) {
            if ($date->duration === 'wholeday') {
                $DURATION += $WHOLEDAY;
            } else {
                $DURATION += $HALFDAY;
            }
        }

        return [
            'count' => $leaveDates->count(),
            'dates' => $leaveDates,
            'duration' => $DURATION,
        ];
    }

    /**
     * Check and determine the attendance status and related remarks for a specific date.
     *
     * This function evaluates if the employee is scheduled to work, is on leave, worked during a holiday,
     * or is absent based on the given date, schedule, and logs. It also identifies if the date falls on 
     * a rest day or a holiday (legal or special).
     *
     * @param  string   $dateString       The date to check (format: YYYY-MM-DD).
     * @param  bool     $date_is_in_logs  Whether there is an attendance log for the date.
     * @param  object   $weeklySchedule   The weekly schedule object for the employee.
     * @param  string   $dayName          The name of the day (e.g., 'monday', 'tuesday').
     * @param  bool     $isFuture         Whether the date is in the future.
     * @param  bool     $isLeave          Whether the employee is on approved leave that day.
     * @param  bool     $isOffset         Whether the date has an approved offset (offset date).
     * @param  object   $defaultShift     Optional default shift schedule (for support shift: do not mark absent for now).
     *
     * @return array                      Returns an array with the following keys:
     *                                    - 'remarks': array of string remarks for the date
     *                                    - 'isAbsent': bool
     *                                    - 'isWorkedDays': bool
     *                                    - 'isRestDays': bool
     *                                    - 'isLegalHolidays': bool
     *                                    - 'isSpecialHolidays': bool
     *                                    - 'isWorkedOnLegalHolidays': bool
     *                                    - 'isWorkedOnSpecialHolidays': bool
     */
    private function checkAttendance($dateString, $date_is_in_logs, $weeklySchedule, $dayName, $isFuture, $isLeave, $isOffset = false, $defaultShift = null)
    {
        # Skip if today
        if (Carbon::parse($dateString)->isToday()) {
            return [
                'remarks' => [],
                'isAbsent' => false,
                'isWorkedDays' => false,
                'isRestDays' => false,
                'isLegalHolidays' => false,
                'isSpecialHolidays' => false,
                'isWorkedOnLegalHolidays' => false,
                'isWorkedOnSpecialHolidays' => false,
            ];
        }
        
        $isScheduled = $weeklySchedule->$dayName == 1;
        $ownRemarks = [];

        $absent = false;
        $workedDays = false;
        $restDays = false;
        $legalHolidays = false;
        $specialHolidays = false;
        $workedOnLegalHolidays = false;
        $workedOnSpecialHolidays = false;

        # Holiday check
        $holiday = $this->getHolidayByDate($dateString);

        $legalHolidays = false;
        $specialHolidays = false;

        $isHoliday = false;
        $isLegalHoliday = false;
        $isSpecialHoliday = false;

        if ($holiday) {
            $isHoliday = true;
            $type = strtolower($holiday->type);

           switch ($type) {
                case 'regular':
                    $legalHolidays = true;
                    $isLegalHoliday = true;

                    // ✅ ADD REGULAR HOLIDAY REMARK
                    $ownRemarks[] = 'Regular Holiday';
                    break;
                case 'special-non-working':
                    $legalHolidays = true;
                    $isLegalHoliday = true;

                    $ownRemarks[] = 'Special Holiday';
                    break;
                case 'special-working':
                case 'company':
                    $specialHolidays = true;
                    $isSpecialHoliday = true;
                    break;
            }
        }

        $arrayWeeklySchedule = (array) $weeklySchedule;

        if (!$arrayWeeklySchedule[$dayName] && !$isHoliday) {
            $dayRemarkKey = $dayName . '_remarks';
            $restDays = true;
            $ownRemarks[] = $arrayWeeklySchedule[$dayRemarkKey];
        }

        if ($date_is_in_logs) {
            if ($isLegalHoliday) {
                $workedOnLegalHolidays = true;
                $ownRemarks[] = 'Legal Hol.';
            } elseif ($isSpecialHoliday) {
                $workedOnSpecialHolidays = true;
                $ownRemarks[] = 'Special Hol.';
            }elseif ($isHoliday) {
                $ownRemarks[] = 'Holiday Work';
            }
            $workedDays = true;
        } elseif ($isScheduled && !$isHoliday && !$isFuture && !$isLeave && !$isOffset) {
            // Support shift: do not mark absent for now (per requirement)
            $skipAbsent = $defaultShift && $this->isSupportShift($defaultShift);
            if (!$skipAbsent) {
                $absent = true;
                $ownRemarks[] = 'Absent';
            }
        }

        $data = [
            'remarks' => $ownRemarks,
            'isAbsent' => $absent,
            'isWorkedDays' => $workedDays,
            'isRestDays' => $restDays,
            'isLegalHolidays' => $legalHolidays,
            'isSpecialHolidays' => $specialHolidays,
            'isWorkedOnLegalHolidays' => $workedOnLegalHolidays,
            'isWorkedOnSpecialHolidays' => $workedOnSpecialHolidays,
        ];

        return $data;
    }

    /**
     * Count the number of working days in a weekly schedule.
     *
     * This function iterates through each day of the week and counts how many days
     * are marked as working days (value equals 1) in the provided weekly schedule object.
     *
     * @param  object  $weeklySchedule  The weekly schedule object containing day properties (e.g., monday, tuesday, ...).
     * @return int                      The number of working days in the week.
     */
    private function countWorkingDays($weeklySchedule)
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        $workingDays = 0;

        foreach ($days as $day) {
            if (property_exists($weeklySchedule, $day) && (int) $weeklySchedule->$day === 1) {
                $workingDays++;
            }
        }

        return $workingDays;
    }

    /**
     * Retrieve holiday information for a specific date.
     *
     * This function queries the `holidays` table and returns the holiday record
     * that matches the provided date, excluding deleted entries (`isDeleted = false`).
     *
     * @param  string  $date  The date to check (format: YYYY-MM-DD).
     * @return object|null    The holiday record if found, or null if none exists.
     */
   /* private function getHolidayByDate($date)
    {
        return DB::table('holidays')
            ->where('isDeleted', false)
            ->where('date', $date)
            ->first();
    }*/

    private function getHolidayByDate($date)
    {
        $monthDay = Carbon::parse($date)->format('m-d');

        $holiday = DB::table('holidays')
            ->where('isDeleted', false)
            ->where('date', $monthDay)
            ->first();

        return $holiday;
    }


    /**
     * Check if an employee has approved leave on a specific date.
     *
     * This function queries the `employee_leave_dates` and `employee_leave` tables
     * to retrieve leave entries that match the given employee number and date,
     * and have a status of "approved".
     *
     * It calculates the total duration of leave taken on that date:
     * - 1.0 for "wholeday"
     * - 0.5 for other durations (assumed to be "halfday")
     *
     * @param  string  $employee_no  The employee number.
     * @param  string  $date         The specific date to check (format: YYYY-MM-DD).
     * @return array                 Returns an array with:
     *                               - 'count': Number of leave records
     *                               - 'dates': Collection of leave records
     *                               - 'duration': Total leave duration for the date
     */
    private function checkLeave($employee_no, $date)
    {
        $WHOLEDAY = 1;
        $HALFDAY = 0.5;
        $DURATION = 0;

        $query = DB::table('employee_leave_dates')
            ->leftJoin('employee_leave', 'employee_leave_dates.employee_leave_id', '=', 'employee_leave.id')
            ->select('employee_leave.duration', 'employee_leave_dates.date')
            ->where('employee_leave_dates.date', $date)
            ->where('employee_leave.employee_no', $employee_no)
            ->where('employee_leave.status', 'approved');

        $leaveDates = $query->get();

        foreach ($leaveDates as $date) {
            if ($date->duration === 'wholeday') {
                $DURATION += $WHOLEDAY;
            } else {
                $DURATION += $HALFDAY;
            }
        }

        return [
            'count' => $leaveDates->count(),
            'dates' => $leaveDates,
            'duration' => $DURATION,
        ];
    }

    /**
     * Calculate undertime and tardiness for a specific employee on a given date.
     *
     * This function analyzes an employee's time logs against their scheduled shift
     * to determine:
     * - Tardiness: if the employee clocked in after the scheduled start time
     * - Undertime: if the employee clocked out before the scheduled end time
     *
     * If the schedule requires a break, it also checks for missing break logs and flags discrepancies.
     * Logs are written for late arrivals and undertimes.
     *
     * @param  string  $employee_no        The employee number.
     * @param  object  $employeeSchedule   The schedule object for the employee.
     * @param  array   $log                Array of time logs with keys: clock_in, lunch_out, lunch_in, clock_out.
     * @param  string  $date               The date being evaluated (format: YYYY-MM-DD).
     *
     * @return array                       Returns an array with:
     *                                     - 'tardiness_minutes': Total minutes late
     *                                     - 'tardiness_freq': Count of tardiness instances
     *                                     - 'undertime_minutes': Total minutes of undertime
     *                                     - 'undertime_freq': Count of undertime instances
     *                                     - 'remarks': Array of remarks (e.g. Late, Undertime, Discrepancy)
     *                                     - 'is_break_required': Whether break time was required on that day
     */
    private function undertimeAndTardiness($employee_no, $employeeSchedule, $log, $date, $forceSupportShift = false)
    {
        $TARDINESS_MINUTES = 0;
        $TARDINESS_FREQ = 0;

        $UNDERTIME_MINUTES = 0;
        $UNDERTIME_FREQ = 0;

        $ownRemark = [];

        $lunchTracking = filter_var(config('app.lunch_tracking', true), FILTER_VALIDATE_BOOLEAN);
        $isSupportShift = $forceSupportShift || $this->isSupportShift($employeeSchedule);
        $isBreakRequired = !$isSupportShift && $lunchTracking && ($employeeSchedule->is_breaktime_required ?? false);

        if ($isBreakRequired) {
            $timeIn = $log['clock_in'];
            $breakOut = $log['lunch_out'];
            $breakIn = $log['lunch_in'];
            $timeOut = $log['clock_out'];
            if (!$timeIn || !$timeOut || !$breakOut || !$breakIn) {
                $ownRemark[] = 'Discrepancy';
            }
        } else {
            $timeIn = $log['clock_in'];
            $timeOut = $log['clock_out'];
            $breakOut = $breakIn = null;
            if ($timeIn == null || $timeOut == null) {
                $ownRemark[] = 'Discrepancy';
            }
        }

        // Incomplete/lost timelogs: do not compute AUT (avoids huge undertime e.g. 26h when clock_out is missing)
        $timeInEmpty = $timeIn === null || trim((string) $timeIn) === '';
        $timeOutEmpty = $timeOut === null || trim((string) $timeOut) === '';
        if ($timeInEmpty || $timeOutEmpty) {
            return [
                'tardiness_minutes' => 0,
                'tardiness_freq' => 0,
                'undertime_minutes' => 0,
                'undertime_freq' => 0,
                'remarks' => $ownRemark,
                'is_break_required' => $isBreakRequired,
            ];
        }

        $firstLog = Carbon::parse("{$date} {$timeIn}");
        $lastLog = Carbon::parse("{$date} {$timeOut}");

        # Overnight shift: clock-out may be next calendar day (e.g. 02:00)
        if ($lastLog->lessThan($firstLog)) {
            $lastLog = (clone $lastLog)->addDay();
        }

        # Support shift is output-based (8h required) and should not be marked late.

        # Get scheduled shift only for non-support shifts.
        $scheduledIn = null;
        $scheduledOut = null;
        if (!$isSupportShift) {
            [$scheduledIn, $scheduledOut, $scheduledBreakIn, $scheduledBreakOut] = $this->getScheduledInOut($employeeSchedule, $date, $firstLog);

            if (!$scheduledIn || !$scheduledOut) {
                Log::warning("Missing schedule for {$employee_no} on {$date}");
                return [
                    'tardiness_minutes' => 0,
                    'tardiness_freq' => 0,
                    'undertime_minutes' => 0,
                    'undertime_freq' => 0,
                    'remarks' => $ownRemark,
                    'is_break_required' => $isBreakRequired,
                ];
            }
        }

        # Tardiness
        if (!$isSupportShift && $firstLog->greaterThan($scheduledIn)) {
            $minutesLate = $firstLog->diffInMinutes($scheduledIn);
            $TARDINESS_MINUTES += $minutesLate;
            $TARDINESS_FREQ++;
            $ownRemark[] = 'Late';
        }

        # Undertime: for support shift only, required hours = 8 from clock-in; otherwise use scheduled end time
        $requiredOut = $isSupportShift
            ? (clone $firstLog)->addHours(8)
            : $scheduledOut;

        if ($lastLog->lessThan($requiredOut)) {
            $minutesUndertime = $requiredOut->diffInMinutes($lastLog);
            $UNDERTIME_MINUTES += $minutesUndertime;
            $UNDERTIME_FREQ++;
            $ownRemark[] = 'Undertime';
        }

        return [
            'tardiness_minutes' => $TARDINESS_MINUTES,
            'tardiness_freq' => $TARDINESS_FREQ,
            'undertime_minutes' => $UNDERTIME_MINUTES,
            'undertime_freq' => $UNDERTIME_FREQ,
            'remarks' => $ownRemark,
            'is_break_required' => $isBreakRequired,
        ];
    }

    /**
     * Get approved offset applications for date range/month.
     *
     * Offset meaning:
     * - offset_date_from: date being offset (should not be absent when approved)
     * - offset_date_to: activity/work date used to compensate
     */
    private function getApprovedOffsets($employeeNo, $dateInput)
    {
        $query = DB::table('employee_offset_applications')
            ->select('offset_date_from', 'offset_date_to')
            ->where('employee_no', $employeeNo)
            ->where('status', 'approved')
            ->where('isDeleted', false);

        if (is_array($dateInput) && count($dateInput) === 2) {
            $startDate = Carbon::parse($dateInput[0])->toDateString();
            $endDate = Carbon::parse($dateInput[1])->toDateString();
            $query->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('offset_date_from', [$startDate, $endDate])
                  ->orWhereBetween('offset_date_to', [$startDate, $endDate]);
            });
        } elseif (is_string($dateInput)) {
            $monthCarbon = Carbon::createFromFormat('m-Y', $dateInput);
            $month = (int) $monthCarbon->format('m');
            $year = (int) $monthCarbon->format('Y');
            $query->where(function ($q) use ($month, $year) {
                $q->where(function ($sub) use ($month, $year) {
                    $sub->whereYear('offset_date_from', $year)->whereMonth('offset_date_from', $month);
                })->orWhere(function ($sub) use ($month, $year) {
                    $sub->whereYear('offset_date_to', $year)->whereMonth('offset_date_to', $month);
                });
            });
        }

        return $query->get();
    }

    /**
     * Get the scheduled time-in, time-out, break-out, and break-in based on the given schedule.
     *
     * - For hybrid setup:
     *   - Uses 'latest_in' as the basis for time-in.
     *   - Adjusts time-in to actual log-in time if earlier.
     *   - Calculates time-out by adding work hours (+1 buffer hour) to time-in.
     * - For regular setup:
     *   - Uses 'start_shift' and 'end_shift' directly for time-in and time-out.
     * 
     * @param  object       $schedule   The schedule object containing work setup and time configurations.
     * @param  string|null  $date       The date to apply for the schedule (format: Y-m-d).
     * @param  string|null  $firstLog   The first actual log-in time (optional).
     * @return array                    An array with [time_in, time_out, break_out, break_in] or nulls if unavailable.
     */
    private function getScheduledInOut($schedule, $date = null, $firstLog = null)
    {
        if ($schedule->work_setup === 'hybrid') {
            $in = Carbon::parse("{$date} {$schedule->latest_in}");

            if ($firstLog) {
                $firstLogTime = Carbon::parse($firstLog);
                if ($firstLogTime->lessThan($in)) {
                    $in = $firstLogTime;
                }
            }

            $out = (clone $in)->addHours($schedule->work_hours + 1);

            $breakOut = Carbon::parse("{$date} {$schedule->break_out}");
            $breakIn = Carbon::parse("{$date} {$schedule->break_in}");

            return [$in, $out, $breakOut, $breakIn];
        }

        if ($schedule->start_shift && $schedule->end_shift) {
            return [
                Carbon::parse("{$date} {$schedule->start_shift}"),
                Carbon::parse("{$date} {$schedule->end_shift}"),
                Carbon::parse("{$date} {$schedule->break_out}"),
                Carbon::parse("{$date} {$schedule->break_in}")
            ];
        }

        return [null, null, null, null];
    }

    /**
     * Compute night shift minutes (work between 10pm–6am) for a given date's logs.
     */
    private function computeNightShiftMinutes(string $dateString, array $dateLogs): int
    {
        $timelogs = $dateLogs['timelogs'] ?? [];
        if (empty($timelogs)) {
            $clockIn = $dateLogs['clock_in'] ?? null;
            $clockOut = $dateLogs['clock_out'] ?? null;
            if (!$clockIn || !$clockOut) {
                return 0;
            }
            $workStart = Carbon::parse("{$dateString} {$clockIn}");
            $workEnd = Carbon::parse("{$dateString} {$clockOut}");
            if ($workEnd->lt($workStart)) {
                $workEnd->addDay();
            }
        } else {
            $timestamps = collect($timelogs)->pluck('timestamp')->filter();
            if ($timestamps->isEmpty()) {
                return 0;
            }
            $workStart = $timestamps->min();
            $workEnd = $timestamps->max();
            if (!$workStart instanceof Carbon) {
                $workStart = Carbon::parse($workStart);
            }
            if (!$workEnd instanceof Carbon) {
                $workEnd = Carbon::parse($workEnd);
            }
        }

        $nightStart = Carbon::parse("{$dateString} 22:00");
        $nightEnd = Carbon::parse("{$dateString} 06:00")->addDay();
        $overlapStart = $workStart->greaterThan($nightStart) ? $workStart : $nightStart;
        $overlapEnd = $workEnd->lessThan($nightEnd) ? $workEnd : $nightEnd;
        if ($overlapEnd->lte($overlapStart)) {
            return 0;
        }

        return (int) $overlapStart->diffInMinutes($overlapEnd);
    }

    /**
     * Get the total approved overtime of an employee for a specific date, month-year, or date range.
     *
     * @param string $employeeNo  Employee number to filter overtime logs.
     * @param string|array $dateInput Either:
     *                                - a string in "MM-YYYY" format for monthly query, or
     *                                - an array with two dates [startDate, endDate] for range query.
     * 
     * @return array {
     *     @type int    $count        Number of approved overtime entries.
     *     @type object $dates        Collection of overtime entries (date, start_time, end_time).
     *     @type string $total_hours  Total time in "X hr(s) Y min(s)" format.
     *     @type int    $raw_minutes  Total overtime in minutes.
     * }
     */
    private function getTotalOvertime($employeeNo, $dateInput) 
    {
        $query = DB::table('employee_atro')
            ->select('date', 'start_time', 'end_time')
            ->where('employee_no', $employeeNo)
            ->where('status', 'approved');

        if (is_string($dateInput) && preg_match('/^\d{2}-\d{4}$/', $dateInput)) {
            [$month, $year] = explode('-', $dateInput);
            $query->whereMonth('date', (int) $month)
                ->whereYear('date', (int) $year);

        } elseif (is_array($dateInput) && count($dateInput) === 2) {
            [$startDate, $endDate] = $dateInput;
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $overtimes = $query->get();
        $totalMinutes = 0;

        foreach ($overtimes as $overtime) {
            try {
                $start = Carbon::parse($overtime->start_time);
                $end = Carbon::parse($overtime->end_time);

                $minutes = $end->diffInMinutes($start);
                $totalMinutes += $minutes;
            } catch (\Exception $e) {
                continue;
            }
        }

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return [
            'count' => $overtimes->count(),
            'dates' => $overtimes,
            'total_hours' => "{$hours} hr(s) {$minutes} min(s)",
            'raw_minutes' => $totalMinutes,
        ];
    }  
    
    /**
     * Process raw log entries for an employee by grouping them per date,
     * assigning time-in/time-out, and attaching related log metadata
     * such as location, image, and accomplishment.
     *
     * @param  object  $employee  The employee data object.
     * @param  array   $logs      The array of log entries.
     * @return array              Processed logs grouped by date with time records and details.
     */
    private function processLogs($employee, $logs)
    {
        $groupedLogs = [];

        foreach ($logs as $log) {
            $date = Carbon::parse($log->timestamp)->toDateString();
            $status = $log->status ?? $log->status1 ?? 0;
            $groupedLogs[$date][] = [
                'timestamp' => Carbon::parse($log->timestamp),
                'status' => (int) $status,
                'shift_id' => $log->shift_id ?? 1,
                'schedule_id' => $log->schedule_id ?? 1,
                'isWeb' => $log->isWeb,
                'captured_image' => $log->captured_image,
                'captured_location' => $log->captured_location,
                'accomplishment' => $log->accomplishment,
            ];
        }

        $processedLogs = [];

        foreach ($groupedLogs as $date => $entries) {
            $entriesSorted = collect($entries)->sortBy('timestamp')->values()->all();
            $firstEntry = $entriesSorted[0];

            $record = $this->initializeRecord($employee, $firstEntry, $date);
            $this->assignTimestampsByStatus($record, $entriesSorted);

            $record['timelogs'] = [];

            foreach ($entriesSorted as $entry) {
                $record['timelogs'][] = [
                    'shift_id' => $entry['shift_id'],
                    'schedule_id' => $entry['schedule_id'],
                    'timestamp' => $entry['timestamp'],
                    'isWeb' => $entry['isWeb'],
                    'captured_image' => $entry['captured_image'],
                    'captured_location' => $entry['captured_location'],
                    'accomplishment' => $entry['accomplishment'],
                ];
            }

            $processedLogs[$date] = $record;
        }

        $this->mergeCrossMidnightClockOuts($processedLogs);

        return $processedLogs;
    }

    private function isTimelogExemptedEmployee(string $employeeNo): bool
    {
        return (bool) DB::table('employee_information')
            ->where('employee_no', $employeeNo)
            ->value('is_timelog_exempted');
    }

    private function buildExemptedRecord(string $dateString): array
    {
        return [
            'bsd_no' => null,
            'clock_in' => Carbon::parse("{$dateString} 09:00:00")->format('h:i A'),
            'lunch_in' => null,
            'lunch_out' => null,
            'clock_out' => Carbon::parse("{$dateString} 18:00:00")->format('h:i A'),
            'origin' => null,
            'aut' => [
                'tardiness' => ['minutes' => 0, 'reason' => null],
                'undertime' => ['minutes' => 0, 'reason' => null],
                'overtime' => ['minutes' => 0, 'reason' => null],
                'night_shift_minutes' => 0,
            ],
            'total_aut' => 0,
            'employee_no' => null,
            'workOnHoliday' => null,
            'isFuture' => false,
            'remarks' => ['Exempted'],
            'is_break_required' => false,
            'timelogs' => [],
        ];
    }

    /**
     * When a day has only 1 log in early morning (00:00-05:59) and the previous day has clock_in
     * without clock_out, treat that log as clock_out for the previous day (past-midnight shift).
     */
    private function mergeCrossMidnightClockOuts(array &$processedLogs): void
    {
        $dates = array_keys($processedLogs);
        sort($dates);

        foreach ($dates as $i => $date) {
            if ($i === 0) continue;

            $record = &$processedLogs[$date];
            $prevDate = $dates[$i - 1];
            $prevRecord = $processedLogs[$prevDate] ?? null;

            if (!$prevRecord || $prevRecord['clock_out'] !== null) continue;

            $timestamps = collect($record['timelogs'] ?? [])->pluck('timestamp')->filter()->sort()->values();
            if ($timestamps->count() !== 1) continue;

            $ts = $timestamps->first();
            $hour = $ts instanceof Carbon ? (int) $ts->format('H') : (int) Carbon::parse($ts)->format('H');
            if ($hour < 0 || $hour >= 6) continue;

            $prevRecord['clock_out'] = $ts instanceof Carbon ? $ts->format('h:i A') : Carbon::parse($ts)->format('h:i A');
            $prevRecord['timelogs'][] = [
                'timestamp' => $ts,
                'shift_id' => $record['timelogs'][0]['shift_id'] ?? null,
                'schedule_id' => $record['timelogs'][0]['schedule_id'] ?? null,
                'isWeb' => $record['timelogs'][0]['isWeb'] ?? null,
                'captured_image' => $record['timelogs'][0]['captured_image'] ?? null,
                'captured_location' => $record['timelogs'][0]['captured_location'] ?? null,
                'accomplishment' => $record['timelogs'][0]['accomplishment'] ?? null,
            ];
            unset($processedLogs[$date]);
        }
    }

    /**
     * Initializes a daily time record array for an employee.
     *
     * @param  mixed  $employee  The employee object or array containing bsd_no or employee_no.
     * @param  string $date      The date of the record in 'Y-m-d' format.
     *
     * @return array  Initialized time record structure with default values.
     */
    private function initializeRecord($employee, $first_log, $date)
    {
        return [
            'bsd_no' => is_array($employee) ? ($employee['bsd_no'] ?? $employee['employee_no']) : ($employee->bsd_no ?? $employee->employee_no),
            'clock_in' => null,
            'lunch_in' => null,
            'lunch_out' => null,
            'clock_out' => null,
            'shift_id' => $first_log['shift_id'],
            'schedule_id' => $first_log['schedule_id'],
            'origin' => null,
            'date' => $date,
            'aut' => [
                'tardiness' => ['minutes' => 0, 'reason' => null],
                'undertime' => ['minutes' => 0, 'reason' => null],
                'overtime' => ['minutes' => 0, 'reason' => null],
            ],
            'total_aut' => 0,
            'timelogs' => []
        ];
    }

    /**
     * Assigns clock-in, lunch-in/out, clock-out using the stored status (0=in, 1=out).
     * Prevents duplicate clock-ins from being displayed as clock-out when timestamps are identical.
     *
     * Expected sequence: 0,1,0,1 (clock_in, lunch_out, lunch_in, clock_out) or 0,1 (clock_in, clock_out).
     *
     * @param array $record  Reference to the time log record to be updated
     * @param array $entries Sorted entries with 'timestamp' and 'status' keys
     * @return void
     */
    private function assignTimestampsByStatus(&$record, array $entries): void
    {
        $ins = [];
        $outs = [];
        foreach ($entries as $e) {
            $ts = $e['timestamp'] instanceof Carbon ? $e['timestamp'] : Carbon::parse($e['timestamp']);
            $status = (int) ($e['status'] ?? 0);
            if ($status === 0) {
                $ins[] = $ts;
            } else {
                $outs[] = $ts;
            }
        }

        $fmt = fn($t) => $t instanceof Carbon ? $t->format('h:i A') : Carbon::parse($t)->format('h:i A');

        $record['clock_in'] = isset($ins[0]) ? $fmt($ins[0]) : null;
        $record['clock_out'] = null;
        $record['lunch_out'] = null;
        $record['lunch_in'] = null;

        if (count($ins) >= 2 && count($outs) >= 2) {
            $record['lunch_out'] = $fmt($outs[0]);
            $record['lunch_in'] = $fmt($ins[1]);
            $record['clock_out'] = $fmt($outs[1]);
        } elseif (count($outs) >= 1) {
            $record['clock_out'] = $fmt($outs[count($outs) - 1]);
        }
    }



}   