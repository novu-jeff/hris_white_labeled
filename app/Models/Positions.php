<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Positions extends Model
{
    use HasFactory;

    protected $table = 'positions';
    protected $fillable = [
        'code',
        'name',
        'salary_grade',
        'salary',
        'type',
        'w_tax',
        'isActive'
    ];

    public function employment_type() {
        return $this->hasOne(EmployementTypes::class, 'id', 'type');
    }

    public function employees()
    {
        return $this->hasMany(EmployeeInformation::class, 'position_id');
    }

}
