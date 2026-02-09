<?php

namespace App\Services;

use App\Models\EmployeeTimeAdjustments;
use App\Models\EmployeeTimelogs;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TimeAdjustmentService
{
    protected bool $bsd_emp_identical;

    public function __construct()
    {
        $this->bsd_emp_identical = (bool) config('app.bsd_emp_identical', true);
    }

    /**
     * Resolve the employee_id used in timelogs (bsd_no or employee_no).
     */
    public function getTimelogEmployeeId(string $employee_no): ?string
    {
        if ($this->bsd_emp_identical) {
            return $employee_no;
        }
        return DB::table('employee_information')
            ->where('employee_no', $employee_no)
            ->value('bsd_no');
    }

    /**
     * Normalize a time string (e.g. "08:00", "08:00:00", "8:00 AM") to "H:i:s".
     */
    protected function normalizeTime(?string $time): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }
        $time = trim($time);
        if (stripos($time, 'M') !== false) {
            try {
                return Carbon::createFromFormat('h:i A', $time)->format('H:i:s');
            } catch (\Exception $e) {
                try {
                    return Carbon::parse($time)->format('H:i:s');
                } catch (\Exception $e2) {
                    return null;
                }
            }
        }
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $time)) {
            $parts = explode(':', $time);
            return sprintf('%02d:%02d:%02d', (int) $parts[0], (int) ($parts[1] ?? 0), (int) ($parts[2] ?? 0));
        }
        try {
            return Carbon::parse($time)->format('H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Apply an approved time adjustment to the employee's timelogs (DTR).
     * Replaces existing logs for that date with the adjusted clock_in, break_out, break_in, clock_out.
     *
     * @return array{success: bool, message: string, applied_count: int}
     */
    public function applyApprovedAdjustmentToDtr(EmployeeTimeAdjustments $adjustment): array
    {
        $employee_no = $adjustment->employee_no;
        $date = Carbon::parse($adjustment->date)->toDateString();
        $id = $adjustment->id;

        Log::channel('single')->info('Time adjustment apply to DTR: start', [
            'adjustment_id' => $id,
            'employee_no' => $employee_no,
            'date' => $date,
        ]);

        if (config('app.external_timelogs')) {
            Log::channel('single')->warning('Time adjustment apply to DTR: skipped (external timelogs)', [
                'adjustment_id' => $id,
                'employee_no' => $employee_no,
            ]);
            return [
                'success' => false,
                'message' => 'Timelogs are external; cannot apply adjustment to DTR.',
                'applied_count' => 0,
            ];
        }

        $employee_id = $this->getTimelogEmployeeId($employee_no);
        if (!$employee_id) {
            Log::channel('single')->warning('Time adjustment apply to DTR: bsd_no not found', [
                'adjustment_id' => $id,
                'employee_no' => $employee_no,
            ]);
            return [
                'success' => false,
                'message' => 'Employee BSD number not found.',
                'applied_count' => 0,
            ];
        }

        $clock_in = $this->normalizeTime($adjustment->clock_in);
        $break_out = $this->normalizeTime($adjustment->break_out);
        $break_in = $this->normalizeTime($adjustment->break_in);
        $clock_out = $this->normalizeTime($adjustment->clock_out);

        if (!$clock_in || !$clock_out) {
            Log::channel('single')->warning('Time adjustment apply to DTR: missing clock_in or clock_out', [
                'adjustment_id' => $id,
                'employee_no' => $employee_no,
                'date' => $date,
            ]);
            return [
                'success' => false,
                'message' => 'Adjustment must have clock_in and clock_out.',
                'applied_count' => 0,
            ];
        }

        $toInsert = [];

        $toInsert[] = [
            'employee_id' => $employee_id,
            'timestamp' => "{$date} {$clock_in}",
            'status' => 0,
            'shift_id' => 1,
            'schedule_id' => 1,
            'isWeb' => true,
            'captured_image' => null,
            'captured_location' => null,
            'accomplishment' => 'Time adjustment #' . $id,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];

        if ($break_out) {
            $toInsert[] = [
                'employee_id' => $employee_id,
                'timestamp' => "{$date} {$break_out}",
                'status' => 1,
                'shift_id' => 1,
                'schedule_id' => 1,
                'isWeb' => true,
                'captured_image' => null,
                'captured_location' => null,
                'accomplishment' => null,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ];
        }
        if ($break_in) {
            $toInsert[] = [
                'employee_id' => $employee_id,
                'timestamp' => "{$date} {$break_in}",
                'status' => 0,
                'shift_id' => 1,
                'schedule_id' => 1,
                'isWeb' => true,
                'captured_image' => null,
                'captured_location' => null,
                'accomplishment' => null,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ];
        }

        $toInsert[] = [
            'employee_id' => $employee_id,
            'timestamp' => "{$date} {$clock_out}",
            'status' => 1,
            'shift_id' => 1,
            'schedule_id' => 1,
            'isWeb' => true,
            'captured_image' => null,
            'captured_location' => null,
            'accomplishment' => null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];

        $table = (new EmployeeTimelogs)->getTable();
        $connection = (new EmployeeTimelogs)->getConnectionName() ?? config('database.default');

        try {
            DB::connection($connection)->beginTransaction();

            $deleted = DB::connection($connection)
                ->table($table)
                ->where('employee_id', $employee_id)
                ->whereDate('timestamp', $date)
                ->delete();

            DB::connection($connection)->table($table)->insert($toInsert);

            DB::connection($connection)->commit();

            Log::channel('single')->info('Time adjustment applied to DTR', [
                'adjustment_id' => $id,
                'employee_no' => $employee_no,
                'employee_id' => $employee_id,
                'date' => $date,
                'deleted_logs' => $deleted,
                'inserted_logs' => count($toInsert),
            ]);

            return [
                'success' => true,
                'message' => 'Adjustment applied to DTR.',
                'applied_count' => count($toInsert),
            ];
        } catch (\Exception $e) {
            DB::connection($connection)->rollBack();
            Log::channel('single')->error('Time adjustment apply to DTR failed', [
                'adjustment_id' => $id,
                'employee_no' => $employee_no,
                'date' => $date,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'applied_count' => 0,
            ];
        }
    }
}
