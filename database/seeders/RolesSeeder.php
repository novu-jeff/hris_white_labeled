<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define roles and their descriptions
        $roles = [
            [
                'name' => 'superadmin',
                'description' => 'A superadmin has full control over system settings and user management.',
                'guard_name' => 'web', // Guard for superadmin
            ],
            [
                'name' => 'admin',
                'description' => 'An admin manages system settings and user permissions with high-level access and control.',
                'guard_name' => 'web', // Guard for admin
            ],
            [
                'name' => 'manager',
                'description' => 'A manager can approve employee leave and overtime application requests.',
                'guard_name' => 'web',
            ],
            [
                'name' => 'employee',
                'description' => 'An employee can manage their own personal information, apply for leaves, clock in/out, view payslips, and access various employee-related services and requests.',
                'guard_name' => 'employee', // Guard for employee
            ]
        ];

        // Define permissions for each role
        $permissions = [
            'superadmin' => Permission::where('guard_name', 'web')->pluck('name')->toArray(), // Web-based permissions for superadmin
            'admin' => Permission::where('guard_name', 'web')->whereNotIn('name', [
                'read roles',
                'write roles',
            ])->pluck('name')->toArray(), // Exclude certain permissions, only web-based
            'manager' => Permission::where('guard_name', 'web')->whereIn('name', [
                'read leave',
                'write leave',
                'read atro',
                'write atro',
            ])->pluck('name')->toArray(),
            'employee' => Permission::where('guard_name', 'employee')->pluck('name')->toArray(), // Employee-based permissions for employee
        ];

        // Loop through roles and either create or update them
        foreach ($roles as $roleData) {
            // Find or create the role with specified guard_name
            $role = Role::updateOrCreate(
                ['name' => $roleData['name']],
                ['description' => $roleData['description'], 'guard_name' => $roleData['guard_name']]
            );

            // Sync permissions for the role
            if (isset($permissions[$roleData['name']])) {
                $role->syncPermissions($permissions[$roleData['name']]);
            }
        }
    }
}
