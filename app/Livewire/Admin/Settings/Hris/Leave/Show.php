<?php

namespace App\Livewire\Admin\Settings\Hris\Leave;

use App\Http\Controllers\Admin\Services\LeaveCardService;
use App\Imports\LeaveCreditsImport;
use App\Models\EmployeeInformation;
use App\Models\EmployeeLeaveCard;
use App\Models\LeaveCredits;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use DateTime;

class Show extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $employee_no;
    public $selected_id;
    public $id;
    public $entries = 10;
    public $search = '';
    public $credits = [];
    public $sl_credits = [];
    public $vl_credits = [];
    public $as_of = [];
    public $total_sl_credits = [];
    public $total_vl_credits = [];
    public $has_leave_card = [];
    public $importFile;
    public $isVlSL;
    public $currentMonth;

    public $leaveName;
    protected $listeners = ['resetCredits'];

    protected $paginationTheme = 'bootstrap';

    // Manual leave credit adjustment (for VL/SL leave cards)
    public $manual_employee_no;
    public $manual_year;
    public $manual_period;
    public $manual_vl_add = 0;
    public $manual_sl_add = 0;
    public $manual_note = '';
    public $months = [
        'JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
        'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'
    ];

    public function mount()
    {
        $this->manual_year = (int) now()->format('Y');
        $this->manual_period = strtoupper(now()->format('F'));
        $this->loadRecords();
    }

    public function loadRecords()
    {
        $employees = EmployeeInformation::with(['personal'])
            ->get();

        $leaveType = LeaveType::where('id', $this->id)
            ->first();

        $leaveTypes = strtolower($leaveType->code);

        $this->leaveName = $leaveType->name;

        $currentMonth = strtoupper(Carbon::now()->format('F'));
        $currentYear = Carbon::now()->year;
        $currentMonthYear = Carbon::now()->format('Y-m');

        $this->currentMonth = $currentMonthYear;

        $this->vl_credits = [];
        $this->sl_credits = [];
        $this->total_vl_credits = [];
        $this->total_sl_credits = [];
        $this->as_of = [];

        if($this->id == 1 || $this->id == 2) {
            foreach ($employees as $employee) {

                $currentleaveCredits = EmployeeLeaveCard::where('employee_no', $employee['employee_no'])
                    ->where('year', $currentYear)
                    ->orderBy('year', 'asc')
                    ->get();

                $currentMonthCredits = $currentleaveCredits->filter(function ($item) use ($currentMonth) {
                    return $item->period === $currentMonth; // Compare period with the current month
                })->first();

                $leaveTotalCredits = $currentleaveCredits->last();

                $leaveTotalCreditsVL = $leaveTotalCredits ? $leaveTotalCredits->vl_bal ?? 0 : 0;
                $leaveTotalCreditsSL = $leaveTotalCredits ? $leaveTotalCredits->sl_bal ?? 0 : 0;

                $this->vl_credits[$employee['employee_no']] = $currentMonthCredits ? $currentMonthCredits->vl_bal : 0;
                $this->sl_credits[$employee['employee_no']] = $currentMonthCredits ? $currentMonthCredits->sl_bal : 0;
                $this->as_of[$employee['employee_no']] = ($leaveTotalCreditsVL <= 0 || $leaveTotalCreditsSL <= 0) ? '' : $currentMonthYear;
                $this->total_vl_credits[$employee['employee_no']] = $leaveTotalCreditsVL  ?? null;
                $this->total_sl_credits[$employee['employee_no']] = $leaveTotalCreditsSL  ?? null;

                $this->has_leave_card[$employee['employee_no']] = ($leaveTotalCreditsVL <= 0 || $leaveTotalCreditsSL <= 0) ? false : true;
            }

            $this->isVlSL = true;

        } else {
            $leaveCredits = LeaveCredits::where('leave_type_id', $this->id)->get();
            foreach ($employees as $employee) {
                $leaveCredit = $leaveCredits->firstWhere('employee_no', $employee['employee_no']);

                $this->credits[$employee['employee_no']] = $leaveCredit ? $leaveCredit->credits : 0;
                $this->as_of[$employee['employee_no']] = $leaveCredit ? $leaveCredit->as_of : null;
                $this->has_leave_card[$employee['employee_no']] = false;
            }

            $this->isVlSL = false;
        }

    }

    protected function rules(bool $isVlSL, string $employee_no)
    {

        if($isVlSL) {
            return [
                "vl_credits.$employee_no" => 'required|numeric|gt:0',
                "sl_credits.$employee_no" => 'required|numeric|gt:0',
                    "as_of.$employee_no" => [
                    'required',
                    'date_format:Y-m',
                    'before_or_equal:' . $this->currentMonth,
                ],
            ];
        } else {
            return [
                "credits.$employee_no" => 'required|numeric|gt:0',
                "as_of.$employee_no" => 'required',
            ];
        }

    }

    protected function messages(string $employee_no)
    {
        return [
            "vl_credits.$employee_no.required" => '*required',
            "vl_credits.$employee_no.numeric" => '*number only',
            "vl_credits.$employee_no.gt" => '*must be greater than 0',

            "sl_credits.$employee_no.required" => '*required',
            "sl_credits.$employee_no.numeric" => '*number only',
            "sl_credits.$employee_no.gt" => '*must be greater than 0',

            "credits.$employee_no.required" => '*required',
            "credits.$employee_no.numeric" => '*number only',
            "credits.$employee_no.gt" => '*must be greater than 0',

            "as_of.$employee_no.required" => '*required',
        ];
    }

    public function resetCredits(bool $isNotify = true, ?string $employee_no = null) {

        if (Gate::denies('write leave-credits')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!',
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Please be informed that you are about to reset this employee\'s leave card. Once this action is processed, it cannot be undone or reversed!';
            $action = 'resetCredits';

            $this->selected_id = $employee_no;
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        } else {


            // Retrieve the EmployeeLeaveCard records
            $leaveCard = EmployeeLeaveCard::where('employee_no', $this->selected_id);

            $leaveCardData = $leaveCard->get();
            // Ensure records exist before performing any actions
            if ($leaveCardData->isNotEmpty()) {
                // Reset leave card values
                $leaveCardData->each(function ($card) {
                    $card->{'particulars'} = '';
                    $card->{'vl_earned'} = '';
                    $card->{'vl_aut_w_pay'} = '';
                    $card->{'vl_aut_wo_pay'} = '';
                    $card->{'vl_bal'} = '';

                    $card->{'sl_earned'} = '';
                    $card->{'sl_aut_w_pay'} = '';
                    $card->{'sl_aut_wo_pay'} = '';
                    $card->{'sl_bal'} = '';
                    $card->{'remarks'} = '';
                    $card->save();
                });


                foreach ([1, 2] as $leave_id) {
                    $record = LeaveCredits::where('employee_no', $this->selected_id)
                        ->where('leave_type_id', $leave_id)
                        ->first();

                    if ($record) {
                        $record->credits = 0;
                        $record->as_of = '';
                        $record->save();
                    }
                }


                // Check if the last leave card has empty 'vl_bal' and 'sl_bal' fields
                $lastLeaveCard = $leaveCardData->last();

                // Only delete if the last card has empty values for 'vl_bal' and 'sl_bal'
                if (empty($lastLeaveCard->vl_bal) && empty($lastLeaveCard->sl_bal)) {
                    // Deleting only the last leave card if conditions are met
                    $leaveCard->delete();
                }

                // Reload records
                $this->loadRecords();

                // Dispatch success alert
                $this->dispatch('alert', [
                    'status' => 'success',
                    'title' => 'Success!',
                    'message' => 'Leave Card for ' . strtoupper($employee_no) . ' has been reset successfully'
                ]);
            } else {
                // If records don't exist, dispatch error alert
                $this->dispatch('alert', [
                    'showAlert' => true,
                    'status' => 'error',
                    'title' => 'Oops!',
                    'message' => 'Error: ID does not exist'
                ]);
            }
        }

    }

    public function save(string $employee_no)
    {

        if (Gate::denies('write leave-credits')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!',
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        $isVlSL = $this->id == 1 || $this->id == 2 ? true : false;

        $this->validate($this->rules($isVlSL, $employee_no), $this->messages($employee_no));

        try {

            DB::beginTransaction();

            if($this->id == 1 || $this->id == 2) {

                $vl_credits = $this->vl_credits[$employee_no];

                if (!is_null($vl_credits)) {
                    $leaveCredit = LeaveCredits::firstOrNew(
                        [
                            'employee_no' => $employee_no,
                            'leave_type_id' => 1,
                        ]
                    );

                    $leaveCredit->credits = $vl_credits;
                    $leaveCredit->as_of = $this->as_of[$employee_no] ?? null;
                    $leaveCredit->save();

                    $leaveCardExists = EmployeeLeaveCard::where('employee_no', $employee_no)
                        ->where('year', Carbon::now()->year)
                        ->whereNotNull("vl_bal")
                        ->exists();


                    if(!$leaveCardExists) {
                        $LeaveCardService = new LeaveCardService;
                        $LeaveCardService->init($employee_no, 'firstime', [
                            'employee_no' => $employee_no,
                            'leave_id' => 1,
                            'credits' => $vl_credits,
                            'as_of' => $this->as_of[$employee_no] ?? null
                        ]);

                    }
                }

                $sl_credits = $this->sl_credits[$employee_no];

                if (!is_null($sl_credits)) {

                    $leaveCredit = LeaveCredits::firstOrNew(
                        [
                            'employee_no' => $employee_no,
                            'leave_type_id' => 2,
                        ]
                    );

                    $leaveCredit->credits = $sl_credits;
                    $leaveCredit->as_of = $this->as_of[$employee_no] ?? null;
                    $leaveCredit->save();

                    $leaveCardExists = EmployeeLeaveCard::where('employee_no', $employee_no)
                        ->where('year', Carbon::now()->year)
                        ->whereNotNull("sl_bal")
                        ->exists();


                    if(!$leaveCardExists) {
                        $LeaveCardService = new LeaveCardService;
                        $LeaveCardService->init($employee_no, 'firstime', [
                            'employee_no' => $employee_no,
                            'leave_id' => 2,
                            'credits' => $sl_credits,
                            'as_of' => $this->as_of[$employee_no] ?? null
                        ]);

                    }
                }


            } else {

                $credits = $this->credits[$employee_no];

                if (!is_null($credits)) {

                    $leaveCredit = LeaveCredits::firstOrNew(
                        [
                            'employee_no' => $employee_no,
                            'leave_type_id' => $this->id,
                        ]
                    );

                    $leaveCredit->credits = $credits;
                    $leaveCredit->as_of = $this->as_of[$employee_no];
                    $leaveCredit->save();

                }

            }

            DB::commit();

            $this->loadRecords();

            $this->dispatch('alert', [
                'status' => 'success',
                'title' => 'Saved!',
                'showAlert' => true,
                'message' => 'Leave credits updated successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Error!',
                'showAlert' => true,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function select_employee(string $employee_no ) {
        $this->selected_id = $employee_no ?? null;
    }

    public function openManualAdd(string $employee_no): void
    {
        $this->manual_employee_no = $employee_no;
        $this->manual_year = (int) now()->format('Y');
        $this->manual_period = strtoupper(now()->format('F'));
        $this->manual_vl_add = 0;
        $this->manual_sl_add = 0;
        $this->manual_note = '';
        $this->resetErrorBag();
    }

    public function applyManualAdd(): void
    {
        if (Gate::denies('write leave-credits')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!',
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        // Only applicable to VL/SL leave cards (leave ids 1/2 page).
        if (!($this->id == 1 || $this->id == 2)) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Not supported',
                'showAlert' => true,
                'message' => 'Manual adjustments are only supported on VL/SL pages.',
            ]);
            return;
        }

        $this->validate([
            'manual_employee_no' => 'required|string',
            'manual_year' => 'required|integer|min:2000|max:2100',
            'manual_period' => 'required|string',
            'manual_vl_add' => 'nullable|numeric|min:0',
            'manual_sl_add' => 'nullable|numeric|min:0',
            'manual_note' => 'nullable|string|max:255',
        ]);

        $vlAdd = (float) ($this->manual_vl_add ?? 0);
        $slAdd = (float) ($this->manual_sl_add ?? 0);

        if ($vlAdd <= 0 && $slAdd <= 0) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Nothing to add',
                'showAlert' => true,
                'message' => 'Please enter a VL and/or SL amount greater than 0.',
            ]);
            return;
        }

        $employeeNo = (string) $this->manual_employee_no;
        $year = (int) $this->manual_year;
        $period = strtoupper((string) $this->manual_period);

        if (!in_array($period, $this->months, true)) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Invalid period',
                'showAlert' => true,
                'message' => 'Please select a valid month.',
            ]);
            return;
        }

        DB::beginTransaction();

        try {
            $target = EmployeeLeaveCard::where('employee_no', $employeeNo)
                ->where('year', $year)
                ->where('period', $period)
                ->first();

            if (!$target) {
                DB::rollBack();
                $this->dispatch('alert', [
                    'status' => 'error',
                    'title' => 'Leave card not found',
                    'showAlert' => true,
                    'message' => "No leave card row found for {$employeeNo} ({$period} {$year}).",
                ]);
                return;
            }

            // Apply adjustment as additional "earned" credits, then recompute balances forward.
            $target->vl_earned = (float) ($target->vl_earned ?? 0) + $vlAdd;
            $target->sl_earned = (float) ($target->sl_earned ?? 0) + $slAdd;

            $stamp = 'MANUAL ADD';
            $note = trim((string) $this->manual_note);
            $parts = [];
            if ($vlAdd > 0) $parts[] = "VL +{$vlAdd}";
            if ($slAdd > 0) $parts[] = "SL +{$slAdd}";
            $msg = $stamp . ': ' . implode(', ', $parts) . ($note !== '' ? " ({$note})" : '');

            $existing = trim((string) ($target->particulars ?? ''));
            $target->particulars = $existing !== '' ? ($existing . ', ' . $msg) : $msg;
            $target->save();

            // Recompute all balances across all years (carry-forward) for this employee.
            $all = EmployeeLeaveCard::where('employee_no', $employeeNo)->get();
            $grouped = $all->groupBy('year')->sortKeys();

            $prevVl = 0.0;
            $prevSl = 0.0;

            foreach ($grouped as $yr => $items) {
                $sorted = $items->sortBy(function ($item) {
                    return DateTime::createFromFormat('F', $item->period)->format('m');
                })->values();

                foreach ($sorted as $row) {
                    $vlEarned = (float) ($row->vl_earned ?? 0);
                    $vlAutWPay = (float) ($row->vl_aut_w_pay ?? 0);
                    $slEarned = (float) ($row->sl_earned ?? 0);
                    $slAutWPay = (float) ($row->sl_aut_w_pay ?? 0);

                    $prevVl = $prevVl + $vlEarned - $vlAutWPay;
                    $prevSl = $prevSl + $slEarned - $slAutWPay;

                    $row->vl_bal = number_format($prevVl, 3, '.', '');
                    $row->sl_bal = number_format($prevSl, 3, '.', '');
                    $row->save();
                }
            }

            DB::commit();

            $this->loadRecords();
            $this->dispatch('hideModal', ['modal' => 'manualAddModal']);
            $this->dispatch('alert', [
                'status' => 'success',
                'title' => 'Saved!',
                'showAlert' => true,
                'message' => 'Manual leave credits added successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Error!',
                'showAlert' => true,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function upload_file() {

        if (Gate::denies('write leave-credits')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!',
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        $this->validate([
            'importFile' => 'required|mimes:csv',
        ], [
            'importFile.required' => 'Please select a file to upload.',
            'importFile.mimes' => 'Invalid file type. Please upload a CSV file.',
        ]);

        try {

            $path = $this->importFile->store('temp');
            $fullPath = storage_path("app/{$path}");

            // Extract the header row
            $headings = (new HeadingRowImport())->toArray($fullPath);

            $employee_nos = Excel::toArray([], $fullPath)[0] ?? [];

            array_shift($employee_nos);

            $employee_nos = array_filter(array_map(fn($row) => $row[0] ?? null, $employee_nos));
            $employee_nos = array_values(array_unique($employee_nos));

            $headerRow = array_values($headings[0][0] ?? []);

            if($this->isVlSL) {
                $requiredHeaders = [
                    'employee_no', 'year', 'period', 'particulars',
                    'vl_earned', 'vl_aut_w_pay', 'vl_bal', 'vl_aut_wo_pay',
                    'sl_earned', 'sl_aut_w_pay', 'sl_bal', 'sl_aut_wo_pay',
                    'remarks'
                ];
            } else {

                $requiredHeaders = [
                    'employee_no', 'credits', 'as_of'
                ];

            }

            $missingHeaders = array_diff($requiredHeaders, $headerRow);

            if (!empty($missingHeaders)) {
                $this->dispatch('alert', [
                    'status' => 'info',
                    'title' => 'Please be informed!',
                    'showAlert' => true,
                    'message' => 'You are importing an invalid file!',
                ]);

                return;
            }


            if($this->isVlSL) {
                EmployeeLeaveCard::whereIn('employee_no', $employee_nos)->delete();
            } else {
                LeaveCredits::whereIn('employee_no', $employee_nos)
                    ->where('leave_type_id', $this->id)
                    ->delete();
            }

            Excel::import(new LeaveCreditsImport($this->selected_id, $this->isVlSL, $this->id), $fullPath);

            $this->loadRecords();

            $this->reset('importFile');

            $this->dispatch('alert', [
                'status' => 'success',
                'title' => 'Success!',
                'showAlert' => true,
                'message' => 'Leave credits uploaded successfully.',
            ]);

        } catch (\Exception $e) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Error!',
                'showAlert' => true,
                'message' => $e->getMessage(),
            ]);
        }

    }

    public function render()
    {

        $model = EmployeeInformation::with(['personal'])
            ->whereNotNull('employee_no');

        if ($this->search) {
            $this->resetPage();
            $model->where(function ($query) {
                $query->where('employee_no', 'like', '%' . $this->search . '%')
                ->orWhereHas('personal', function ($subQuery) {
                    $subQuery->whereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ['%' . $this->search . '%']);
                });
            });
        }

        $records = $model->paginate($this->entries);

        return view('livewire.admin.settings.hris.leave.show', [
            'records' => $records,
        ]);
    }
}
