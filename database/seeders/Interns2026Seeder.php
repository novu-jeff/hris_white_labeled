<?php

namespace Database\Seeders;

use App\Helper\Generate;
use App\Models\EmployeeAccount;
use App\Models\EmployeeInformation;
use App\Models\EmployeePersonal;
use App\Models\EmployeeSchedule;
use App\Models\EmployementTypes;
use App\Models\Positions;
use App\Models\Sections;
use App\Models\ShiftSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class Interns2026Seeder extends Seeder
{
    /**
     * Real interns 2026 - NI-Interns-26XX (26 = year, XX = intern number).
     * All interns: flexible (Default) shift, default days schedule, and section-based position.
     */
    public function run(): void
    {
        $this->call(InternPositionsSeeder::class);

        $internsTypeId = EmployementTypes::where('name', 'Interns')->value('id');
        if (!$internsTypeId) {
            $this->command->error('Employment type "Interns" not found. Run migrations first.');
            return;
        }

        $flexibleShift = ShiftSchedule::where('shift_duration', 'flexible')->first()
            ?? ShiftSchedule::where('name', 'Default Shift')->first();
        $defaultSchedule = EmployeeSchedule::where('name', 'Default Schedule')->first();

        $positionBySection = [
            'Operations' => 'Operations Intern',
            'Lazarus' => 'Software Development Intern',
            'Technical' => 'Technical Intern',
        ];

        $interns = [
            [
                'number' => '01',
                'firstname' => 'Joevic',
                'middlename' => null,
                'lastname' => 'Almajar',
                'email' => 'almajar.joevic@outlook.com',
                'section_name' => 'Operations',
            ],
            [
                'number' => '02',
                'firstname' => 'Mariel',
                'middlename' => null,
                'lastname' => 'Gonzales',
                'email' => 'marielgonzales444@gmail.com',
                'section_name' => 'Operations',
            ],
            [
                'number' => '03',
                'firstname' => 'Michael',
                'middlename' => null,
                'lastname' => 'De La Torre',
                'email' => 'michaelangelodelatorre06@gmail.com',
                'section_name' => 'Lazarus',
            ],
            [
                'number' => '04',
                'firstname' => 'Denz Ashley',
                'middlename' => null,
                'lastname' => 'Pascua',
                'email' => 'pdenzashley@gmail.com',
                'section_name' => 'Lazarus',
            ],
            [
                'number' => '05',
                'firstname' => 'Ace Johann',
                'middlename' => null,
                'lastname' => 'Basinillo',
                'email' => 'acejohann19@gmail.com',
                'section_name' => 'Lazarus',
            ],
            [
                'number' => '06',
                'firstname' => 'John Cedric',
                'middlename' => null,
                'lastname' => 'Juare',
                'email' => 'johncedricjuare@gmail.com',
                'section_name' => 'Technical',
            ],
            [
                'number' => '07',
                'firstname' => 'Carl',
                'middlename' => null,
                'lastname' => 'Sesbreño',
                'email' => 'cjsoriano0522@gmail.com',
                'section_name' => 'Technical',
            ],
        ];

        $generate = new Generate;

        foreach ($interns as $intern) {
            $employeeNo = 'NI-Interns-26' . $intern['number'];
            $bsdNo = '26' . $intern['number'];

            $section = Sections::firstOrCreate(
                ['name' => $intern['section_name']],
                ['name' => $intern['section_name']]
            );

            $positionName = $positionBySection[$intern['section_name']] ?? 'Operations Intern';
            $position = Positions::where('name', $positionName)->first();

            EmployeeInformation::updateOrCreate(
                ['employee_no' => $employeeNo],
                [
                    'employee_no' => $employeeNo,
                    'bsd_no' => $bsdNo,
                    'date_hired' => now()->format('Y-m-d'),
                    'employment_type_id' => $internsTypeId,
                    'section_id' => $section->id,
                    'shift_id' => $flexibleShift?->id,
                    'schedule_id' => $defaultSchedule?->id,
                    'position_id' => $position?->id,
                    'salary' => 0,
                    'status' => 'active',
                ]
            );

            EmployeePersonal::updateOrCreate(
                ['employee_no' => $employeeNo],
                [
                    'firstname' => $intern['firstname'],
                    'middlename' => $intern['middlename'],
                    'lastname' => $intern['lastname'],
                    'birthday' => null,
                    'sex' => null,
                ]
            );

            $emailId = $generate->email(
                $employeeNo,
                $intern['firstname'],
                $intern['lastname']
            );

            $user = EmployeeAccount::updateOrCreate(
                ['employee_no' => $employeeNo],
                [
                    'employee_no' => $employeeNo,
                    'email' => $intern['email'],
                    'email_id' => $emailId,
                    'password' => Hash::make('password'),
                ]
            );

            $user->assignRole('employee');

            $this->command->info("Created: {$employeeNo} - {$intern['firstname']} {$intern['lastname']} ({$intern['email']})");
        }

        $this->command->info('All 7 intern accounts created. Default password: password');
    }
}
