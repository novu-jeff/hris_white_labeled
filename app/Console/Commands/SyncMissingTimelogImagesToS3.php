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
        $s3Throw = config('filesystems.disks.s3_throw') ? Storage::disk('s3_throw') : $s3;

        $rows = 0;
        $uploaded = 0;
        $alreadyInS3 = 0;
        $missingLocal = 0;
        $errors = 0;
        $firstErrorMessage = null;

        EmployeeTimelogs::query()
            ->whereNotNull('captured_image')
            ->where('captured_image', '!=', '')
            ->orderBy('id')
            ->chunkById($chunk, function ($logs) use (
                $public,
                $s3,
                $s3Throw,
                $dryRun,
                &$rows,
                &$uploaded,
                &$alreadyInS3,
                &$missingLocal,
                &$errors,
                &$firstErrorMessage
            ) {
                foreach ($logs as $log) {
                    $rows++;
                    $relative = ltrim((string) $log->captured_image, '/');
                    $path = 'timelogs/' . $relative;

                    try {
                        $inS3 = false;
                        try {
                            $inS3 = $s3->exists($path);
                        } catch (\Throwable $e) {
                            // S3 unreachable or timeout; assume not in S3 and try to upload.
                        }
                        if ($inS3) {
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
                        $stored = false;
                        try {
                            $stored = $s3Throw->put($path, $payload);
                            if (!$stored) {
                                $stored = $s3Throw->put($path, $payload, ['visibility' => 'public']);
                            }
                        } catch (\Throwable $e) {
                            if ($firstErrorMessage === null) {
                                $firstErrorMessage = $e->getMessage();
                                $this->newLine();
                                $this->error('First S3 error (so you can fix config/network): ' . $firstErrorMessage);
                                $this->newLine();
                                \Log::warning('Timelog sync S3 upload failed', [
                                    'path' => $path,
                                    'error' => $firstErrorMessage,
                                ]);
                            }
                            throw $e;
                        }

                        if ($stored) {
                            $uploaded++;
                            if ($uploaded % 50 === 0) {
                                $this->line("  Uploaded {$uploaded} so far...");
                            }
                        } else {
                            $errors++;
                            $msg = 'put() returned false (no exception). Check credentials, bucket, and endpoint.';
                            if ($firstErrorMessage === null) {
                                $firstErrorMessage = $msg;
                                $this->newLine();
                                $this->error('First S3 failure: ' . $msg);
                                $this->newLine();
                            }
                            $this->warn("Failed to upload: {$path}");
                        }
                    } catch (\Throwable $e) {
                        $errors++;
                        if ($firstErrorMessage === null) {
                            $firstErrorMessage = $e->getMessage();
                            $this->newLine();
                            $this->error('First S3 error: ' . $firstErrorMessage);
                            $this->newLine();
                        }
                        $this->warn("Error syncing {$path}: {$e->getMessage()}");
                    }
                }
            });

        $mode = $dryRun ? 'DRY RUN' : 'SYNC';
        $this->newLine();
        $this->info("[{$mode}] Timelog S3 missing sync complete.");
        $this->line("Processed rows: {$rows}");
        $this->line($dryRun ? "Would upload: {$uploaded}" : "Uploaded: {$uploaded}");
        $this->line("Already in S3: {$alreadyInS3}");
        $this->line("Missing local file: {$missingLocal}");
        $this->line("Errors: {$errors}");
        if ($errors > 0 && $firstErrorMessage !== null) {
            $this->newLine();
            $this->comment('Tip: Fix the error above (credentials, bucket, endpoint, network) then run again.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
