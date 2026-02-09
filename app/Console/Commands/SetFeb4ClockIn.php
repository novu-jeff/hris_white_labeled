<?php

namespace App\Console\Commands;

use App\Models\EmployeeInformation;
use App\Models\EmployeePersonal;
use App\Models\EmployeeTimelogs;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetFeb4ClockIn extends Command
{
    protected $signature = 'timelogs:set-feb4-clockin {--date=2026-02-04 : Date (Y-m-d)} {--dry-run : Show what would be updated}';
    protected $description = 'Set clock-in to 8:00 AM on Feb 4 (Wed) for all except Leoiz and Charleston';

    public function handle(): int
    {
        $date = $this->option('date');
        $dryRun = $this->option('dry-run');
        $targetTime = Carbon::parse($date)->setTime(8, 0, 0)->toDateTimeString();

        $external = config('app.external_timelogs');
        $connection = $external ? 'mysql2' : config('database.default');
        $table = $external ? 'attendances' : 'timelogs';
        $statusCol = $external ? 'status1' : 'status';

        $this->info("Date: {$date}, target clock-in: {$targetTime}");
        $this->info("Using: connection={$connection}, table={$table}");
        if ($dryRun) {
            $this->warn('DRY RUN – no changes will be made.');
        }

        $bsdEmpIdentical = (bool) config('app.bsd_emp_identical', true);

        // Exclude employees with firstname or lastname containing Leoiz or Charleston
        $excludeNames = ['Leoiz', 'Charleston'];
        $excludeEmployeeNos = EmployeePersonal::where(function ($q) use ($excludeNames) {
            foreach ($excludeNames as $name) {
                $q->orWhere('firstname', 'like', "%{$name}%")
                    ->orWhere('lastname', 'like', "%{$name}%");
            }
        })->pluck('employee_no')->unique()->values()->all();

        $excludeTimelogIds = collect();
        if ($bsdEmpIdentical) {
            $excludeTimelogIds = collect($excludeEmployeeNos);
        } else {
            $excludeTimelogIds = EmployeeInformation::whereIn('employee_no', $excludeEmployeeNos)
                ->pluck('bsd_no')
                ->filter()
                ->unique()
                ->values();
        }

        $this->info('Excluded (do not change): ' . ($excludeTimelogIds->isEmpty() ? 'none found' : $excludeTimelogIds->join(', ')));

        $logsTable = DB::connection($connection)->table($table);
        $dateStart = Carbon::parse($date)->startOfDay()->toDateTimeString();
        $dateEnd = Carbon::parse($date)->endOfDay()->toDateTimeString();

        $dayLogs = $logsTable
            ->whereBetween('timestamp', [$dateStart, $dateEnd])
            ->where($statusCol, 0)
            ->orderBy('employee_id')
            ->orderBy('timestamp')
            ->get();

        // Keep only the first clock-in per employee (earliest status=0); later status=0 is lunch_in
        $firstClockInPerEmployee = $dayLogs->groupBy('employee_id')->map(function ($rows) {
            return $rows->sortBy('timestamp')->first();
        })->values();

        $updated = 0;
        $skipped = 0;
        $alreadyEight = 0;

        foreach ($firstClockInPerEmployee as $log) {
            if ($excludeTimelogIds->contains($log->employee_id)) {
                $skipped++;
                continue;
            }
            $currentTs = Carbon::parse($log->timestamp)->format('H:i:s');
            if ($currentTs === '08:00:00') {
                $alreadyEight++;
                continue;
            }
            if ($dryRun) {
                $this->line("  Would set employee_id {$log->employee_id} clock-in from {$log->timestamp} to {$targetTime}");
                $updated++;
                continue;
            }
            $updateData = [
                'timestamp' => $targetTime,
                'updated_at' => now()->toDateTimeString(),
            ];
            if (\Schema::connection($connection)->hasColumn($table, 'punch_type')) {
                $updateData['punch_type'] = 'clock_in';
            }
            $logsTable->where('id', $log->id)->update($updateData);
            $updated++;
        }

        $this->info("Done. Updated: {$updated}, Skipped (Leoiz/Charleston): {$skipped}, Already 8:00: {$alreadyEight}");

        // Show current Feb 4 clock-ins so you can verify the changes
        $this->newLine();
        $this->info('Feb 4 clock-in times in database (first punch per employee):');
        $verifyLogs = DB::connection($connection)->table($table)
            ->whereBetween('timestamp', [$dateStart, $dateEnd])
            ->where($statusCol, 0)
            ->orderBy('employee_id')
            ->orderBy('timestamp')
            ->get();
        $firstPunches = $verifyLogs->groupBy('employee_id')->map(fn ($rows) => $rows->sortBy('timestamp')->first());
        foreach ($firstPunches as $empId => $row) {
            $this->line("  {$empId}: " . Carbon::parse($row->timestamp)->format('Y-m-d H:i:s'));
        }

        return 0;
    }
}
