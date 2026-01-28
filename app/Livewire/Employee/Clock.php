<?php

namespace App\Livewire\Employee;

use App\Models\EmployeeTimelogs;
use App\Models\EmployeeInformation;
use App\Services\ClockInOutService;
use App\Services\DailyTimeRecordService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Clock extends Component
{


    use WithFileUploads;

    public $bsd_emp_identical;
    public $employee_id;
    public $employee_no;
    public $gps_location;
    public $isFaceDetected;
    public $isToHide = false;
    public $status;
    public $entry;
    public $imageCaptured;
    public $isForcedOut = false;
    public $captured_at;
    public $accomplishment;
    public $requires_accomplishment = false;
    public $logs = [];
    public $manipulate_timestamp = '07:00';
    public $upload_accomplishment;

    protected $listeners = [
        'imageCaptured',
        'resetCapture',
        'confirmClock',
        'saveAccomplishment'
    ];

    protected $rules = [
        'upload_accomplishment' => 'required|file|mimes:pdf|max:5120', // 5MB max
    ];

    protected $validationAttributes = [
    ];

    public function mount(): void
    {

       
        $this->bsd_emp_identical = config('app.bsd_emp_identical');
        $this->employee_no = Auth::user()->employee_no;

        $employee = EmployeeInformation::where('employee_no', $this->employee_no)->first();
        if (!$employee) {
            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Error',
                'message' => 'Employee not found.'
            ]);
            return;
        }

        $this->employee_id = $this->bsd_emp_identical ? $employee->employee_no : $employee->bsd_no;

        $this->toggleStatus();
    }

    public function loadRecords()
    {

      
        $this->employee_no = Auth::user()->employee_no;
        $this->bsd_emp_identical = config('app.bsd_emp_identical');
    }

    // Location temporarily disabled.


    public function showLogs()
    {
       $this->logs = $this->getLogs();
        $this->dispatch('showModal', ['modal' => 'logs_modal']);
    }

    private function getLogs()
    {
        $records = EmployeeTimelogs::with('employee.personal')
            ->where('employee_id', $this->employee_id)
            ->whereMonth('timestamp', now()->month)
            ->whereYear('timestamp', now()->year)
            ->get();

        return $records
            ->groupBy(fn($record) => optional(Carbon::parse($record->timestamp))->format('j/n/Y') . '|' . ($record->employee_id ?? 'undefined'))
            ->filter()
            ->map(function ($logs, $key) {
                [$date, $employee_id] = explode('|', $key);
                $logs = $logs->sortBy('timestamp')->values();

                $formatLog = fn($log) => [
                    'time' => optional(Carbon::parse($log->timestamp))->format('H:i:s'),
                    'captured_image' => $log->captured_image,
                    'captured_location' => $log->captured_location,
                    'accomplishment' => $log->accomplishment ?? null
                ];

                $baseData = [
                    'date' => $date,
                    'bsd_no' => $employee_id,
                    'employee' => optional($logs->first())->employee,
                    'origin' => optional($logs->first())->origin,
                ];

                $count = $logs->count();
                $lastHasAccomplishment = !empty(optional($logs->last())->accomplishment);

                if ($count === 2 && $lastHasAccomplishment) {
                    return array_merge($baseData, ['logs' => [
                        $formatLog($logs[0]),
                        [],
                        [],
                        $formatLog($logs[1]),
                    ]]);
                }

                if ($count === 3 && $lastHasAccomplishment) {
                    return array_merge($baseData, ['logs' => [
                        $formatLog($logs[0]),
                        $formatLog($logs[1]),
                        [],
                        $formatLog($logs[2]),
                    ]]);
                }

                return array_merge($baseData, ['logs' => $logs->map($formatLog)->values()->all()]);
            })
            ->sortByDesc(fn($item) => Carbon::createFromFormat('j/n/Y', $item['date']))
            ->values();
    }

   
    public function toggleStatus()
    {
        $service = app(DailyTimeRecordService::class);
        $shiftSchedule = $service->getShiftSchedule($this->employee_no);
        $lunchTracking = filter_var(config('app.lunch_tracking', true), FILTER_VALIDATE_BOOLEAN);
        $hasBreaktime = $lunchTracking && ($shiftSchedule->is_breaktime_required ?? false);

        $timestamp = now()->format('Y-m-d');
        $bsd_no = $this->bsd_emp_identical ? $this->employee_no : $service->getBsdNo($this->employee_no);

        $model = EmployeeTimelogs::where('employee_id', $bsd_no)->where('timestamp', 'LIKE', "{$timestamp}%");
        $clockRecords = $model->get();
        $entry = $model->count();

        $hasAccomplishment = $clockRecords->contains(fn($record) => !empty($record->accomplishment));
        $this->entry = $entry;

        if ($hasAccomplishment) {
            $this->status = 'Done';
            $this->requires_accomplishment = false;
            return;
        }

        if ($hasBreaktime) {
            $this->status = match ($entry) {
                0 => 'Clock In',
                1 => 'Lunch Out',
                2 => 'Lunch In',
                3 => 'Clock Out',
                default => 'Done',
            };
        } else {
            $this->status = match ($entry) {
                0 => 'Clock In',
                1 => 'Clock Out',
                default => 'Done',
            };
        }

        $this->dispatch('loadDefaults');

        // Accomplishment is required every Friday during Clock Out.
        $this->requires_accomplishment = $this->shouldRequireAccomplishment();
    }

    private function shouldRequireAccomplishment(): bool
    {
        // "Every Friday clock out" — if status is Clock Out (or forced-out) and it's Friday.
        return now()->isFriday() && ($this->status === 'Clock Out' || $this->isForcedOut);
    }

   public function triggerClock()
    {

        if($this->status == 'Done') {
            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'info',
                'title' => 'Please be informed',
                'message' => 'You\'ve completed today\'s work.',
            ]);
            return;
        }

        $effectiveEntry = $this->getEffectiveEntry();

        if (!$this->isFaceDetected) {
            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'info',
                'title' => 'No Face Detected',
                'message' => 'No face detected. Please ensure your face is visible to the camera.',
            ]);
            return;
        }

       /* if (is_null($this->gps_location)) {
            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'info',
                'title' => 'No Location Detected',
                'message' => 'No location detected. Please make sure to enable your location or GPS.',
            ]);
            return;
        }*/

        if (empty($this->imageCaptured)) {
            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Image Missing',
                'message' => 'No image was captured.',
            ]);

            return;
        }

        // Require accomplishment report on Friday clock-out.
        if ($this->shouldRequireAccomplishment() && empty($this->accomplishment)) {
            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Accomplishment Report Required',
                'message' => 'Please upload your accomplishment report before clocking out (required every Friday).',
            ]);
            return;
        }

        $service = app(ClockInOutService::class);
        $toProcess = $this->buildToProcess();

        $response = $service->process((string) $effectiveEntry, $toProcess, (string) $this->employee_no);

        // Use the app-wide confirmation modal for "confirm" scenarios (late/undertime, etc).
        if (($response['alert'] ?? null) === 'confirm') {
            $this->dispatch('showConfirmation', [
                'title' => $response['title'] ?? 'Are you sure to continue?',
                'message' => $response['message'] ?? '',
                'action' => 'confirmClock',
            ]);
            return;
        }

        $this->dispatch('alert', [
            'showAlert' => true,
            'status' => $response['alert'] ?? 'info',
            'title' => $response['title'] ?? 'Please be informed',
            'message' => $response['message'] ?? '',
        ]);

        if (($response['status'] ?? false) === true) {
            $this->toggleStatus();
        }
    }

    public function confirmClock($payload = null)
    {
        // Same safety checks as triggerClock (confirmation happens client-side).
        if ($this->status === 'Done') {
            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'info',
                'title' => 'Please be informed',
                'message' => 'You\'ve completed today\'s work.',
            ]);
            return;
        }

        if (!$this->isFaceDetected || empty($this->imageCaptured)) {
            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Unable to proceed',
                'message' => 'Please capture a photo with face detection before proceeding.',
            ]);
            return;
        }

        $service = app(ClockInOutService::class);
        $response = $service->insertLog($this->getEffectiveEntry(), $this->buildToProcess(), (string) $this->employee_no);

        $this->dispatch('alert', [
            'showAlert' => true,
            'status' => $response['alert'] ?? 'success',
            'title' => $response['title'] ?? 'Recorded!',
            'message' => $response['message'] ?? '',
        ]);

        $this->toggleStatus();
    }

    private function buildToProcess(): array
    {
        return [
            'timestamp' => Carbon::now(),
            'captured_image' => $this->imageCaptured,
            'captured_location' => $this->gps_location,
            'accomplishment' => $this->accomplishment ?? null,
        ];
    }

    private function getEffectiveEntry(): int
    {
        // If user force-clocks out during lunch-out, treat it as a clock-out entry.
        if ($this->isForcedOut) {
            return 3;
        }

        return (int) $this->entry;
    }

    public function delete()
    {
        $date = now()->format('Y-m-d');
        EmployeeTimelogs::where('timestamp', 'like', "%{$date}%")->delete();
        $this->toggleStatus();
    }

    public function imageCaptured($imageData, $isFaceDetected = false, $isForcedOut = false, $location = null)
    {
        $this->imageCaptured = $imageData;
        $this->isFaceDetected = $isFaceDetected;
        $this->isForcedOut = $isForcedOut;
        $this->captured_at = now()->toDateTimeString();
        $this->gps_location = $location;
        $this->requires_accomplishment = $this->shouldRequireAccomplishment();
    }

    public function resetCapture(): void
    {
        $this->imageCaptured = null;
        $this->isFaceDetected = false;
        $this->isForcedOut = false;
        $this->captured_at = null;
        $this->upload_accomplishment = null;
        $this->accomplishment = null;
        $this->requires_accomplishment = false;
        $this->resetErrorBag();
    }

    public function updatedUploadAccomplishment()
    {
        
        $this->validateOnly('upload_accomplishment');
    }


    public function saveAccomplishment()
    {
        $this->resetErrorBag();

        // Safety check
        if (!$this->upload_accomplishment) {
            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Missing File',
                'message' => 'Please upload an accomplishment report.',
            ]);
            return;
        }

        // Store the file
        $file = $this->upload_accomplishment;
        $fileName = $this->employee_no . '_' . time() . '.' . $file->getClientOriginalExtension();
        $disk = env('USE_S3_STORAGE', false) ? 's3' : 'public';
        $file->storeAs('accomplishments', $fileName, $disk);

        // Save the filename to DB or property
        $this->accomplishment = $fileName;

        // Optional: reset the property after upload if you want to allow re-upload
        $this->upload_accomplishment = null;

        // Trigger clock or next step (this will show Recorded!/confirm, etc.)
        $this->triggerClock();
    }

    public function render()
    {
        return view('livewire.employee.clock');
    }
}
