<?php

namespace Database\Seeders;

use App\Models\EmployeeModuleSetting;
use App\Models\EmployementTypes;
use Illuminate\Database\Seeder;

class EmployeeModuleSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $moduleKeys = SettingsSeeder::moduleKeys();
        foreach (EmployementTypes::all() as $type) {
            foreach ($moduleKeys as $key) {
                EmployeeModuleSetting::setEnabled($type->id, $key, true);
            }
        }
    }
}
