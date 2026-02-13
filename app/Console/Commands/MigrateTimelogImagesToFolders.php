<?php

namespace App\Console\Commands;

use App\Models\EmployeeTimelogs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateTimelogImagesToFolders extends Command
{
    protected $signature = 'timelog:migrate-to-folders
        {--dry-run : Only report what would be done, do not move files or update DB}
        {--chunk=100 : Number of records to process per chunk}
        {--skip-s3 : Do not migrate/copy files on S3}
        {--skip-local : Do not migrate local/public disk (use if CLI cannot write storage)}
        {--local-only : Only move local files for records already in folder format (run as web user after --skip-local)}';

    protected $description = 'Move existing flat timelog images into per-employee folders (public + S3) and update DB';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $skipS3 = (bool) $this->option('skip-s3');
        $skipLocal = (bool) $this->option('skip-local');
        $localOnly = (bool) $this->option('local-only');

        if ($dryRun) {
            $this->warn('DRY RUN: no files or DB will be changed.');
        }

        $public = Storage::disk('public');
        $s3Configured = !$skipS3 && config('filesystems.disks.s3.key') && config('filesystems.disks.s3.bucket');
        $s3 = $s3Configured ? Storage::disk('s3') : null;

        $oldPathPrefix = 'timelogs/';
        $migrated = 0;
        $skipped = 0;
        $errors = 0;

        if ($localOnly) {
            return $this->runLocalOnlyMigration($public, $oldPathPrefix, $dryRun, $chunkSize);
        }

        $query = EmployeeTimelogs::query()
            ->whereNotNull('captured_image')
            ->where('captured_image', '!=', '')
            ->whereRaw("captured_image NOT LIKE '%/%'");

        $total = $query->count();
        if ($total === 0) {
            $this->info('No flat-format timelog images to migrate.');
            return self::SUCCESS;
        }

        $this->info("Found {$total} timelog record(s) with flat captured_image. Migrating to per-employee folders...");

        $query->with('employee:id,employee_no,bsd_no')
            ->chunkById($chunkSize, function ($logs) use (
                $public,
                $s3,
                $s3Configured,
                $oldPathPrefix,
                $dryRun,
                $skipLocal,
                &$migrated,
                &$skipped,
                &$errors
            ) {
                foreach ($logs as $log) {
                    $oldImage = $log->captured_image;
                    $oldPath = $oldPathPrefix . $oldImage;

                    $employeeNo = $log->employee->employee_no ?? $log->employee_id;
                    $employeeFolder = strtolower(trim((string) $employeeNo));
                    $filename = basename($oldImage);
                    $newRelative = "{$employeeFolder}/{$filename}";
                    $newPath = $oldPathPrefix . $newRelative;

                    if ($newRelative === $oldImage) {
                        $skipped++;
                        continue;
                    }

                    $content = null;
                    $existsPublic = $public->exists($oldPath);
                    if ($existsPublic) {
                        $content = $public->get($oldPath);
                    }

                    $existsS3 = false;
                    if ($s3Configured && $s3) {
                        $existsS3 = $s3->exists($oldPath);
                        if ($existsS3 && $content === null) {
                            $content = $s3->get($oldPath);
                        }
                    }

                    if (!$existsPublic && !$existsS3) {
                        $this->warn("Missing file(s) for captured_image: {$oldImage} (log id: {$log->id}). Skipping.");
                        $skipped++;
                        continue;
                    }

                    if ($content === null) {
                        $content = $existsPublic ? $public->get($oldPath) : $s3->get($oldPath);
                    }

                    if ($dryRun) {
                        $migrated++;
                        continue;
                    }

                    try {
                        if (!$skipLocal) {
                            $public->put($newPath, $content, ['visibility' => 'public']);
                            if ($existsPublic) {
                                $public->delete($oldPath);
                            }
                        }

                        if ($s3Configured && $s3) {
                            $s3->put($newPath, $content);
                            if ($existsS3) {
                                $s3->delete($oldPath);
                            }
                        }

                        $log->captured_image = $newRelative;
                        $log->save();
                        $migrated++;
                    } catch (\Throwable $e) {
                        $errors++;
                        $this->error("Failed to migrate {$oldImage}: " . $e->getMessage());
                    }
                }
            });

        $this->newLine();
        $this->info("Done. Migrated: {$migrated}, Skipped: {$skipped}, Errors: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function runLocalOnlyMigration($public, string $oldPathPrefix, bool $dryRun, int $chunkSize): int
    {
        $query = EmployeeTimelogs::query()
            ->whereNotNull('captured_image')
            ->where('captured_image', '!=', '')
            ->whereRaw("captured_image LIKE '%/%'");

        $total = $query->count();
        if ($total === 0) {
            $this->info('No records in folder format to migrate locally.');
            return self::SUCCESS;
        }

        $this->info("Found {$total} record(s) in folder format. Moving local files only...");
        $migrated = 0;
        $skipped = 0;
        $errors = 0;

        $query->chunkById($chunkSize, function ($logs) use ($public, $oldPathPrefix, $dryRun, &$migrated, &$skipped, &$errors) {
            foreach ($logs as $log) {
                $newRelative = $log->captured_image;
                $filename = basename($newRelative);
                $oldPath = $oldPathPrefix . $filename;
                $newPath = $oldPathPrefix . $newRelative;

                if (!$public->exists($oldPath)) {
                    if ($public->exists($newPath)) {
                        $migrated++;
                    }
                    continue;
                }

                if ($dryRun) {
                    $this->line("Would move local: {$oldPath} -> {$newPath}");
                    $migrated++;
                    continue;
                }

                try {
                    $content = $public->get($oldPath);
                    $public->put($newPath, $content, ['visibility' => 'public']);
                    $public->delete($oldPath);
                    $migrated++;
                } catch (\Throwable $e) {
                    $errors++;
                    $this->error("Failed to move {$oldPath}: " . $e->getMessage());
                }
            }
        });

        $this->newLine();
        $this->info("Done. Migrated: {$migrated}, Skipped: {$skipped}, Errors: {$errors}");
        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
