<?php

namespace App\Helpers;

use App\Models\EmployeeInformation;
use App\Models\EmployeePersonal;
use App\Models\Sections;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class SupervisorApproval
{
    /**
     * Check if the supervisor-approval feature is globally enabled.
     */
    public static function isEnabled(): bool
    {
        return Setting::getBool('ess.supervisor_approval_enabled', false);
    }

    /**
     * Resolve the current admin user's employee number by matching email.
     */
    public static function currentAdminEmployeeNo(): ?string
    {
        $user = Auth::user();
        if (!$user || empty($user->email)) {
            return null;
        }

        $personal = EmployeePersonal::where('email', $user->email)->first();

        return $personal?->employee_no;
    }

    /**
     * Get the configured supervisor employee number for a given employee.
     */
    public static function employeeSupervisorNo(string $employeeNo): ?string
    {
        $info = EmployeeInformation::where('employee_no', $employeeNo)->first();
        if (!$info || !$info->section_id) {
            return null;
        }

        $section = Sections::find($info->section_id);

        return $section?->supervisor_id;
    }

    /**
     * Determine if the current admin user is allowed to approve for a given employee.
     *
     * Rules:
     *  - If feature disabled: defer to role-based checks (caller should handle), so always true here.
     *  - Superadmin: always allowed.
     *  - If employee has NO supervisor configured: HR/approvers allowed (return true).
     *  - If employee HAS a supervisor:
     *      - Allow only if current admin is mapped to that supervisor's employee_no via email.
     */
    public static function canApprove(string $employeeNo): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Always allow superadmin regardless of feature flag.
        if (method_exists($user, 'hasRole') && $user->hasRole('superadmin')) {
            return true;
        }

        // If feature is disabled, do not restrict further here.
        if (!self::isEnabled()) {
            return true;
        }

        $supervisorNo = self::employeeSupervisorNo($employeeNo);

        // No supervisor configured => HR / approvers can proceed.
        if (!$supervisorNo) {
            return true;
        }

        // Map current admin to an employee via email.
        $adminEmpNo = self::currentAdminEmployeeNo();

        if ($adminEmpNo && $adminEmpNo === $supervisorNo) {
            // Current admin is the configured supervisor for this employee.
            return true;
        }

        // Employee has a supervisor but current admin is not that supervisor.
        return false;
    }

    /**
     * Convenience: check if an employee has a configured supervisor.
     */
    public static function employeeHasSupervisor(string $employeeNo): bool
    {
        return (bool) self::employeeSupervisorNo($employeeNo);
    }
}

