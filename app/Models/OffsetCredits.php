<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OffsetCredits extends Model
{
    use HasFactory;

    protected $table = 'offset_credits';

    protected $fillable = [
        'employee_no',
        'credits',
        'as_of',
    ];

    protected $casts = [
        'credits' => 'float',
    ];
}
