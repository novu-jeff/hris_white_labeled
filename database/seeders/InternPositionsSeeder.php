<?php

namespace Database\Seeders;

use App\Models\EmployementTypes;
use App\Models\Positions;
use App\Models\ShiftSchedule;
use App\Models\EmployeeSchedule;
use Illuminate\Database\Seeder;

class InternPositionsSeeder extends Seeder
{
    /**
     * Ensures interns have: flexible (Default) shift, default days schedule,
     * and positions: Operations Intern, Software Development Intern, Technical Intern.
     */
    public function run(): void
    {
        $internsTypeId = EmployementTypes::where('name', 'Interns')->value('id');
        if (!$internsTypeId) {
            $this->command->warn('Employment type "Interns" not found. Run migration add_interns_employment_type first.');
            return;
        }

        // Ensure flexible shift exists (Default Shift - used for all interns)
        ShiftSchedule::firstOrCreate(
            ['name' => 'Default Shift'],
            [
                'description' => 'Default shift for OPAPRU - flexible 8 hours',
                'shift_duration' => 'flexible',
                'earliest_in' => '07:00',
                'latest_in' => '09:00',
                'start_shift' => null,
                'break_out' => '12:00',
                'break_in' => '13:00',
                'end_shift' => null,
                'work_setup' => 'hybrid',
                'min_ot_mins' => 120,
                'max_ot_time' => '22:00',
                'mobile_earliest_clockin' => '08:00',
                'mobile_latest_clockin' => '08:00',
                'web_earliest_clockin' => '07:00',
                'web_latest_clockin' => '09:00',
            ]
        );

        // Ensure default days schedule exists
        EmployeeSchedule::firstOrCreate(
            ['name' => 'Default Schedule'],
            [
                'description' => 'Default schedule for OPAP employees',
                'monday' => 1,
                'monday_remarks' => null,
                'tuesday' => 1,
                'tuesday_remarks' => null,
                'wednesday' => 1,
                'wednesday_remarks' => null,
                'thursday' => 1,
                'thursday_remarks' => null,
                'friday' => 1,
                'friday_remarks' => null,
                'saturday' => 0,
                'saturday_remarks' => 'Rest Day',
                'sunday' => 0,
                'sunday_remarks' => 'Rest Day',
            ]
        );

        // Add intern positions (linked to Interns employment type)
        $positions = [
            ['code' => 'OPS-INT', 'name' => 'Operations Intern'],
            ['code' => 'SD-INT', 'name' => 'Software Development Intern'],
            ['code' => 'TECH-INT', 'name' => 'Technical Intern'],
        ];

        foreach ($positions as $p) {
            Positions::updateOrCreate(
                ['name' => $p['name']],
                [
                    'code' => $p['code'],
                    'name' => $p['name'],
                    'type' => $internsTypeId,
                    'salary_grade' => null,
                    'salary' => null,
                    'w_tax' => null,
                ]
            );
        }

        $this->command->info('Intern positions and default shift/schedule ready.');
    }
}
