<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MirrorTimelogImageToS3 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 20;

    public function __construct(
        public string $path,
        public string $employeeNo
    ) {
    }

    public function handle(): void
    {
        if (!Storage::disk('public')->exists($this->path)) {
            Log::warning('Timelog mirror source not found', [
                'path' => $this->path,
                'employee_no' => $this->employeeNo,
            ]);
            return;
        }

        $payload = Storage::disk('public')->get($this->path);

        $stored = Storage::disk('s3')->put($this->path, $payload, ['visibility' => 'public']);
        if (!$stored) {
            $stored = Storage::disk('s3')->put($this->path, $payload);
        }

        if ($stored) {
            Log::info('Timelog image mirrored to s3', [
                'path' => $this->path,
                'disk' => 's3',
                'employee_no' => $this->employeeNo,
            ]);
            return;
        }

        Log::warning('Failed to mirror timelog image to s3', [
            'path' => $this->path,
            'disk' => 's3',
            'employee_no' => $this->employeeNo,
        ]);
    }
}
