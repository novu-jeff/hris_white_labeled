<?php

namespace App\Services;

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
        if ($shift->shift_duration === 'flexible') {
            $earliest = Carbon::parse($shift->earliest_in);
            $latest = Carbon::parse($shift->latest_in);

            return match (true) {
                $timeMark->lt($earliest) => $this->info("The earliest clock in is " . $earliest->format('g:i A')),
                $timeMark->gt($latest) => $this->confirm("You're clocking in and will be marked as late."),
                default => ['status' => true]
            };
        }

        $startShift = Carbon::parse($shift->start_shift);
        return $timeMark->gt($startShift)
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
        $minHours = (int) env('MIN_CLOCKOUT_HOURS', 9);

        $firstLog = $this->getFirstLog($timestamp, $employee_no);
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

    private function getFirstLog(string $timestamp, string $employee_no): ?array
    {
        $date = Carbon::parse($timestamp)->toDateString();
        $employeeId = $this->getEmployeeID($employee_no);

        return EmployeeTimelogs::where('employee_id', $employeeId)
            ->whereDate('timestamp', $date)
            ->orderBy('timestamp')
            ->first()?->toArray();
    }

    public function insertLog(int $entry, array $toProcess, string $employee_no): array
    {
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
        $employeeId = $this->getEmployeeID($employee_no);

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
     * Store the captured image on disk, with a visual overlay that includes:
     * - Clock label (Time In / Time Out)
     * - Exact time & date
     * - Optional location text and mini-map (when coordinates are available)
     */
    private function insertImage(
        string $employee_no,
        string $imageData,
        string $timestamp,
        mixed $rawLocation,
        int $entry
    ): ?string
    {
        if (!str_contains($imageData, 'base64,')) {
            \Log::error('Invalid image data format.', compact('employee_no'));
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

        $filename = strtolower($employee_no . '_' . time() . '.png');

        $disk = env('USE_S3_STORAGE', false) ? 's3' : 'public';
        Storage::disk($disk)->put("timelogs/{$filename}", $finalImage ?? $decodedImage, ['visibility' => 'public']);

        return $filename;
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

        // Draw bottom overlay bar.
        $barHeight = (int) max(90, $height * 0.27);
        $barY = $height - $barHeight;
        imagefilledrectangle($canvas, 0, $barY, $width, $height, $overlayBg);

        // Text positioning (using built-in fonts for portability).
        $xPadding = 16;
        $y = $barY + 10;

        // Label + time (bigger).
        imagestring($canvas, 5, $xPadding, $y, $label . '  ' . $time, $accent);
        $y += 20;
        imagestring($canvas, 4, $xPadding, $y, $date, $white);
        $y += 18;

        if ($employeeName) {
            imagestring($canvas, 4, $xPadding, $y, $employeeName, $white);
            $y += 18;
        }

        if ($positionName) {
            imagestring($canvas, 3, $xPadding, $y, $positionName, $white);
            $y += 18;
        }

        if ($locationLabel !== null) {
            imagestring($canvas, 2, $xPadding, $y, $locationLabel, $white);
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
            $response = Http::timeout(3)->get($urlPath, [
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
