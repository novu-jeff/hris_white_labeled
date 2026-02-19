<?php

namespace App\Console\Commands;

use App\Models\EmployeeInformation;
use App\Models\Setting;
use Illuminate\Console\Command;

class SetDefaultPayrollBank extends Command
{
    protected $signature = 'payroll:set-default-bank
                            {--value= : Override: set all to this bank name instead of the setting}';

    protected $description = 'Set all employees\' payroll_bank to the default from Payroll Settings (or --value).';

    public function handle(): int
    {
        $value = $this->option('value') ?? Setting::get('payroll_bank_default', 'Metro Bank');
        $value = trim($value);

        if ($value === '') {
            $this->warn('Default Payroll Bank is empty in settings. Use --value="Metro Bank" to set a value.');
            return self::FAILURE;
        }

        $updated = EmployeeInformation::query()->update(['payroll_bank' => $value]);

        $this->info("Updated {$updated} employee(s) to payroll_bank = \"{$value}\".");
        return self::SUCCESS;
    }
}
