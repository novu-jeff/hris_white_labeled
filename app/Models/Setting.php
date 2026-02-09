<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'key';
    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        $cacheKey = 'setting.' . $key;
        return Cache::remember($cacheKey, 300, function () use ($key, $default) {
            $row = static::find($key);
            return $row !== null ? $row->value : $default;
        });
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        Cache::forget('setting.' . $key);
    }

    public static function getFloat(string $key, float $default = 0): float
    {
        $v = static::get($key, $default);
        return (float) $v;
    }

    public static function getBool(string $key, bool $default = true): bool
    {
        $v = static::get($key, $default ? '1' : '0');
        return filter_var($v, FILTER_VALIDATE_BOOLEAN);
    }
}
