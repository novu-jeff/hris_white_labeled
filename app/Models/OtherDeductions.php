<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtherDeductions extends Model
{
    use HasFactory;

    protected $table = 'other_deductions';
    protected $fillable = [
        'code',
        'name',
        'amount',
        'amount_type',
        'maximum_amount'
    ];

}
