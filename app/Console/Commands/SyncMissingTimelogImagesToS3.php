<?php

namespace App\Console\Commands;

use App\Models\EmployeeTimelogs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncMissingTimelogImagesToS3 extends Command
{
    protected $signature = 'timelog:sync-missing-to-s3
        {--dry-run : Only report what would be uploaded}
        {--chunk=500 : Number of timelog rows to process per chunk}';

    protected $description = 'Upload timelog images from local/public to S3 only when missing in S3';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));

        $s3Key = config('filesystems.disks.s3.key');
        $s3Bucket = config('filesystems.disks.s3.bucket');
        if (empty($s3Key) || empty($s3Bucket)) {
            $this->warn('S3 is not configured (missing key or bucket). Nothing to sync.');
            return self::SUCCESS;
        }

        $public = Storage::disk('public');
        $s3 = Storage::disk('s3');

        $rows = 0;
        $uploaded = 0;
        $alreadyInS3 = 0;
        $missingLocal = 0;
        $errors = 0;

        EmployeeTimelogs::query()
            ->whereNotNull('captured_image')
            ->where('captured_image', '!=', '')
            ->orderBy('id')
            ->chunkById($chunk, function ($logs) use (
                $public,
                $s3,
                $dryRun,
                &$rows,
                &$uploaded,
                &$alreadyInS3,
                &$missingLocal,
                &$errors
            ) {
                foreach ($logs as $log) {
                    $rows++;
                    $relative = ltrim((string) $log->captured_image, '/');
                    $path = 'timelogs/' . $relative;

                    try {
                        if ($s3->exists($path)) {
                            $alreadyInS3++;
                            continue;
                        }

                        if (!$public->exists($path)) {
                            $missingLocal++;
                            continue;
                        }

                        if ($dryRun) {
                            $uploaded++;
                            continue;
                        }

                        $payload = $public->get($path);
                        $stored = $s3->put($path, $payload, ['visibility' => 'public']);
                        if (!$stored) {
                            $stored = $s3->put($path, $payload);
                        }

                        if ($stored) {
                            $uploaded++;
                        } else {
                            $errors++;
                            $this->warn("Failed to upload: {$path}");
                        }
                    } catch (\Throwable $e) {
                        $errors++;
                        $this->warn("Error syncing {$path}: {$e->getMessage()}");
                    }
                }
            });

        $mode = $dryRun ? 'DRY RUN' : 'SYNC';
        $this->newLine();
        $this->info("[{$mode}] Timelog S3 missing sync complete.");
        $this->line("Processed rows: {$rows}");
        $this->line('Missing in S3 (to upload): ' . $uploaded);
        $this->line("Already in S3: {$alreadyInS3}");
        $this->line("Missing local file: {$missingLocal}");
        $this->line("Errors: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
