<?php

namespace App\Helpers;

use App\Models\EmployeeModuleSetting;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class EmployeeModules
{
    /** Route name => module key */
    protected static array $routeToModule = [
        'employee.dashboard' => 'dashboard',
        'employee.announcements.index' => 'announcements',
        'employee.announcements.view' => 'announcements',
        'employee.dtr' => 'dtr',
        'employee.time-adjustments' => 'time_adjustments',
        'employee.time-adjustments.apply' => 'time_adjustments',
        'employee.time-adjustments.edit' => 'time_adjustments',
        'employee.time-adjustments.show' => 'time_adjustments',
        'employee.payslip' => 'payslip',
        'employee.leave' => 'leave',
        'employee.leave-card' => 'leave',
        'employee.leave.apply' => 'leave',
        'employee.leave.edit' => 'leave',
        'employee.leave.show' => 'leave',
        'employee.atro' => 'atro',
        'employee.atro.apply' => 'atro',
        'employee.atro.edit' => 'atro',
        'employee.obs.index' => 'obs',
        'employee.obs.apply' => 'obs',
        'employee.obs.edit' => 'obs',
        'employee.offset.index' => 'offset',
        'employee.offset.apply' => 'offset',
        'employee.offset.edit' => 'offset',
        'employee.team' => 'team',
        'employee.messages' => 'messages',
        'employee.directory' => 'directory',
        'employee.tutorial' => 'tutorial',
        'employee.profile' => 'profile',
        'employee.clock' => 'clock',
        'employee.credit' => 'leave',
    ];

    public static function resolveEmploymentTypeId(): ?int
    {
        $user = Auth::guard('employee')->user();
        if (!$user) {
            return null;
        }
        $info = $user->information ?? null;
        return $info?->employment_type_id;
    }

    public static function isModuleEnabledForType(?int $employmentTypeId, string $moduleKey): bool
    {
        if ($employmentTypeId === null) {
            return true;
        }
        return EmployeeModuleSetting::isEnabled($employmentTypeId, $moduleKey);
    }

    public static function isModuleEnabled(string $moduleKey): bool
    {
        return self::isModuleEnabledForType(self::resolveEmploymentTypeId(), $moduleKey);
    }

    public static function isRouteAllowed(string $routeName, ?string $form = null, ?int $employmentTypeId = null): bool
    {
        $typeId = $employmentTypeId ?? self::resolveEmploymentTypeId();
        if ($form === 'security-notifications') {
            return self::isModuleEnabledForType($typeId, 'security_notifications');
        }
        $module = self::$routeToModule[$routeName] ?? null;
        if (!$module) {
            return true;
        }
        return self::isModuleEnabledForType($typeId, $module);
    }

    public static function moduleForRoute(string $routeName): ?string
    {
        return self::$routeToModule[$routeName] ?? null;
    }

    public static function isNavModuleEnabled(string $moduleKey, ?int $employmentTypeId = null): bool
    {
        $typeId = $employmentTypeId ?? self::resolveEmploymentTypeId();
        return self::isModuleEnabledForType($typeId, $moduleKey);
    }
}
