<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftSchedule extends Model
{
    use HasFactory;

    protected $table = 'shift_schedule';
    protected $fillable = [
        'name',
        'description',
        'shift_duration',
        'is_breaktime_required',
        'allow_anytime_clockin',
        'allow_anytime_clockout',
        'earliest_in',
        'latest_in',
        'start_shift',
        'break_out',
        'break_in',
        'end_shift',
        'work_setup',
        'min_ot_mins',
        'max_ot_time',
        'mobile_earliest_clockin',
        'mobile_latest_clockin',
        'web_earliest_clockin',
        'web_latest_clockin',
    ];    

    protected $casts = [
        'is_breaktime_required' => 'boolean',
        'allow_anytime_clockin' => 'boolean',
        'allow_anytime_clockout' => 'boolean',
    ];

    public function employees() {
        return $this->hasMany(EmployeeInformation::class, 'shift_id', 'id');
    }

}
