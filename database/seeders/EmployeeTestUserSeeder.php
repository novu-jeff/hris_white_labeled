<?php

namespace Database\Seeders;

use App\Models\EmployeeAccount;
use App\Models\EmployeeInformation;
use App\Models\EmployeePersonal;
use App\Models\EmployementTypes;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeTestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $product = config('app.product');
        
        $testEmployees = [
            [
                'employee_no' => 'EMP-TEST-01',
                'bsd_no' => '959',
                'shift_id' => 1,
                'schedule_id' => 1,
                'company_name' => '',
                'email_id' => 'employee.test01@hris.com',
                'email' => 'employee01@gmail.com',
                'password' => Hash::make('password'),
                'firstname' => 'Kim Anne',
                'middlename' => 'T.',
                'lastname' => 'Llemos',
                'birthday' => '1990-01-01',
                'sex' => 'male',
                'status' => 'active',
                'salary' => '293191',
                'payroll_account_number' => '1234567890',
                'date_hired' => '2025-01-05',
                'gsis_no' => '10000000001',
                'pagibig_no' => '10000000002',
                'philhealth_no' => '10000000003',
                'sss_no' => '10000000004',
                'tin_no' => '10000000005',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'employee_no' => 'EMP-TEST-02',
                'bsd_no' => '100',
                'shift_id' => 1,
                'schedule_id' => 1,
                'company_name' => '',
                'email_id' => 'employee.test02@hris.com',
                'email' => 'employee02@gmail.com',
                'password' => Hash::make('password'),
                'firstname' => 'Albert',
                'middlename' => 'R.',
                'lastname' => 'Yabut',
                'birthday' => '1990-01-01',
                'sex' => 'male',
                'status' => 'active',
                'salary' => '293191',
                'payroll_account_number' => '1234567890',
                'date_hired' => '2025-01-05',
                'gsis_no' => '20000000001',
                'pagibig_no' => '20000000002',
                'philhealth_no' => '20000000003',
                'sss_no' => '20000000004',
                'tin_no' => '20000000005',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'employee_no' => 'NI-SAMPLE-01',
                'bsd_no' => '900',
                'shift_id' => 1,
                'schedule_id' => 1,
                'company_name' => '',
                'email_id' => 'jeffprodev@hris.com',
                'email' => 'jeffprodev@gmail.com',
                'password' => Hash::make('password'),
                'firstname' => 'Jeff',
                'middlename' => null,
                'lastname' => 'Prodev',
                'birthday' => '2000-01-01',
                'sex' => 'male',
                'status' => 'active',
                'salary' => '0',
                'payroll_account_number' => null,
                'date_hired' => now()->format('Y-m-d'),
                'gsis_no' => null,
                'pagibig_no' => null,
                'philhealth_no' => null,
                'sss_no' => null,
                'tin_no' => null,
                'employment_type' => 'Interns',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($testEmployees as $employee) {

            $section_id = null;
            $position_id = null;
            $employment_type_id = null;

            if ($product == 'government') {
                $section_id = rand(1, 3);
                $position_id = 1;
                $employment_type_id = 1;
            }

            if (isset($employee['employment_type'])) {
                $employment_type_id = EmployementTypes::where('name', $employee['employment_type'])->value('id');
            }

            EmployeeInformation::updateOrCreate(
                ['employee_no' => $employee['employee_no']], 
                [
                    'employee_no' => $employee['employee_no'],
                    'bsd_no' => $employee['bsd_no'],
                    'date_hired' => $employee['date_hired'],
                    'employment_type_id' => $employment_type_id ?? null,
                    'shift_id' => $employee['shift_id'],
                    'schedule_id' => $employee['schedule_id'],
                    'section_id' => $section_id ?? null,
                    'position_id' => $position_id ?? null,
                    'salary' => $employee['salary']
                ]
            );
        
            EmployeePersonal::updateOrCreate(
                ['employee_no' => $employee['employee_no']],
                [
                    'firstname' => $employee['firstname'],
                    'middlename' => $employee['middlename'],
                    'lastname' => $employee['lastname'],
                    'birthday' => $employee['birthday'],
                    'sex' => $employee['sex'],
                    'gsis_no' => $employee['gsis_no'],
                    'pagibig_no' => $employee['pagibig_no'],
                    'philhealth_no' => $employee['philhealth_no'],
                    'sss_no' => $employee['sss_no'],
                    'tin_no' => $employee['tin_no'],
                ]
            );
        
            $user = EmployeeAccount::updateOrCreate(
                ['email' => $employee['email']],
                [
                    'email_id' => $employee['email_id'],
                    'email' => $employee['email'],
                    'employee_no' => $employee['employee_no'],
                    'password' => $employee['password'],
                ]
            );
            
            $user->assignRole('employee');
        }
    }
}
