<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TimelogS3SmokeTest extends Command
{
    protected $signature = 'timelog:s3-smoke
        {--path= : Optional custom S3 key (default: timelogs/smoke-tests/...)}
        {--keep : Keep test file in S3 (default deletes after verification)}';

    protected $description = 'Upload and verify a temporary file in S3 timelogs path';

    public function handle(): int
    {
        // Force exception mode so we can see the real provider error.
        config(['filesystems.disks.s3.throw' => true]);
        Storage::forgetDisk('s3');
        $disk = Storage::disk('s3');
        $key = $this->option('path');

        if (empty($key)) {
            $key = 'timelogs/smoke-tests/s3-smoke-' . now()->format('Ymd-His') . '.txt';
        }

        $payload = implode("\n", [
            'Timelog S3 smoke test',
            'generated_at=' . now()->toIso8601String(),
            'app_env=' . app()->environment(),
            'app_url=' . config('app.url'),
            '',
        ]);

        $this->line('S3 smoke test starting...');
        $this->line('Key: ' . $key);
        $this->line('Bucket: ' . (config('filesystems.disks.s3.bucket') ?: '(empty)'));
        $this->line('Region: ' . (config('filesystems.disks.s3.region') ?: '(empty)'));
        $this->line('Endpoint: ' . (config('filesystems.disks.s3.endpoint') ?: '(default AWS endpoint)'));

        try {
            $stored = $disk->put($key, $payload, ['visibility' => 'public']);
            if (!$stored) {
                $this->warn('Upload with visibility=public failed, retrying without visibility option...');
                $stored = $disk->put($key, $payload);
            }
            if (!$stored) {
                $this->error('S3 put() returned false. Upload failed.');
                return self::FAILURE;
            }

            $exists = $disk->exists($key);
            if (!$exists) {
                $this->error('Upload returned true but exists() is false.');
                return self::FAILURE;
            }

            $size = $disk->size($key);
            $this->info('Upload OK. Object exists in S3.');
            $this->line('Size: ' . $size . ' bytes');

            try {
                $this->line('URL: ' . $disk->url($key));
            } catch (\Throwable $e) {
                $this->warn('Could not build URL: ' . $e->getMessage());
            }

            if (!$this->option('keep')) {
                $deleted = $disk->delete($key);
                $this->line($deleted ? 'Cleanup: deleted test object.' : 'Cleanup: failed to delete test object.');
            } else {
                $this->line('Kept test object in S3 (because --keep was passed).');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('S3 smoke test failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
