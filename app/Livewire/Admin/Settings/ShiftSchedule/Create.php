<?php

namespace App\Livewire\Admin\Settings\ShiftSchedule;

use App\Models\ShiftSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use PhpParser\Node\Expr\AssignOp\ShiftLeft;

class Create extends Component
{
    public $id;
    public $name;
    public $description;
    public $shift_duration = '';
    public $is_breaktime_required = true;
    public $allow_anytime_clockin = false;
    public $allow_anytime_clockout = false;
    public $earliest_in = '07:00';
    public $latest_in = '09:00';
    public $start_shift = '07:00';
    public $break_out = '12:00';
    public $break_in = '13:00';
    public $end_shift = '16:00';
    public $work_setup;
    public $isFlexible = false;
    public $isHybrid = false;
    public $min_ot_mins = '120';
    public $max_ot_time = '22:00';
    public $mobile_earliest_clockin = '08:00';
    public $mobile_latest_clockin = '08:00';
    public $web_earliest_clockin = '07:00';
    public $web_latest_clockin = '09:00';

    protected $listeners = ['save'];

    public function onSelect($property)
    {
        if ($property === 'shift_duration') {
            $this->isFlexible = ($this->shift_duration === 'flexible');
        }
    }

    public function changeWorkSetup() {
        $setup = $this->work_setup;

        if($setup == 'hybrid') {
            $this->isHybrid = true;
        } else {
            $this->isHybrid = false;
        }

    }

    public function rules()
    {
        $rules = [
            'name' => 'required|string',
            'description' => 'required|string',
            'shift_duration' => 'required|in:flexible,standard,extended,full-day,compressed,part-time',
            'work_setup' => 'required|in:onsite,hybrid',
            'is_breaktime_required' => 'required|boolean',
            'allow_anytime_clockin' => 'nullable|boolean',
            'allow_anytime_clockout' => 'nullable|boolean',
        ];

        if ($this->is_breaktime_required) {
            $rules = array_merge($rules, [
                'break_in' => 'required|date_format:H:i',
                'break_out' => 'required|date_format:H:i',
            ]);
        }
    
        if (!$this->isFlexible) {
            $rules = array_merge($rules, [
                'start_shift' => 'required|date_format:H:i',
                'end_shift' => 'required|date_format:H:i|after:start_shift', // Ensure end shift is after start shift
            ]);
        } else {
            // Add specific rules for flexible shifts
            $rules = array_merge($rules, [
                'earliest_in' => 'required|date_format:H:i',
                'latest_in' => 'required|date_format:H:i|after:earliest_in', // Ensure latest_in is after earliest_in
            ]);
        }
    
        // Custom validator to handle shift duration and break times
        $this->withValidator(function ($validator) {
            if (!$this->isFlexible) {
                $validator->after(function ($validator) {
                    // Validate presence of start and end shift times
                    if (!$this->start_shift || !$this->end_shift) {
                        $validator->errors()->add('start_shift', 'Start and End shift times are required.');
                        return;
                    }
    
                    // Convert start and end shift times to Carbon instances
                    try {
                        $fromTime = Carbon::createFromFormat('H:i', $this->start_shift);
                        $toTime = Carbon::createFromFormat('H:i', $this->end_shift);
                    } catch (\Exception $e) {
                        $validator->errors()->add('start_shift', 'Invalid start or end shift time format.');
                        return;
                    }
    
                    $shiftHours = $fromTime->diffInHours($toTime);
    
                    // Set expected hours based on shift duration
                    $expectedHours = match ($this->shift_duration) {
                        'standard' => 9,
                        'extended' => 13,
                        'full-day' => 25,
                        'compressed' => 11,
                        'part-time' => 0,
                        default => null,
                    };

                    if ($expectedHours !== null && !$this->is_breaktime_required && $expectedHours > 0) {
                        $expectedHours -= 1;
                    }
    
                    // Validate shift duration based on shift type
                    if ($this->shift_duration !== 'flexible' && $this->shift_duration !== 'part-time' && $shiftHours !== $expectedHours) {
                        $validator->errors()->add('end_shift', "The shift duration must be exactly {$expectedHours} hours (including break time) for a {$this->shift_duration} shift.");
                    } elseif ($this->shift_duration === 'part-time' && $shiftHours >= 8) {
                        $validator->errors()->add('end_shift', 'The shift duration must be below 8 hours for a part-time shift.');
                    }
    
                    // Validate break time duration (must be exactly 1 hour)
                    if ($this->is_breaktime_required) {
                        try {
                            $breakTimeIn = Carbon::createFromFormat('H:i', $this->break_in);
                            $breakTimeOut = Carbon::createFromFormat('H:i', $this->break_out);
                        } catch (\Exception $e) {
                            $validator->errors()->add('break_in', 'Invalid break-in or break-out time format.');
                            return;
                        }
    
                        $breakDuration = $breakTimeIn->diffInMinutes($breakTimeOut);
    
                        if ($breakDuration !== 60) {
                            $validator->errors()->add('break_out', 'The break time must be exactly 1 hour between break time in and break time out.');
                        }
    
                        // Ensure break-in and break-out times are within the shift times
                        if (($breakTimeIn->lt($fromTime) || $breakTimeIn->gt($toTime)) || ($breakTimeOut->lt($fromTime) || $breakTimeOut->gt($toTime))) {
                            $validator->errors()->add('break_in', 'The break-in and break-out times must be between the start and end shift times.');
                        }
                    }
                });
            }
        });
    
        return $rules;
    }

    protected function messages()
    {
        return [
            'name.required' => 'The name field is required.',
            'description.required' => 'The description field is required.',
            'shift_duration.required' => 'Please select a shift duration.',
            'shift_duration.in' => 'Invalid shift duration value.',
            'start_shift.required' => 'The start time is required.',
            'end_shift.required' => 'The end time is required.',
            'break_in.required' => 'The break-in time is required.',
            'break_out.required' => 'The break-out time is required.',
            'earliest_in.required' => 'The earliest in time is required for flexible shifts.',
            'latest_in.required' => 'The latest in time is required for flexible shifts.',
        ];
    }

    public function save(bool $isNotify = true)
    {

        if (Gate::denies('write shift-schedule')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!', 
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        if ($isNotify) {
            $this->dispatch('showConfirmation', [
                'title' => 'Are you sure to continue?',
                'message' => 'The action cannot be undone or reverted!',
                'action' => 'save'
            ]);
        } else {

            $this->validate();

            try {

                $mobileEarliestClockin = $this->work_setup === 'hybrid' ? $this->mobile_earliest_clockin : null;
                $mobileLatestClockin = $this->work_setup === 'hybrid' ? $this->mobile_latest_clockin : null;
                $webEarliestClockin = $this->work_setup === 'hybrid' ? $this->web_earliest_clockin : null;
                $webLatestClockin = $this->work_setup === 'hybrid' ? $this->web_latest_clockin : null;

                // Build data for update or create
                $data = [
                    'name' => $this->name,
                    'description' => $this->description,
                    'shift_duration' => $this->shift_duration,
                    'is_breaktime_required' => (int) $this->is_breaktime_required,
                    'allow_anytime_clockin' => (int) $this->allow_anytime_clockin,
                    'allow_anytime_clockout' => (int) $this->allow_anytime_clockout,
                    'work_setup' => $this->work_setup,
                    'min_ot_mins' => $this->min_ot_mins,
                    'max_ot_time' => $this->max_ot_time,
                    'break_out' => $this->break_out,
                    'break_in' => $this->break_in,
                    'mobile_earliest_clockin' => $mobileEarliestClockin,
                    'mobile_latest_clockin' => $mobileLatestClockin,
                    'web_earliest_clockin' => $webEarliestClockin,
                    'web_latest_clockin' => $webLatestClockin,
                ];

                if ($this->isFlexible) {
                    $data['earliest_in'] = $this->earliest_in;
                    $data['latest_in'] = $this->latest_in;
                } else {
                    $data = array_merge($data, [
                        'start_shift' => $this->start_shift,
                        'end_shift' => $this->end_shift,
                    ]);
                }

                $record = ShiftSchedule::updateOrCreate(['id' => $this->id], $data);

                $message = is_null($this->id) ? 'has been added.' : 'has been updated.';
                
                $this->dispatch('alert', [
                    'showAlert' => true,
                    'status' => 'success',
                    'title' => 'Yey!',
                    'message' => 'Shift schedule `' . $record->name . '` ' . $message
                ]);

                if (is_null($this->id)) {
                    $this->resetExcept('user_id');
                }

            } catch (\Exception $e) {
                $this->dispatch('alert', [
                    'showAlert' => true,
                    'status' => 'error',
                    'title' => 'Oops',
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.shift-schedule.create');
    }
}
