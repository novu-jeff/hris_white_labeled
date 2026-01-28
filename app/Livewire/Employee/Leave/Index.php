<?php

namespace App\Livewire\Employee\Leave;

use App\Http\Controllers\Admin\Services\LeaveCardService;
use App\Models\EmployeeInformation;

use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveCard;
use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\EmployeeAtro;
use App\Models\EmployeeBusinessSlip;
use App\Models\LeaveType;

class Index extends Component
{

    use WithPagination;

    public $selected_id;
    public $user_id;
    protected $listeners = ['remove', 'cancel'];
    public $applications;

    protected $paginationTheme = 'bootstrap';
    public $entries = 10;
    public $status = 'all';

    public $currentMonth; 
    public $leaveBalances;

    public $id;
    public $employee_no;
    public $action;
    public $leaverecords;

    public $period = [];
    public $particulars = [];
    public $vl_earned = [];
    public $vl_aut_w_pay = [];
    public $vl_bal = [];
    public $vl_aut_wo_pay = [];
    public $sl_earned = [];
    public $sl_aut_w_pay = [];
    public $sl_bal = [];
    public $sl_aut_wo_pay = [];
    public $remarks = [];
    public $vl_total_bal = [];
    public $sl_total_bal = [];
    public $total_bal = [];
    public $activeYear;

    public function mount() {
        $user = Auth::guard('employee')->user() ?? Auth::user();
        $user_id = $user?->employee_no;

        if(is_null($user_id)) {
            return redirect()->route('employee.leave');
        }

        $this->user_id = $user_id;

        $this->getEmployeeLeaveCardBalances();
        $this->getLeaveCredits();

        
    }

    public function download(int $leave_id) {
        
        $employee_no = $this->user_id;
    
        $records = EmployeeLeave::with('employee.personal', 'employee.positions')->where('employee_no', $employee_no)
            ->where('id', $leave_id)
            ->first();
    
        $template = public_path('templates/forms/HRMS-PD Form 03.xlsx');
    
        if (!file_exists($template)) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops', 
                'message' => 'File does not exists!'
            ]);
        }
    
        try {
            $spreadsheet = IOFactory::load($template);
            $sheet = $spreadsheet->getActiveSheet();
    
            $sheet->setCellValue('G9', strtoupper($records->employee->personal->lastname) ?? '');
            $sheet->setCellValue('I9', strtoupper($records->employee->personal->firstname) ?? '');
            $sheet->setCellValue('N9', strtoupper($records->employee->personal->middlename) ?? '');
            $sheet->setCellValue('O11', strtoupper($records->employee->salary) ?? '');
            $sheet->setCellValue('H11', strtoupper($records->employee->positions->name) ?? '');

            // Set created date
            $sheet->setCellValue('F11', Carbon::parse($records->created_at)->format('m/d/y'));
    
            // Define leave type mapping
            $leaveType = [
                1 => 'C17',
                2 => 'C18',
                3 => 'C19',
                4 => 'C20',
                5 => 'C21',
                6 => 'C22',
                7 => 'C23',
                8 => 'C24',
                9 => 'C25',
                10 => 'C26',
                11 => 'C27',
                12 => 'C28',
                13 => 'C29',
            ];
    
            if (isset($leaveType[$records->leave_id])) {
                $sheet->setCellValue($leaveType[$records->leave_id], '/');
            }
    
            if($records->leave_id == 1 || $records->leave_id == 6) {
                if($records->location == 'ph') {
                    $sheet->setCellValue('J18', '/');
                    $sheet->setCellValue('N18', strtoupper($records->location_specific) ?? '');
                } else {
                    $sheet->setCellValue('J19', '/');
                    $sheet->setCellValue('N19', strtoupper($records->location_specific) ?? '');
                }
            }
    
            if($records->leave_id == 3) {
                if($records->confinement == 'hospital') {
                    $sheet->setCellValue('J18', '/');
                    $sheet->setCellValue('N18', strtoupper($records->illness) ?? '');
                } else {
                    $sheet->setCellValue('J21', '/');
                    $sheet->setCellValue('N21', strtoupper($records->illness) ?? '');
                }
            }
    
            if($records->leave_id == 8) {
                if($records->study == 'completion_masters') {
                    $sheet->setCellValue('J26', '/');
                } elseif($records->study == 'examination') {
                    $sheet->setCellValue('J27', '/');
                } else {
                    $sheet->setCellValue('M28', strtoupper($records->study_other_purpose) ?? '');
                }
            }
    
            $sheet->setCellValue($records->commutation == 'YES' ? 'J34' : 'J33', '/');
    
            $from = Carbon::parse($records->from);
            $to = isset($records->to) ? Carbon::parse($records->to) : null;
            $daysCovered = $to ? $from->diffInDays($to) + 1 : 1;


            $holidays = Holiday::pluck('date')->map(function ($date) {
                return Carbon::createFromFormat('m-d', $date)->format('m-d'); // Normalize to MM-DD
            })->toArray();

            $period = $to ? CarbonPeriod::create($from, $to) : CarbonPeriod::create($from, $from);
            $dates = [];

            foreach ($period as $date) {
                $formattedDate = $date->format('m-d'); 
                $dayOfWeek = $date->format('D'); 

                if ($dayOfWeek !== 'Sat' && $dayOfWeek !== 'Sun' && !in_array($formattedDate, $holidays)) {
                    $dates[] = $date->format('m/d/y');
                }
            }

            $leaveCardBalance = $this->getLeaveCard();
            $currentTimestamp = Carbon::now()->format('F Y');

            if($records->leave_id == 1) {
                $vl_latest = $leaveCardBalance->vl_bal;
                $vl_covered = number_format($daysCovered, 2);
                $vl_bal = $vl_latest - $vl_covered;
            } else if($records->leave_id == 2) {
                $sl_latest = $leaveCardBalance->sl_bal;
                $sl_covered = number_format($daysCovered, 2);
                $sl_bal = $sl_latest - $sl_covered;
            }

            $sheet->setCellValue('F42', $currentTimestamp ?? '');

            $sheet->setCellValue('F45', $vl_latest ?? 0);
            $sheet->setCellValue('F46', $vl_covered ?? 0);
            $sheet->setCellValue('F47', $vl_bal ?? 0);

            $sheet->setCellValue('G45', $sl_latest ?? 0);
            $sheet->setCellValue('G46', $sl_covered ?? 0);
            $sheet->setCellValue('G47', $sl_bal ?? 0);

            $sheet->setCellValue('E33', $daysCovered . ($daysCovered > 1 ? ' days' : ' day'));
            $sheet->setCellValue('E35', implode(', ', $dates));
    
            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save('php://output');
            }, ucwords($records->employee->personal->lastname) . '_Leave_Application' .  '.xlsx');
        
        } catch (\Exception $e) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops', 
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function getLeaveCard() {

        $employee_no = $this->user_id;

        $currentMonth = strtoupper(Carbon::now()->format('F'));
        $currentYear = Carbon::now()->year;


        $leaveCardBalance = EmployeeLeaveCard::where('employee_no', $employee_no)
            ->where('period', $currentMonth)
            ->where('year', $currentYear)
            ->orderBy('year', 'asc')
            ->first();

        return $leaveCardBalance;
    }

    private function getEmployeeLeaveCardBalances() {

         $this->employee_no = Auth::user()->employee_no;

        $employee = EmployeeInformation::where('employee_no', $this->employee_no)->first();

        if (!$employee) {
            return redirect()->route('employee.dashboard');
        }

        $leaveCardService = new LeaveCardService;

        $sortedRecords = $leaveCardService->getLeaveCard($this->employee_no);

        $this->leaverecords = $sortedRecords;

        $this->activeYear = array_key_last($sortedRecords->toArray());

        // Reset arrays
        $this->period = [];
        $this->particulars = [];
        $this->vl_earned = [];
        $this->vl_aut_w_pay = [];
        $this->vl_bal = [];
        $this->vl_aut_wo_pay = [];
        $this->sl_earned = [];
        $this->sl_aut_w_pay = [];
        $this->sl_bal = [];
        $this->sl_aut_wo_pay = [];
        $this->remarks = [];
        $this->vl_total_bal = [];
        $this->sl_total_bal = [];

        foreach ($sortedRecords as $year => $recordData) {
            $this->total_bal[$year] = $recordData['previous_bal'];
            foreach ($recordData['items'] as $record) {
                $this->particulars[$year][] =  $record['particulars'];
                $this->period[$year][] = $record['period'];
                $this->vl_earned[$year][] = $record['vl_earned'] ?? 0;
                $this->vl_aut_w_pay[$year][] = $record['vl_aut_w_pay'] ?? 0;
                $this->vl_bal[$year][] = $record['vl_bal'] ?? 0;
                $this->vl_aut_wo_pay[$year][] = $record['vl_aut_wo_pay'] ?? 0;
                $this->sl_earned[$year][] = $record['sl_earned'] ?? 0;
                $this->sl_aut_w_pay[$year][] = $record['sl_aut_w_pay'] ?? 0;
                $this->sl_bal[$year][] = $record['sl_bal'] ?? 0;
                $this->sl_aut_wo_pay[$year][] = $record['sl_aut_wo_pay'] ?? 0;
                $this->remarks[$year][] = $record['remarks'] ?? '';
            }
        }

    }

    private function getLeaveCredits() {
        
        /**
         * -----------------------------
         * CURRENT MONTH LEAVE CARD
         * -----------------------------
         */
         $employee_no = Auth::user()->employee_no;
        $this->currentMonth = strtoupper(now()->format('F')); 
        $year = now()->year;

         $this->dtrDate = Carbon::parse($this->currentMonth . ' ' . $year);

        $this->currentMonthCard = EmployeeLeaveCard::where('employee_no', $employee_no)
            ->where('year', $year)
            ->where('period', $this->currentMonth)
            ->first();

        /**
         * -----------------------------
         * ONLY VL AND SL LEAVE TYPES WITH BALANCES
         * -----------------------------
         */
        $leaveTypes = LeaveType::whereIn('code', ['VL', 'SL'])->get();

        $this->leaveBalances = $leaveTypes->map(function ($type) {
            $balance = 0;

            if ($this->currentMonthCard) {
                $balance = match($type->code) {
                    'VL' => $this->currentMonthCard->vl_bal,
                    'SL' => $this->currentMonthCard->sl_bal,
                    default => 0,
                };
            }

            return [
                'code'    => $type->code,
                'name'    => $type->name,
                'balance' => $balance,
            ];
        });

    }
       

    public function remove(bool $isNotify = true, int $id = null) {

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Please be informed that you are about to delete your leave application <b>#' . strtoupper(format_id($id, 6)) . '</b>. Once this action is completed, it cannot be undone or reversed!';
            $action = 'remove';

            $this->selected_id = $id;
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        }  else {

            $record = EmployeeLeave::find($this->selected_id);
                
            if($record) {
                
                $record->isDeleted = true;
                $record->save();

                $this->dispatch('alert', [
                    'status' => 'success',
                    'title' => 'Success!', 
                    'id' => $this->selected_id,
                    'isRemoveRowDT' => true,
                    'message' => 'Leave Application #' . strtoupper(format_id($record->id, 6)) . ' was deleted successfully.' 
                ]);
            } else {
                return $this->dispatch('alert', [
                    'showAlert' => true,
                    'status' => 'error',
                    'title' => 'Oops!', 
                    'isRemoveRowDT' => false,
                    'message' => 'Error: ID does not exists' 
                ]);
            }
        }
    }

    public function cancel(bool $isNotify = true, int $id = null) {

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Please be informed that you are about to cancel your leave application <b>#' . strtoupper(format_id($id, 6)) . '</b>. Once this action is completed, it cannot be undone or reversed!';
            $action = 'cancel';

            $this->selected_id = $id;
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        }  else {

            $record = EmployeeLeave::find($this->selected_id);
                
            if($record) {
                
                $record->status = 'cancelled';
                $record->save();

                $this->dispatch('alert', [
                    'status' => 'success',
                    'title' => 'Success!', 
                    'id' => $this->selected_id,
                    'isRemoveRowDT' => true,
                    'message' => 'Leave Application #' . strtoupper(format_id($record->id, 6)) . ' was cancelled successfully.' 
                ]);
            } else {
                return $this->dispatch('alert', [
                    'showAlert' => true,
                    'status' => 'error',
                    'title' => 'Oops!', 
                    'isRemoveRowDT' => false,
                    'message' => 'Error: ID does not exists' 
                ]);
            }
        }
    }

    public function render()
    {
        $query = EmployeeLeave::where('employee_no', $this->user_id)
            ->where('isDeleted', false);

        if (!empty($this->status) && $this->status !== 'all') {
            $status = $this->status === 'granted' ? 'approved' : $this->status;
            $query->where('status', $status);
        }

        $records = $query->latest()->paginate($this->entries);

        $this->applications = [
            'leave' => [
                'title' => 'Leave Application',
                'count' => EmployeeLeave::where('employee_no', $this->user_id)
                    ->where('status', 'pending')
                    ->count(),
                'route' => 'employee.leave',
            ],
            'atro' => [
                'title' => 'ATRO Application',
                'count' => EmployeeAtro::where('employee_no', $this->user_id)
                    ->where('status', 'pending')
                    ->count(),
                'route' => 'employee.atro',
            ],
            /*'time_adjustments' => [
                'title' => 'Time Adjustments',
                'count' => EmployeeTimeAdjustments::where('employee_no', $employee_no)
                    ->where('status', 'pending')
                    ->count(),
                'route' => 'employee.time-adjustments',
            ],*/
            'oba' => [
                'title' => 'OB Application',
                'count' => EmployeeBusinessSlip::where('employee_no', $this->user_id)
                    ->where('status', 'pending')
                    ->count(),
                'route' => 'employee.obs.index',
            ]
        ];

        return view('livewire.employee.leave.index', [
            'records' => $records,
            'applications' => $this->applications,
            'leaveBalances' =>  $this->leaveBalances
        ]);
    }


}
