<?php

namespace App\Console\Commands;

use App\Models\EmployeeAccount;
use App\Models\EmployeeInformation;
use App\Models\EmployeePersonal;
use App\Models\EmployeeTimelogs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveSampleEmployee extends Command
{
    protected $signature = 'employee:remove-sample {employee_no=NI-SAMPLE-01 : Employee number to remove} {--force : Skip confirmation}';

    protected $description = 'Remove NI-SAMPLE-01 (Jeff Prodev) and all related DB records.';

    public function handle(): int
    {
        $employeeNo = $this->argument('employee_no');

        if (!$this->option('force') && !$this->confirm("Remove employee {$employeeNo} and all related records?", true)) {
            return self::FAILURE;
        }

        $account = EmployeeAccount::where('employee_no', $employeeNo)->first();
        if (!$account && !EmployeeInformation::where('employee_no', $employeeNo)->exists()) {
            $this->warn("Employee {$employeeNo} not found. Nothing to remove.");
            return self::SUCCESS;
        }

        DB::beginTransaction();
        try {
            // Role assignment (Spatie)
            if ($account) {
                DB::table('model_has_roles')
                    ->where('model_type', EmployeeAccount::class)
                    ->where('model_id', $account->id)
                    ->delete();
            }

            // Timelogs (uses employee_id)
            EmployeeTimelogs::where('employee_id', $employeeNo)->delete();

            // Time adjustment attachments (before deleting employee_time_adjustments)
            if (\Schema::hasTable('employee_time_adjustments_attachments')) {
                $ids = DB::table('employee_time_adjustments')->where('employee_no', $employeeNo)->pluck('id');
                if ($ids->isNotEmpty()) {
                    DB::table('employee_time_adjustments_attachments')->whereIn('employee_time_adjustment_id', $ids)->delete();
                }
            }

            // Tables with employee_no (order doesn't matter for string FK)
            $tables = [
                'leave_credits', 'employee_earnings', 'employee_deductions',
                'employee_education', 'employee_parents', 'employee_children',
                'employee_employment_history', 'employee_civil_service', 'employee_trainings',
                'employee_other_works', 'employee_skills_hobbies', 'loans',
                'employee_offset_applications', 'offset_credits', 'employee_atro',
                'employee_business_slips', 'employee_time_adjustments', 'employee_payslip_request',
                'employee_leave', 'employee_leave_dates', 'employee_leave_cards',
                'employee_announcements_seen', 'monitoring', 'employee_request_logs',
                'parallel_overtime',
                'employee_atro_relative',
            ];

            foreach ($tables as $table) {
                if (\Schema::hasTable($table)) {
                    $deleted = DB::table($table)->where('employee_no', $employeeNo)->delete();
                    if ($deleted) {
                        $this->line("  Deleted {$deleted} from {$table}");
                    }
                }
            }

            // Payroll-related (may have employee_no)
            $payrollTables = [
                'salary_items_payroll', 'ot_items_payroll', 'payroll_employees',
                'bonuses_items', 'clothing_allowance_items', 'bonus_items_payroll',
            ];
            foreach ($payrollTables as $table) {
                if (\Schema::hasTable($table)) {
                    $deleted = DB::table($table)->where('employee_no', $employeeNo)->delete();
                    if ($deleted) {
                        $this->line("  Deleted {$deleted} from {$table}");
                    }
                }
            }

            // Employee update tables (profile approval / updated info)
            $updateTables = [
                'employee_update_personal', 'employee_update_parents', 'employee_update_children',
                'employee_update_education', 'employee_update_employment_history', 'employee_update_civil_service',
                'employee_update_trainings', 'employee_update_other_works', 'employee_update_skills_hobbies',
            ];
            foreach ($updateTables as $table) {
                if (\Schema::hasTable($table)) {
                    $deleted = DB::table($table)->where('employee_no', $employeeNo)->delete();
                    if ($deleted) {
                        $this->line("  Deleted {$deleted} from {$table}");
                    }
                }
            }

            EmployeeAccount::where('employee_no', $employeeNo)->delete();
            EmployeePersonal::where('employee_no', $employeeNo)->delete();
            EmployeeInformation::where('employee_no', $employeeNo)->delete();

            DB::commit();
            $this->info("Removed {$employeeNo} and all related records.");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
