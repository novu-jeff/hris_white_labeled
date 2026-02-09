<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sections extends Model
{
    use HasFactory;

    protected $table = 'sections';
    protected $fillable = [
        'code',
        'name',
        'branch_id',
        'department_id',
        'supervisor_id'
    ];

    public function branch() {
        return $this->belongsTo(Branches::class, 'branch_id', 'id');
    }

    public function department() {
        return $this->belongsTo(Departments::class, 'department_id', 'id');
    }
}
