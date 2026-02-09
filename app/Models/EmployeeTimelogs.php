<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeTimelogs extends Model
{
    use HasFactory;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $external = config('app.external_timelogs');
       // dd( $external );

        $this->setConnection($external ? 'mysql2' : 'mysql');

        $this->setTable($external ? 'attendances' : 'timelogs');

        $this->fillable = $external
            ? [
                'sn',
                'table',
                'stamp',
                'employee_id',
                'timestamp',
                'status1',
                'isWeb',
                'captured_image',
                'captured_location',
                'accomplishment',
            ]
            : [
                'employee_id',
                'timestamp',
                'status',
                'punch_type',
                'isWeb',
                'captured_image',
                'captured_location',
                'accomplishment',
            ];
    }

    public function employee()
    {

        $bsd_emp_identical = config('app.bsd_emp_identical');

        if(!$bsd_emp_identical) {
            return $this->belongsTo(EmployeeInformation::class, 'employee_id', 'bsd_no');
        } 

        return $this->belongsTo(EmployeeInformation::class, 'employee_id', 'employee_no');

    }
}
