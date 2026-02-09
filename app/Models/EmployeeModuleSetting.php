<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class EmployeeModuleSetting extends Model
{
    protected $fillable = ['employment_type_id', 'module_key', 'enabled'];

    protected $casts = ['enabled' => 'boolean'];

    public static function isEnabled(int $employmentTypeId, string $moduleKey): bool
    {
        $cacheKey = "employee_module.{$employmentTypeId}.{$moduleKey}";
        return (bool) Cache::remember($cacheKey, 300, function () use ($employmentTypeId, $moduleKey) {
            $row = static::where('employment_type_id', $employmentTypeId)
                ->where('module_key', $moduleKey)
                ->first();
            return $row ? $row->enabled : true;
        });
    }

    public static function setEnabled(int $employmentTypeId, string $moduleKey, bool $enabled): void
    {
        static::updateOrCreate(
            [
                'employment_type_id' => $employmentTypeId,
                'module_key' => $moduleKey,
            ],
            ['enabled' => $enabled]
        );
        Cache::forget("employee_module.{$employmentTypeId}.{$moduleKey}");
    }
}
