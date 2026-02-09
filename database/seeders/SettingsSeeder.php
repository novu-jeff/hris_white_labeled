<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public static function moduleKeys(): array
    {
        return [
            'dashboard',
            'announcements',
            'dtr',
            'time_adjustments',
            'payslip',
            'leave',
            'atro',
            'obs',
            'offset',
            'team',
            'messages',
            'directory',
            'tutorial',
            'profile',
            'security_notifications',
            'clock',
        ];
    }

    public function run(): void
    {
        Setting::set('night_shift_differential', '10');

        foreach (self::moduleKeys() as $key) {
            Setting::set('module_' . $key, '1');
        }
    }
}
