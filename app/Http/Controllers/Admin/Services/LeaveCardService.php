<?php

namespace App\Http\Controllers\Admin\Services;

use App\Models\EmployeeLeave;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\EmployeeLeaveCard;
use App\Models\EmployeeLeaveDates;
use App\Models\Holiday;
use App\Models\LeaveCredits;
use App\Models\LeaveType;
use Carbon\Carbon;
use DateTime;

class LeaveCardService extends Controller
{

    public function init(string $employee_no, string $action, $data = null) {

        if($employee_no) {

            if($action == 'firstime') {
                $this->triggerSLVLFirst($data);
            }

            if($action == 'leave_approval') {

                $this->triggerSLVL($data);
            }
        }
    }

    public function triggerSLVL($data)
    {
        if (!$data) return;

            $employee_no = $data->employee_no;
            $currentYear = now()->year;

            $leaveType = LeaveType::find($data->leave_id);
            $leaveCode = strtolower($leaveType->code ?? '');
        
            $leave_info = EmployeeLeave::with('dates')
                    ->find($data->id);

            $leave_info = array_merge(
                $leave_info->toArray(),
                [
                    'dates' => EmployeeLeaveDates::where('employee_leave_id', $data->id)
                        ->pluck('date')
                        ->map(fn($d) => Carbon::parse($d)->toDateString())
                        ->toArray()
                ]
            );

            if (!$leave_info) return;

            $latestCard = EmployeeLeaveCard::where('employee_no', $employee_no)
                ->where('year', $currentYear)
                ->orderBy('year', 'asc')
                ->get()
                ->last();

            $leaveCardBalance = match (true) {
                in_array($leaveCode, ['vl', 'sl']) => (float) ($latestCard?->{$leaveCode . '_bal'} ?? 0),
                default => (float) ($latestCard?->vl_bal ?? 0),
            };


            // Build leave months from actual leave dates
            $leaveMonths = $this->processLeaveDates($leave_info, $leaveCode, $leaveCardBalance);

            // Latest cards to update
            $latestLeaveCard = EmployeeLeaveCard::where('employee_no', $employee_no)
                ->where('year', $currentYear)
                ->orderBy('year', 'asc')
                ->get()
                ->toArray();

            $mapped = array_map(function ($leaveCard) use ($leaveMonths, $leaveCode) {
                $match = collect($leaveMonths)->firstWhere('month', $leaveCard['period']);
                
                if ($match) {
                    if (in_array($leaveCode, ['vl', 'sl'])) {
                        $leaveCard[$leaveCode . '_aut_w_pay'] =
                            floatval($leaveCard[$leaveCode . '_aut_w_pay'] ?? 0) +
                            floatval($match[$leaveCode . '_aut_w_pay'] ?? 0);

                        $leaveCard[$leaveCode . '_aut_wo_pay'] =
                            floatval($leaveCard[$leaveCode . '_aut_wo_pay'] ?? 0) +
                            floatval($match[$leaveCode . '_aut_wo_pay'] ?? 0);
                    } else {
                        $leaveCard['vl_aut_w_pay'] =
                            floatval($leaveCard['vl_aut_w_pay'] ?? 0) +
                            floatval($match['vl_aut_w_pay'] ?? 0);
                    }

                    $fieldKeys = $leaveCode === 'mfl'
                        ? ['particulars', 'remarks']
                        : [in_array($leaveCode, ['vl', 'sl']) ? 'particulars' : 'remarks'];

                    foreach ($fieldKeys as $key) {
                        $existing = !empty($leaveCard[$key]) ? explode(', ', trim($leaveCard[$key])) : [];
                        $new = !empty($match[$key]) ? explode(', ', trim($match[$key])) : [];
                        $merged = array_filter(array_unique(array_merge($existing, $new)));
                        $leaveCard[$key] = implode(', ', $merged);
                    }
                } else {
                    if (in_array($leaveCode, ['vl', 'sl'])) {
                        $leaveCard[$leaveCode . '_aut_w_pay'] = '';
                        $leaveCard[$leaveCode . '_aut_wo_pay'] = '';
                        $leaveCard['particulars'] = '';
                    } else {
                        $leaveCard['remarks'] = '';
                    }
                }

                return $leaveCard;
            }, $latestLeaveCard);


        $combined = $this->combine($mapped, $latestLeaveCard);
        $newData = $this->compute($combined);

        foreach ($newData as $info) {
            EmployeeLeaveCard::updateOrCreate(
                ['employee_no' => $info['employee_no'], 'period' => $info['period'], 'year' => $info['year']],
                [
                    'particulars' => $info['particulars'],
                    'vl_earned' => $info['vl_earned'],
                    'vl_aut_w_pay' => $info['vl_aut_w_pay'],
                    'vl_bal' => $info['vl_bal'],
                    'vl_aut_wo_pay' => $info['vl_aut_wo_pay'],
                    'sl_earned' => $info['sl_earned'],
                    'sl_aut_w_pay' => $info['sl_aut_w_pay'],
                    'sl_bal' => $info['sl_bal'],
                    'sl_aut_wo_pay' => $info['sl_aut_wo_pay'],
                    'remarks' => $info['remarks'],
                ]
            );
        }

        // Deduct credits only for other types (use leave equivalent: half-day = 0.5 per date)
        if (!in_array($leaveCode, ['vl', 'sl', 'mfl'])) {
            $credit = LeaveCredits::where('employee_no', $employee_no)
                ->where('leave_type_id', $data->leave_id)
                ->first();

            if ($credit) {
                $datesCount = $data->dates ? $data->dates->count() : 0;
                $equivalent = ($data->duration ?? 'wholeday') === 'wholeday' ? $datesCount : $datesCount * 0.5;
                $credit->credits -= $equivalent;
                $credit->as_of = now()->format('Y-m');
                $credit->save();
            }
        }
    }

    private function processLeaveDates($leaveInfo, string $leaveCode, float $leaveBalance): array
    {
        $leaveDates = $leaveInfo['dates'];
        $holidays = Holiday::pluck('date')->toArray();
        $leaveMonths = [];

        $duration = $leaveInfo['duration'] ?? 'wholeday';
        $daysCovered = count($leaveDates);

        foreach ($leaveDates as $date) {
            $carbonDate = $date instanceof Carbon ? $date : Carbon::parse($date);

            $month = strtoupper($carbonDate->format('F'));
            $abbrMonth = $carbonDate->format('M');
            $day = (int) $carbonDate->format('d');
            $dayOfWeek = $carbonDate->format('D');
            $formattedDate = $carbonDate->format('m-d');

            if (!isset($leaveMonths[$month])) {
                $leaveMonths[$month] = [
                    'month' => $abbrMonth, 
                    'days' => [],
                    'remarks' => '',
                ];

                if (in_array($leaveCode, ['vl', 'sl'])) {
                    $leaveMonths[$month][$leaveCode . '_aut_w_pay'] = 0;
                    $leaveMonths[$month][$leaveCode . '_aut_wo_pay'] = 0;
                    $leaveMonths[$month]['particulars'] = strtoupper($leaveCode) . ': ' . $abbrMonth;
                } elseif ($leaveCode === 'mfl') {
                    $leaveMonths[$month]['vl_aut_w_pay'] = 0;
                    $leaveMonths[$month]['vl_aut_wo_pay'] = 0;
                    $leaveMonths[$month]['particulars'] = strtoupper($leaveCode) . ': ' . $abbrMonth;
                    $leaveMonths[$month]['remarks'] = strtoupper($leaveCode) . ': ' . $abbrMonth;
                } else {
                    $leaveMonths[$month]['remarks'] = strtoupper($leaveCode) . ': ' . $abbrMonth;
                }

                // Compute leave equivalent
                if (in_array($leaveCode, ['vl', 'sl'])) {
                    $leaveMonths[$month]['leave_equivalent'] = (float) number_format(round(
                        $duration === 'wholeday' ? $daysCovered * 1 : $daysCovered / 2, 3), 2);
                } else {
                    $leaveMonths[$month]['leave_equivalent'] = (float) number_format(round($daysCovered * 1, 3), 2);
                }

                $leaveMonths[$month]['daysCovered'] = $daysCovered;
                $leaveMonths[$month]['duration'] = $duration;
            }

            // Exclude weekends and holidays
            if ($dayOfWeek === 'Sat' || $dayOfWeek === 'Sun' || in_array($formattedDate, $holidays)) {
                $leaveMonths[$month]['remarks'] .= ($leaveMonths[$month]['remarks'] ? ', ' : 'Except, ') . "$abbrMonth $day";
            } else {
                $leaveMonths[$month]['days'][] = $day;
            }
        }

        foreach ($leaveMonths as &$entry) {
            $leaveDays = $entry['leave_equivalent'];

            if (in_array($leaveCode, ['vl', 'sl'])) {
                $entry[$leaveCode . '_aut_w_pay'] = min($leaveBalance, $leaveDays);
                $entry[$leaveCode . '_aut_wo_pay'] = max(0, $leaveDays - $leaveBalance);
            } else {
                $entry['vl_aut_w_pay'] = min($leaveBalance, $leaveDays);
                $entry['vl_aut_wo_pay'] = max(0, $leaveDays - $leaveBalance);
            }

            // Group consecutive days
            sort($entry['days']);
            $ranges = [];
            $rangeStart = $prev = null;

            foreach ($entry['days'] as $day) {
                if ($rangeStart === null) {
                    $rangeStart = $day;
                } elseif ($day !== $prev + 1) {
                    $ranges[] = ($rangeStart === $prev) ? "$rangeStart" : "$rangeStart-$prev";
                    $rangeStart = $day;
                }
                $prev = $day;
            }

            if ($rangeStart !== null) {
                $ranges[] = ($rangeStart === $prev) ? "$rangeStart" : "$rangeStart-$prev";
            }

            $dayRanges = implode(', ', $ranges);

            // Format particulars and remarks
            if (in_array($leaveCode, ['vl', 'sl'])) {
                $entry['particulars'] = strtoupper($leaveCode) . ': ' . $entry['month'] . ' ' . $dayRanges;
                if ($entry['duration'] !== 'wholeday') {
                    $entry['particulars'] .= ' (' . str_replace('_', ' ', $entry['duration']) . ')';
                }
            } elseif ($leaveCode === 'mfl') {
                $entry['particulars'] .= ' ' . $dayRanges;
                $entry['remarks'] .= ' ' . $dayRanges;
            } else {
                $entry['remarks'] .= ' ' . $dayRanges;
            }
        }

        $leaveMonths[$month]['month'] = $month;

        return array_values($leaveMonths);
    }



    private function combine($a, $b) {
        $mergedData = [];

        foreach ($a as $index => $itemA) {
            $itemB = $b[$index] ?? [];
            $mergedItem = [];

            foreach ($itemA as $key => $valueA) {
                $valueB = $itemB[$key] ?? null;

                $valueA = trim(preg_replace('/\s+/', ' ', $valueA ?? ''));
                $valueB = trim(preg_replace('/\s+/', ' ', $valueB ?? ''));

                if ($key === 'particulars' && $valueB !== null) {
                    $values = array_filter(
                        array_merge(explode(', ', $valueA), explode(', ', $valueB)),
                        fn($v) => $v !== '' && trim($v) !== ''
                    );

                    $mergedValue = implode(', ', array_unique($values));

                } elseif (in_array($key, ['vl_aut_w_pay', 'sl_aut_w_pay', 'vl_aut_wo_pay', 'sl_aut_wo_pay'])) {
                     $numA = is_numeric($valueA) ? (float) $valueA : 0;
                    $numB = is_numeric($valueB) ? (float) $valueB : 0;
                    $mergedValue = max($numA, $numB);
                } elseif ($key === 'remarks') {
                    $mergedValue = $valueA;

                } else {
                    $mergedValue = $valueB ?? $valueA;
                }

                $mergedItem[$key] = $mergedValue;
            }

            $mergedData[] = $mergedItem;
        }

        return $mergedData;
    }


    public function compute($data) {
        for ($i = 0; $i < count($data); $i++) {
            $period = $data[$i]['period'];
            $vl_earned = floatval($data[$i]['vl_earned']);
            $vl_aut_w_pay = floatval($data[$i]['vl_aut_w_pay']);
            $sl_earned = floatval($data[$i]['sl_earned']);
            $sl_aut_w_pay = floatval($data[$i]['sl_aut_w_pay']);

            if ($i === 0) {
                // First row logic
                $vl_bal = $vl_earned - $vl_aut_w_pay;
                $sl_bal = $sl_earned - $sl_aut_w_pay;

                Log::debug("[$period] First row computation:");
                Log::debug("VL: $vl_earned - $vl_aut_w_pay = $vl_bal");
                Log::debug("SL: $sl_earned - $sl_aut_w_pay = $sl_bal");
            } else {
                $prev_vl_bal = floatval($data[$i - 1]['vl_bal']);
                $prev_sl_bal = floatval($data[$i - 1]['sl_bal']);

                $vl_bal = $prev_vl_bal + $vl_earned - $vl_aut_w_pay;
                $sl_bal = $prev_sl_bal + $sl_earned - $sl_aut_w_pay;

                Log::debug("[$period] Row $i computation:");
                Log::debug("VL: $prev_vl_bal + $vl_earned - $vl_aut_w_pay = $vl_bal");
                Log::debug("SL: $prev_sl_bal + $sl_earned - $sl_aut_w_pay = $sl_bal");
            }

            // Assign formatted balances
            $data[$i]['vl_bal'] = number_format($vl_bal, 3, '.', '');
            $data[$i]['sl_bal'] = number_format($sl_bal, 3, '.', '');
        }

        return $data;
    }

    public function triggerSLVLFirst($data)
    {
        if (!$data) return;

        $leaveCredits = LeaveCredits::with('leave')
            ->where('employee_no', $data['employee_no'])
            ->where('leave_type_id', $data['leave_id'])
            ->first();

        if (!$leaveCredits) return;

        $leaveCode = $leaveCredits->leave->code;
        $initialCredits = floatval($leaveCredits->credits ?? 0);

        $initialDate = Carbon::parse($leaveCredits->as_of);
        $initialMonth = strtoupper($initialDate->format('F'));
        $initialYear = $initialDate->format('Y');

        $months = [
            'JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
            'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'
        ];

        $result = [];
        $balance = 0;
        $startCollecting = false;

        foreach ($months as $month) {
            if ($month == $initialMonth) {
                $startCollecting = true;
                $earned = $initialCredits;
            } elseif ($startCollecting) {
                $earned = 1.25;
            } else {
                continue; // Skip months before 'as_of'
            }

            $balance += $earned;

            $entry = [
                'employee_no' => $data['employee_no'],
                'period' => $month,
                'particulars' => '',
                'earned' => number_format($earned, 3),
                'aut_w_pay' => '',
                'bal' => number_format($balance, 3),
                'year' => $initialYear,
            ];

            $result[] = $entry;
        }

        foreach ($result as $entry) {
            if ($leaveCode === 'VL') {
                EmployeeLeaveCard::updateOrCreate(
                    ['employee_no' => $entry['employee_no'], 'period' => $entry['period'], 'year' => $entry['year']],
                    [
                        'particulars' => $entry['particulars'],
                        'vl_earned' => $entry['earned'],
                        'vl_aut_w_pay' => $entry['aut_w_pay'],
                        'vl_bal' => $entry['bal'],
                        'vl_aut_wo_pay' => '',
                        'year' => $entry['year'],
                    ]
                );
            } elseif ($leaveCode === 'SL') {
                EmployeeLeaveCard::updateOrCreate(
                    ['employee_no' => $entry['employee_no'], 'period' => $entry['period'], 'year' => $entry['year']],
                    [
                        'particulars' => $entry['particulars'],
                        'sl_earned' => $entry['earned'],
                        'sl_aut_w_pay' => $entry['aut_w_pay'],
                        'sl_bal' => $entry['bal'],
                        'sl_aut_wo_pay' => '',
                        'year' => $entry['year'],
                    ]
                );
            }
        }
    }

    public function getLeaveCard($employee_no, $filterMonthYear = null)
    {
        $records = EmployeeLeaveCard::where('employee_no', $employee_no)->get();

        if ($filterMonthYear) {
            [$filterYear, $filterMonth] = explode('-', $filterMonthYear);

            $records = $records->filter(function ($record) use ($filterYear, $filterMonth) {
                $recordMonthNum = DateTime::createFromFormat('F', $record->period)->format('m');
                return $record->year == $filterYear && $recordMonthNum == $filterMonth;
            });
        }

        $sortedRecords = collect($records)
            ->groupBy('year')
            ->map(function ($items, $year) use ($records) {
                $lastItem = $items->last();

                $prevBal = [
                    'vl' => (float)($lastItem['vl_bal'] ?? 0),
                    'sl' => (float)($lastItem['sl_bal'] ?? 0),
                ];

                $previousYearRecord = $records->where('year', $year - 1)->last();

                if ($previousYearRecord) {
                    $prevBal['vl'] = (float)($previousYearRecord['vl_bal'] ?? 0);
                    $prevBal['sl'] = (float)($previousYearRecord['sl_bal'] ?? 0);
                } else {
                    $prevBal['vl'] = 0;
                    $prevBal['sl'] = 0;
                }

                $sortedItems = $items->sortBy(function ($item) {
                    return DateTime::createFromFormat('F', $item['period'])->format('m');
                })->values();

                return [
                    'previous_bal' => $prevBal,
                    'items' => $sortedItems
                ];
            })
            ->sortKeys();

        return $sortedRecords;
    }

    public function updateLeaveCard(string $employee_no, array $records)
    {
        foreach ($records as $year => $recordGroup) {
            foreach ($recordGroup['items'] as $item) {
                EmployeeLeaveCard::updateOrCreate(
                    [
                        'employee_no' => $employee_no,
                        'year' => $year,
                        'period' => $item['period']
                    ],
                    [
                        'particulars' => $item['particulars'] ?? '',
                        'vl_earned' => $item['vl_earned'] ?? 0,
                        'vl_aut_w_pay' => $item['vl_aut_w_pay'] ?? 0,
                        'vl_bal' => $item['vl_bal'] ?? 0,
                        'vl_aut_wo_pay' => $item['vl_aut_wo_pay'] ?? 0,
                        'sl_earned' => $item['sl_earned'] ?? 0,
                        'sl_aut_w_pay' => $item['sl_aut_w_pay'] ?? 0,
                        'sl_bal' => $item['sl_bal'] ?? 0,
                        'sl_aut_wo_pay' => $item['sl_aut_wo_pay'] ?? 0,
                        'remarks' => $item['remarks'] ?? '',
                    ]
                );
            }
        }
    }



}
