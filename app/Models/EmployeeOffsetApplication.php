<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeOffsetApplication extends Model
{
    use HasFactory;

    protected $table = 'employee_offset_applications';
    protected $fillable = [
        'employee_no',
        'date_filed',
        'offset_date_from',
        'offset_date_to',
        'purpose',
        'requested_by',
        'status',
        'remarks',
        'action_by_id'
    ];

    public function employment() {
        return $this->hasOne(EmployeeInformation::class, 'employee_no', 'employee_no');
    }

    public function employee() {
        return $this->hasOne(EmployeeInformation::class, 'employee_no', 'employee_no');
    }

    public function approved_by()
    {
        return $this->belongsTo(User::class, 'action_by_id', 'id');
    }
}
