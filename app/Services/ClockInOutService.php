<?php

namespace App\Services;

use App\Jobs\MirrorTimelogImageToS3;
use App\Models\EmployeeTimelogs;
use App\Models\EmployeeInformation;
use App\Services\DailyTimeRecordService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ClockInOutService
{
    public function process(string $entry, array $toProcess, string $employee_no): array
    {
        $validation = $this->checkLog((int) $entry, $toProcess, $employee_no);

        if (($validation['status'] ?? false) !== true) {
            return $validation;
        }

        return $this->insertLog((int) $entry, $toProcess, $employee_no);
    }

    public function checkLog(int $entry, array $toProcess, string $employee_no): array
    {
        $timeMark = Carbon::parse($toProcess['timestamp']);
        $shift = app(DailyTimeRecordService::class)->getShiftSchedule($employee_no);
        $lunchTracking = filter_var(config('app.lunch_tracking', true), FILTER_VALIDATE_BOOLEAN);
        $hasBreakTime = $lunchTracking && ($shift->is_breaktime_required ?? false);

        return match (true) {
            $hasBreakTime => match ($entry) {
                0 => $this->validateClockIn($timeMark, $shift),
                1, 2 => $this->validateLunch($entry, $timeMark, $shift),
                3 => $this->validateClockOut($timeMark, $shift, $toProcess['timestamp'], $employee_no),
                4 => $this->validateAlreadyDone(),
                default => ['status' => true]
            },
            default => match ($entry) {
                0 => $this->validateClockIn($timeMark, $shift),
                1 => $this->validateClockOut($timeMark, $shift, $toProcess['timestamp'], $employee_no),
                2 => $this->validateAlreadyDone(),
                default => ['status' => true]
            }
        };
    }

    private function validateClockIn(Carbon $timeMark, $shift): array
    {
        if (!empty($shift->allow_anytime_clockin)) {
            return ['status' => true];
        }

        // Allow clock-in from 7:00 AM (configurable via EARLIEST_CLOCK_IN_ALLOWED). Clock-out logic unchanged.
        $earliestAllowedTime = config('app.earliest_clock_in_allowed', '07:00');

        if ($shift->shift_duration === 'flexible') {
            $earliest = Carbon::parse($shift->earliest_in);
            $latest = Carbon::parse($shift->latest_in);
            // Use the earlier of (shift earliest, 7am) so 7am clock-in is always allowed
            $effectiveTime = ($earliest->format('H:i') > $earliestAllowedTime) ? $earliestAllowedTime : $earliest->format('H:i');
            $effectiveEarliest = $timeMark->copy()->startOfDay()->setTimeFromTimeString($effectiveTime);

            return match (true) {
                $timeMark->lt($effectiveEarliest) => $this->info("The earliest clock in is " . $effectiveEarliest->format('g:i A')),
                $timeMark->gt($latest) => $this->confirm("You're clocking in and will be marked as late."),
                default => ['status' => true]
            };
        }

        $startShift = Carbon::parse($shift->start_shift);
        $effectiveStart = $timeMark->copy()->startOfDay()->setTimeFromTimeString($startShift->format('H:i'));
        $effectiveEarliestDt = $timeMark->copy()->startOfDay()->setTimeFromTimeString($earliestAllowedTime);
        if ($timeMark->lt($effectiveEarliestDt)) {
            return $this->info("The earliest clock in is " . Carbon::parse($earliestAllowedTime)->format('g:i A'));
        }
        return $timeMark->gt($effectiveStart)
            ? $this->confirm("You're clocking in and will be marked as late.")
            : ['status' => true];
    }

    private function validateLunch(int $entry, Carbon $timeMark, $shift): array
    {
        $breakOut = Carbon::parse($shift->break_out);
        $breakIn = Carbon::parse($shift->break_in);

        return match (true) {
            $entry === 1 && $timeMark->lt($breakOut) => $this->confirm("You're lunching out too early and will be marked as undertime."),
            $entry === 2 && $timeMark->gt($breakIn) => $this->confirm("You're lunching in too late and will be marked as undertime."),
            default => ['status' => true]
        };
    }

    private function validateClockOut(Carbon $timeMark, $shift, string $timestamp, string $employee_no): array
    {
        if (!empty($shift->allow_anytime_clockout)) {
            return ['status' => true];
        }

        $minHours = (int) env('MIN_CLOCKOUT_HOURS', 9);

        $firstLog = $this->getFirstLog($timestamp, $employee_no, $shift);
        $minExpectedOut = !empty($firstLog['timestamp'])
            ? Carbon::parse($firstLog['timestamp'])->addHours($minHours)
            : null;

        // Existing schedule-based expectation (for flexible schedules, system uses clock-in + 9 hours).
        $expectedOut = $shift->shift_duration === 'flexible'
            ? (!empty($firstLog['timestamp']) ? Carbon::parse($firstLog['timestamp'])->addHours(9) : null)
            : Carbon::parse($shift->end_shift);

        // Enforce minimum duration since clock-in (default 9 hours).
        $requiredOut = match (true) {
            $minExpectedOut && $expectedOut => $minExpectedOut->greaterThan($expectedOut) ? $minExpectedOut : $expectedOut,
            (bool) $minExpectedOut => $minExpectedOut,
            default => $expectedOut,
        };

        if (!$requiredOut) {
            return ['status' => true];
        }

        return $timeMark->lt($requiredOut)
            ? $this->confirm("You're clocking out before completing {$minHours} hours since clock in (expected after {$requiredOut->format('g:i A')}). This may result in undertime or absence.")
            : ['status' => true];
    }

    private function validateAlreadyDone(): array
    {
        return $this->info("Today's job is already done");
    }

    private function getFirstLog(string $timestamp, string $employee_no, $shift = null): ?array
    {
        $timeMark = Carbon::parse($timestamp);
        $date = $timeMark->toDateString();
        $employeeId = $this->getEmployeeID($employee_no);

        $firstLog = EmployeeTimelogs::where('employee_id', $employeeId)
            ->whereDate('timestamp', $date)
            ->orderBy('timestamp')
            ->first()?->toArray();

        if ($firstLog) {
            return $firstLog;
        }

        if ($shift && $this->shiftAllowsPastMidnightClockOut($shift)) {
            $hour = (int) $timeMark->format('H');
            if ($hour >= 0 && $hour < 6) {
                $yesterday = $timeMark->copy()->subDay()->toDateString();
                return EmployeeTimelogs::where('employee_id', $employeeId)
                    ->whereDate('timestamp', $yesterday)
                    ->orderBy('timestamp')
                    ->first()?->toArray();
            }
        }

        return null;
    }

    /**
     * Support shift and other overnight shifts: clock in 6pm, clock out 2am (next calendar day)
     * is still the same shift — no 12am shenanigans. Only these shifts get past-midnight exception.
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

    public function insertLog(int $entry, array $toProcess, string $employee_no): array
    {
        $employeeId = $this->getEmployeeID($employee_no);
        $shift = app(DailyTimeRecordService::class)->getShiftSchedule($employee_no);
        $lunchTracking = filter_var(config('app.lunch_tracking', true), FILTER_VALIDATE_BOOLEAN);
        $hasBreakTime = $lunchTracking && ($shift->is_breaktime_required ?? false);

        $date = Carbon::parse($toProcess['timestamp'])->toDateString();
        $todayRecords = EmployeeTimelogs::where('employee_id', $employeeId)
            ->whereDate('timestamp', $date)
            ->orderBy('timestamp')
            ->get();
        $currentProgress = $this->getEntryProgressFromRecords($todayRecords, $hasBreakTime);

        $currentCount = EmployeeTimelogs::where('employee_id', $employeeId)
            ->whereDate('timestamp', $date)
            ->count();

        $isPastMidnightClockOut = false;
        if (($entry === 1 || $entry === 3) && $currentCount === 0) {
            try {
                $isPastMidnightClockOut = $this->shiftAllowsPastMidnightClockOut($shift);
            } catch (\Throwable $e) {
                $isPastMidnightClockOut = false;
            }
        }

        $isAllowedForcedOutFromLunchOut = $hasBreakTime && $entry === 3 && $currentProgress === 1;

        if (
            !$isPastMidnightClockOut &&
            !$isAllowedForcedOutFromLunchOut &&
            $currentProgress !== $entry
        ) {
            return [
                'status' => false,
                'alert' => 'error',
                'title' => 'Duplicate or out-of-order clock',
                'message' => 'A clock record was already recorded. Please refresh the page and try again if needed.',
            ];
        }

        $timestamp = $toProcess['timestamp'];
        $rawLocation = $toProcess['captured_location'] ?? null;
        $captured_location = $this->formatCapturedLocation($rawLocation);

        $captured_image = $this->insertImage(
            $employee_no,
            $toProcess['captured_image'],
            $timestamp,
            $rawLocation,
            $entry
        );
        $accomplishment = $toProcess['accomplishment'] ?? null;

        $formattedTimestamp = Carbon::now()->format('Y-m-d') . ' ' . Carbon::parse($timestamp)->format('H:i');
        $statusMap = [0 => 0, 1 => 1, 2 => 0, 3 => 1];

        $data = [
            'employee_id' => $employeeId,
            'timestamp' => $formattedTimestamp,
            'isWeb' => true,
            'captured_location' => $captured_location,
            'captured_image' => $captured_image,
            'accomplishment' => $accomplishment
        ];

        if (config('app.external_timelogs')) {
            $data += [
                'sn' => 'RUU5242500021',
                'table' => 'ATTLOG',
                'stamp' => '9999',
                'status1' => $statusMap[$entry]
            ];
        } else {
            $data['status'] = $statusMap[$entry];
        }

        EmployeeTimelogs::create($data);

        return [
            'status' => true,
            'alert' => 'success',
            'title' => 'Recorded!',
            'message' => ''
        ];
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

    private function formatCapturedLocation(mixed $capturedLocation): ?string
    {
        if (empty($capturedLocation)) {
            return null;
        }

        // Backward-compatible: if older code already sent a string, store as-is.
        if (is_string($capturedLocation)) {
            return $capturedLocation;
        }

        // Expected shape from Livewire: ['place' => string|null, 'coordinates' => ['lat' => .., 'lng' => ..], ...]
        if (is_array($capturedLocation)) {
            $coords = $capturedLocation['coordinates'] ?? null;

            $lat = is_array($coords) ? ($coords['lat'] ?? null) : null;
            $lng = is_array($coords) ? ($coords['lng'] ?? null) : null;

            // If we only have coords, fall back to a simple string.
            if ($lat === null || $lng === null) {
                return isset($capturedLocation['place']) ? (string) $capturedLocation['place'] : null;
            }

            $payload = [
                'lat' => (float) $lat,
                'lng' => (float) $lng,
                'place' => $capturedLocation['place'] ?? null,
                'provider' => $capturedLocation['provider'] ?? null,
                'accuracy_m' => $capturedLocation['accuracy_m'] ?? null,
            ];

            // Remove null keys to keep the column short.
            $payload = array_filter($payload, fn($v) => $v !== null && $v !== '');

            return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return null;
    }

    /**
     * Store the captured image in the final timelogs location only (never in livewire-tmp or temporary paths).
     * Primary save follows USE_S3_STORAGE (s3/public). For testing, set TIMELOG_MIRROR_TO_S3=true
     * to keep the local/public copy and also write a second copy to S3.
     * Also builds a visual overlay: clock label, time & date, optional location + mini-map.
     */
    private function insertImage(
        string $employee_no,
        ?string $imageData,
        string $timestamp,
        mixed $rawLocation,
        int $entry
    ): ?string
    {
        if (empty($imageData) || !str_contains($imageData, 'base64,')) {
            if (!empty($imageData)) {
                \Log::error('Invalid image data format.', compact('employee_no'));
            }
            return null;
        }

        [$header, $base64Data] = explode('base64,', $imageData);
        $decodedImage = base64_decode(str_replace(' ', '+', $base64Data));

        if ($decodedImage === false) {
            \Log::error('Failed to decode base64 image.', compact('employee_no'));
            return null;
        }

        // Try to build an overlayed image; if anything fails, fall back to the raw capture.
        $finalImage = $this->buildOverlayedImage($decodedImage, $timestamp, $rawLocation, $entry, $employee_no);

        $employeeFolder = strtolower(trim($employee_no));
        $filename = strtolower($employee_no . '_' . time() . '.png');
        $relativePath = "{$employeeFolder}/{$filename}";
        $path = "timelogs/{$relativePath}";
        $payload = $finalImage ?? $decodedImage;

        $primaryDisk = env('USE_S3_STORAGE', false) ? 's3' : 'public';
        if (!$this->storeTimelogImage($primaryDisk, $path, $payload, $employee_no, 'saved to final location')) {
            return null;
        }

        // Keep a local/public copy when primary is S3 to preserve existing APP_URL/storage access.
        if ($primaryDisk !== 'public') {
            $this->storeTimelogImage('public', $path, $payload, $employee_no, 'mirrored to local storage');
        }

        $mirrorToS3 = filter_var(config('app.timelog_mirror_to_s3', false), FILTER_VALIDATE_BOOLEAN);
        if ($mirrorToS3 && $primaryDisk !== 's3') {
            // Queue mirror to avoid blocking clock-in/out response.
            MirrorTimelogImageToS3::dispatch($path, $employee_no);
        }

        return $relativePath;
    }

    private function storeTimelogImage(
        string $disk,
        string $path,
        string $payload,
        string $employeeNo,
        string $actionLabel
    ): bool {
        try {
            $stored = Storage::disk($disk)->put($path, $payload, ['visibility' => 'public']);

            // Some S3-compatible providers reject ACL/visibility headers.
            if (!$stored && $disk === 's3') {
                $stored = Storage::disk($disk)->put($path, $payload);
                if ($stored) {
                    \Log::warning('Timelog image stored to s3 without visibility option', [
                        'path' => $path,
                        'disk' => $disk,
                        'employee_no' => $employeeNo,
                    ]);
                }
            }

            if (!$stored) {
                \Log::warning('Failed to store timelog image', [
                    'path' => $path,
                    'disk' => $disk,
                    'employee_no' => $employeeNo,
                    'action' => $actionLabel,
                ]);
                return false;
            }

            \Log::info("Timelog image {$actionLabel}", [
                'path' => $path,
                'disk' => $disk,
                'employee_no' => $employeeNo,
            ]);

            return true;
        } catch (\Throwable $e) {
            \Log::warning('Failed to store timelog image', [
                'path' => $path,
                'disk' => $disk,
                'employee_no' => $employeeNo,
                'action' => $actionLabel,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create a PNG image with time and (optionally) location + map overlaid.
     *
     * This uses the GD extension. If GD, HTTP, or remote map fetching is not available,
     * it safely falls back to returning null so that the raw image can be stored instead.
     */
    private function buildOverlayedImage(
        string $binaryImage,
        string $timestamp,
        mixed $rawLocation,
        int $entry,
        string $employeeNo
    ): ?string {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }

        $base = @imagecreatefromstring($binaryImage);
        if ($base === false) {
            return null;
        }

        $width = imagesx($base);
        $height = imagesy($base);

        // Prepare canvas with alpha so overlays look clean.
        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        // Copy original image into canvas.
        imagecopy($canvas, $base, 0, 0, 0, 0, $width, $height);
        imagedestroy($base);

        // Colors.
        $overlayBg = imagecolorallocatealpha($canvas, 0, 0, 0, 70); // semi-transparent black
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $accent = imagecolorallocate($canvas, 46, 204, 113); // green-ish

        // Time & date formatting.
        $time = Carbon::parse($timestamp)->format('H:i');
        $date = Carbon::parse($timestamp)->format('D, M j, Y');
        $label = match ($entry) {
            0 => 'Time In',
            1, 3 => 'Time Out',
            default => 'Time',
        };

        // Lookup employee name & position.
        $employeeName = null;
        $positionName = null;
        try {
            $employee = EmployeeInformation::with(['personal', 'positions'])
                ->where('employee_no', $employeeNo)
                ->first();

            if ($employee) {
                if ($employee->personal) {
                    $employeeName = trim(
                        ($employee->personal->firstname ?? '') . ' ' . ($employee->personal->lastname ?? '')
                    ) ?: null;
                }
                if ($employee->positions) {
                    $positionName = $employee->positions->name ?? null;
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to load employee info for timelog overlay', [
                'employee_no' => $employeeNo,
                'error' => $e->getMessage(),
            ]);
        }

        // Simple location label based on rawLocation payload.
        $locationLabel = $this->buildLocationLabel($rawLocation);

        // Draw a compact bottom-left overlay bar (avoid covering too much of the frame).
        $barHeight = (int) max(110, $height * 0.18);      // ~18% of height, min 110px
        $barWidth  = (int) min($width * 0.55, 420);       // left-side panel only
        $barY      = $height - $barHeight;
        $barX1     = 0;
        $barX2     = $barWidth;
        imagefilledrectangle($canvas, $barX1, $barY, $barX2, $height, $overlayBg);

        // Text positioning (using built-in fonts for portability) inside the bar.
        $xPadding = $barX1 + 16;
        $y        = $barY + 10;

        // GD imagestring uses Latin-1; convert UTF-8 (e.g. ñ) so it displays correctly.
        $toLatin1 = fn (?string $s) => $s === null ? '' : (mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8') ?: $s);

        // Label + time (bigger).
        imagestring($canvas, 5, $xPadding, $y, $toLatin1($label . '  ' . $time), $accent);
        $y += 20;
        imagestring($canvas, 4, $xPadding, $y, $toLatin1($date), $white);
        $y += 18;

        if ($employeeName) {
            imagestring($canvas, 4, $xPadding, $y, $toLatin1($employeeName), $white);
            $y += 18;
        }

        if ($positionName) {
            imagestring($canvas, 3, $xPadding, $y, $toLatin1($positionName), $white);
            $y += 18;
        }

        if ($locationLabel !== null) {
            imagestring($canvas, 2, $xPadding, $y, $toLatin1($locationLabel), $white);
        }

        // Optional mini-map in the bottom-right if coordinates + API key are available.
        $mapBinary = $this->maybeFetchStaticMap($rawLocation);
        if ($mapBinary !== null) {
            $mapImage = @imagecreatefromstring($mapBinary);
            if ($mapImage !== false) {
                $mapSize = (int) min(220, $barHeight - 16);
                $mapX = $width - $mapSize - 10;
                $mapY = $barY + 8;

                $mapW = imagesx($mapImage);
                $mapH = imagesy($mapImage);

                // Resize & place map.
                imagecopyresampled(
                    $canvas,
                    $mapImage,
                    $mapX,
                    $mapY,
                    0,
                    0,
                    $mapSize,
                    $mapSize,
                    $mapW,
                    $mapH
                );
                imagedestroy($mapImage);
            }
        }

        ob_start();
        imagepng($canvas);
        imagedestroy($canvas);
        $buffer = ob_get_clean();

        return $buffer === false ? null : $buffer;
    }

    /**
     * Build a human-readable one-line location label from the raw Livewire payload.
     */
    private function buildLocationLabel(mixed $rawLocation): ?string
    {
        if (empty($rawLocation)) {
            return null;
        }

        if (is_string($rawLocation)) {
            return $rawLocation;
        }

        if (!is_array($rawLocation)) {
            return null;
        }

        $place = $rawLocation['place'] ?? null;
        $coords = $rawLocation['coordinates'] ?? null;
        $lat = is_array($coords) ? ($coords['lat'] ?? null) : null;
        $lng = is_array($coords) ? ($coords['lng'] ?? null) : null;

        $parts = [];
        if (!empty($place)) {
            $parts[] = $place;
        }
        if ($lat !== null && $lng !== null) {
            $parts[] = sprintf('%.5f, %.5f', (float) $lat, (float) $lng);
        }

        return empty($parts) ? null : implode(' • ', $parts);
    }

    /**
     * Optionally fetch a small static map image (PNG) for the given location.
     * Uses MAPBOX_API in env; returns null if unavailable.
     */
    private function maybeFetchStaticMap(mixed $rawLocation): ?string
    {
        if (!class_exists(Http::class)) {
            return null;
        }

        if (empty($rawLocation) || !is_array($rawLocation)) {
            return null;
        }

        $coords = $rawLocation['coordinates'] ?? null;
        $lat = is_array($coords) ? ($coords['lat'] ?? null) : null;
        $lng = is_array($coords) ? ($coords['lng'] ?? null) : null;

        if ($lat === null || $lng === null) {
            return null;
        }

        $token = env('MAPBOX_API');
        if (empty($token)) {
            return null;
        }

        // Mapbox Static Images API
        $style = 'mapbox/streets-v11';
        $pin = "pin-l+ff0000({$lng},{$lat})";
        $center = "{$lng},{$lat},17,0";
        $urlPath = "https://api.mapbox.com/styles/v1/{$style}/static/{$pin}/{$center}/300x300";

        try {
            $response = Http::timeout(3)->withOptions(['cookies' => false])->get($urlPath, [
                'access_token' => $token,
            ]);

            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to fetch static map for timelog image (Mapbox).', [
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function getEmployeeID(string $employee_no): ?string
    {
        $service = app(DailyTimeRecordService::class);
        return config('app.bsd_emp_identical') ? $employee_no : $service->getBsdNo($employee_no);
    }

    private function confirm(string $message): array
    {
        return [
            'status' => false,
            'alert' => 'confirm',
            'title' => 'Are you sure to continue?',
            'message' => $message
        ];
    }

    private function info(string $message): array
    {
        return [
            'status' => false,
            'alert' => 'info',
            'title' => 'Please be informed',
            'message' => $message
        ];
    }
}
