<?php

namespace App\Livewire\Employee;

use App\Models\EmployeeTimelogs;
use App\Models\EmployeeInformation;
use App\Services\ClockInOutService;
use App\Services\DailyTimeRecordService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
    public $accomplishment_link;

    protected $listeners = [
        'imageCaptured',
        'resetCapture',
        'confirmClock',
        'saveAccomplishment'
    ];

    protected $rules = [
        'accomplishment_link' => 'required|url|max:500',
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
        $showLunch = filter_var(config('app.lunch_tracking', true), FILTER_VALIDATE_BOOLEAN);
        $records = EmployeeTimelogs::with('employee.personal')
            ->where('employee_id', $this->employee_id)
            ->whereMonth('timestamp', now()->month)
            ->whereYear('timestamp', now()->year)
            ->orderBy('timestamp')
            ->get();

        return $records
            ->groupBy(fn($record) => optional(Carbon::parse($record->timestamp))->format('j/n/Y') . '|' . ($record->employee_id ?? 'undefined'))
            ->filter()
            ->map(function ($logs, $key) use ($showLunch) {
                [$date, $employee_id] = explode('|', $key);
                $logs = $logs->sortBy('timestamp')->values();

                $formatLog = fn($log) => [
                    'time' => optional(Carbon::parse($log->timestamp))->format('H:i:s'),
                    'captured_image' => $log->captured_image,
                    'captured_location' => $log->captured_location,
                    'accomplishment' => $log->accomplishment ?? null,
                ];

                $ins = [];
                $outs = [];
                foreach ($logs as $log) {
                    $status = (int) ($log->status ?? $log->status1 ?? 0);
                    $formatted = $formatLog($log);
                    if ($status === 0) {
                        $ins[] = $formatted;
                    } else {
                        $outs[] = $formatted;
                    }
                }

                $logsSlots = [null, null, null, null];
                if ($showLunch && count($ins) >= 2 && count($outs) >= 2) {
                    $logsSlots[0] = $ins[0];
                    $logsSlots[1] = $outs[0];
                    $logsSlots[2] = $ins[1];
                    $logsSlots[3] = $outs[1];
                } elseif ($showLunch) {
                    $logsSlots[0] = $ins[0] ?? null;
                    $logsSlots[3] = $outs[0] ?? null;
                } else {
                    $logsSlots[0] = $ins[0] ?? null;
                    $logsSlots[1] = $outs[0] ?? null;
                }

                return [
                    'date' => $date,
                    'bsd_no' => $employee_id,
                    'employee' => optional($logs->first())->employee,
                    'origin' => optional($logs->first())->origin,
                    'logs' => array_map(fn($s) => $s ?? [], $logsSlots),
                ];
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
        $clockRecords = $model->orderBy('timestamp')->get();
        $entry = $this->getEntryProgressFromRecords($clockRecords, $hasBreaktime);

        $allowPastMidnight = $this->shiftAllowsPastMidnightClockOut($shiftSchedule);
        if ($entry === 0 && $allowPastMidnight && now()->hour < 6) {
            $yesterday = now()->subDay()->format('Y-m-d');
            $yesterdayLogs = EmployeeTimelogs::where('employee_id', $bsd_no)
                ->where('timestamp', 'LIKE', "{$yesterday}%")
                ->orderBy('timestamp')
                ->get();
            $yesterdayEntry = $this->getEntryProgressFromRecords($yesterdayLogs, $hasBreaktime);
            if ($hasBreaktime && in_array($yesterdayEntry, [1, 3], true)) {
                $entry = $yesterdayEntry;
                $this->entry = $yesterdayEntry;
            } elseif (!$hasBreaktime && $yesterdayEntry === 1) {
                $entry = 1;
                $this->entry = 1;
            }
        } else {
            $this->entry = $entry;
        }

        $hasAccomplishment = $clockRecords->contains(function ($record) {
            $punchStatus = $this->getPunchStatus($record);
            return $punchStatus === 1 && !empty($record->accomplishment);
        });

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

        $this->requires_accomplishment = $this->shouldRequireAccomplishment();
    }

    private function getEntryProgressFromRecords($records, bool $hasBreaktime): int
    {
        $expectedSequence = $hasBreaktime ? [0, 1, 0, 1] : [0, 1];
        $progress = 0;
        $lastAcceptedStatus = null;

        foreach ($records as $record) {
            $status = $this->getPunchStatus($record);

            if (!in_array($status, [0, 1], true)) {
                continue;
            }

            // Ignore duplicate consecutive punches (e.g., double clock-in).
            if ($lastAcceptedStatus !== null && $status === $lastAcceptedStatus) {
                continue;
            }

            if ($progress < count($expectedSequence) && $status === $expectedSequence[$progress]) {
                $progress++;
                $lastAcceptedStatus = $status;
            }
        }

        return $progress;
    }

    private function getPunchStatus($record): ?int
    {
        if (isset($record->status) && $record->status !== null) {
            return (int) $record->status;
        }

        if (isset($record->status1) && $record->status1 !== null) {
            return (int) $record->status1;
        }

        return null;
    }

    /**
     * Support shift and other overnight: clock in 6pm, clock out 2am = same shift (no 12am shenanigans).
     */
    private function shiftAllowsPastMidnightClockOut($shift): bool
    {
        if (!$shift) {
            return false;
        }
        $duration = $shift->shift_duration ?? '';
        $name = $shift->name ?? '';
        $allowedDurations = ['extended', 'full-day', 'compressed', 'part-time', 'support'];
        return in_array($duration, $allowedDurations, true)
            || stripos($name, 'support') !== false;
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

        // Require location for clock in/out.
        if (empty($this->gps_location)) {
            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Location required',
                'message' => 'Location is required for clock in/out. Please allow location access in your browser, then retake the photo and proceed.',
            ]);
            return;
        }

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
                'message' => 'Please provide your accomplishment report link before clocking out (required every Friday).',
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

        if (empty($this->gps_location)) {
            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Location required',
                'message' => 'Location is required. Please allow location access and retake the photo.',
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
        $capturedImage = $this->imageCaptured;

        // Temp file is in clock-capture-tmp (not livewire-tmp). Prefer session (set at upload) so Proceed works across workers/servers; else read file, then delete temp.
        if (is_string($capturedImage) && str_starts_with($capturedImage, 'capture:')) {
            $path = substr($capturedImage, 8);
            $sessionKey = 'clock_capture_' . $path;
            $base64 = session()->get($sessionKey);
            if ($base64 !== null && $base64 !== '') {
                session()->forget($sessionKey);
                $capturedImage = 'data:image/jpeg;base64,' . $base64;
            } else {
                $fullPath = Storage::disk('local')->path($path);
                if (file_exists($fullPath)) {
                    $contents = file_get_contents($fullPath);
                    $capturedImage = 'data:image/jpeg;base64,' . base64_encode($contents);
                    @unlink($fullPath);
                } else {
                    Log::warning('Clock:buildToProcess missing capture file', [
                        'path' => $path,
                        'employee_no' => $this->employee_no,
                        'storage_path' => Storage::disk('local')->path(''),
                    ]);
                    $capturedImage = null;
                }
            }
        }

        return [
            'timestamp' => Carbon::now(),
            'captured_image' => $capturedImage,
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

    public function imageCaptured($imageData, $isFaceDetected = false, $isForcedOut = false, $location = null, $locationError = null)
    {
        // Normalize to avoid corrupt payload (e.g. only accept scalar locationError).
        $locationError = is_string($locationError) ? $locationError : null;

        // Keep path as-is (capture:...) so Livewire response stays small; resolve to base64 only in buildToProcess() when saving.
        // Do NOT read file or convert to base64 here — that would bloat the component state and cause 413/HTML response again.

        Log::info('Clock:imageCaptured', [
            'employee_no'    => $this->employee_no,
            'raw_location'   => $location,
            'location_error' => $locationError,
        ]);

        $this->imageCaptured = $imageData;
        $this->isFaceDetected = $isFaceDetected;
        $this->isForcedOut = $isForcedOut;
        $this->captured_at = now()->toDateTimeString();
        // Convert coordinates to real address using Google Geocoding (env: GOOGLE_MAPS_API_KEY).
        $address = $this->resolveLocationFromCoordinates($location);
        $this->gps_location = $address !== null ? $address : ($location ?? '');
        $this->requires_accomplishment = $this->shouldRequireAccomplishment();

    }

    public function resetCapture(): void
    {
        $this->imageCaptured = null;
        $this->isFaceDetected = false;
        $this->isForcedOut = false;
        $this->captured_at = null;
        $this->upload_accomplishment = null;
        $this->accomplishment_link = null;
        $this->accomplishment = null;
        $this->requires_accomplishment = false;
        $this->resetErrorBag();
    }

    public function saveAccomplishment()
    {
        $this->resetErrorBag();

        // Safety check
        if (!$this->accomplishment_link) {
            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Missing Link',
                'message' => 'Please enter an accomplishment report link.',
            ]);
            return;
        }

        // Validate the link format
        $this->validateOnly('accomplishment_link');

        // Save the link to the property that gets stored with the timelog
        $this->accomplishment = $this->accomplishment_link;

        // Trigger clock or next step (this will show Recorded!/confirm, etc.)
        $this->triggerClock();
    }

    public function render()
    {
        return view('livewire.employee.clock');
    }

    /**
     * Convert \"lat,lng\" coordinates into a formatted address using
     * Google Maps Geocoding API. Returns null on any failure.
     */
    private function resolveLocationFromCoordinates(?string $coords): ?string
    {
        if (!$coords) {
            return null;
        }

        $parts = array_map('trim', explode(',', $coords));
        if (count($parts) < 2) {
            return null;
        }

        $apiKey = config('services.google.maps_key');
        if (empty($apiKey)) {
            return null;
        }

        try {
            Log::info('Clock:geocode request', [
                'employee_no' => $this->employee_no,
                'coords'     => $coords,
            ]);

            $response = Http::timeout(6)->withOptions(['cookies' => false])
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'latlng' => implode(',', $parts),
                    'key'    => $apiKey,
                ]);

            if (!$response->successful()) {
                Log::warning('Clock:geocode http_error', [
                    'employee_no'  => $this->employee_no,
                    'status_code'  => $response->status(),
                    'body'         => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();

            Log::info('Clock:geocode response', [
                'employee_no'   => $this->employee_no,
                'status'       => $data['status'] ?? null,
                'has_results'  => !empty($data['results']),
                'address'      => $data['results'][0]['formatted_address'] ?? null,
            ]);

            if (($data['status'] ?? '') !== 'OK' || empty($data['results'][0]['formatted_address'])) {
                return null;
            }

            return $data['results'][0]['formatted_address'];
        } catch (\Throwable $e) {
            Log::error('Clock:geocode exception', [
                'employee_no' => $this->employee_no,
                'message'     => $e->getMessage(),
            ]);
            return null;
        }
    }
}