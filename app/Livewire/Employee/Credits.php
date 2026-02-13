<?php

namespace App\Livewire\Employee;

use App\Models\EmployeeLeaveCard;
use App\Models\LeaveCredits;
use App\Models\OffsetCredits;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Credits extends Component
{

    public $records;

    public function mount() {

        $employee_no = Auth::user()->employee_no;

        $defaultLeave = LeaveCredits::where('employee_no', $employee_no)->with('leave')->get() ?? [];
        
        $remaingCredits = [];

        foreach($defaultLeave as $credits) {
            if($credits->leave_type_id == 1 || $credits->leave_type_id == 2) {
               
                $leaveCard = EmployeeLeaveCard::where('employee_no', $employee_no)->get() ?? [];
                $latest = $leaveCard->last();

                if($credits->leave_type_id == 1) {
                    $leaveType = 'Vacation Leave';
                    $leaveCode = 'VL';
                    $credits = $latest->vl_bal ?? 0;
                } else {
                    $leaveType = 'Sick Leave';
                    $leaveCode = 'SL';
                    $credits = $latest->sl_bal ?? 0;
                }

                $remaingCredits[] = [
                    'name' => $leaveType,
                    'code' => $leaveCode,
                    'credits' => $credits,
                ];


            } else {
                $remaingCredits[] = [
                    'name' => $credits->leave->name,
                    'code' => $credits->leave->code,
                    'credits' => $credits->credits,
                ];
            }
        }


        $offsetCredits = OffsetCredits::where('employee_no', $employee_no)->value('credits');
        if (!is_null($offsetCredits)) {
            $remaingCredits[] = [
                'name' => 'Offset Credits',
                'code' => 'OFFSET',
                'credits' => (float) $offsetCredits,
            ];
        }

        return $this->records = $remaingCredits;

    }

    public function render()
    {
        return view('livewire.employee.credits');
    }
}
