<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'recruitment' => [
                'jobs',
                'applicants'
            ],
            'hris' => [
                'hris'
            ],
            'timekeeping' => [
                'timelogs',
                'correction-timelogs'
            ],
            'payroll' => [],
            'ess' => [
                'leave',
                'obs',
                'offset',
                'atro',
                'time-adjustments',
                'payslip-request',
                'announcements',
                'employee-profile-approval',
                'messages',
                'faqs'
            ],
            'reports' => [
                'dtr',
                'bir-2316'
            ],
            'settings' => [
                'company-information',
                'scheduler',
                'tranches',
                'branches',
                'departments',
                'sections',
                'assessments',
                'requirements',
                'users',
                'roles',
                'bank-information',
                'employment-type',
                'positions',
                'violations',
                'leave-types',
                'leave-credits',
                'gsis-billing',
                'employee-earnings',
                'employee-deductions',
                'other-earnings',
                'other-deductions',
                'shift-schedule',
                'employee-schedule',
                'holidays',
            ],
            'employee' => [
                'apply-leave',
                'clock-in-out',
                'remaining-credit',
                'apply-atro',
                'apply-time-adjustments',
                'payslip',
                'employee-payslip-request',
                'employee-messages',
                'apply-obs',
                'apply-offset',
                'employee-dtr',
                'my-directory',
                'my-team',
                'employee-announcements',
                'my-profile',
            ]
        ];

        foreach ($permissions as $module => $actions) {
            foreach ($actions as $action) {
                $guardName = $module === 'employee' ? 'employee' : 'web';

                if (in_array($action, ['my-directory', 'my-team', 'employee-announcements', 'payslip'])) {
                    $this->createPermission($module, "read $action", $guardName);
                } else {
                    $this->createPermission($module, "read $action", $guardName);
                    $this->createPermission($module, "write $action", $guardName);
                }
            }
        }
    }

    private function createPermission(string $module, string $permissionName, string $guardName)
    {
        Permission::firstOrCreate([
            'name' => $permissionName,
            'guard_name' => $guardName,
            'module_name' => $module,
        ]);
    }
}
