<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TimelogImageController extends Controller
{
    /**
     * Stream timelog captured image through the app (admin). Uses app HTTPS so S3 cert issues don't break images.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:read dtr');
    }

    public function show(Request $request, string $path): StreamedResponse
    {
        $path = $this->normalizePath($path);
        if ($path === null) {
            abort(404);
        }

        return $this->streamImage($path);
    }

    private function normalizePath(string $path): ?string
    {
        $path = trim($path, "/\t\n\r ");
        $path = str_replace(['../', '..\\'], '', $path);
        if ($path === '' || str_contains($path, '..')) {
            return null;
        }
        return $path;
    }

    private function streamImage(string $relativePath): StreamedResponse
    {
        $key = 'timelogs/' . $relativePath;
        $useS3 = config('filesystems.disks.s3.key') && config('filesystems.disks.s3.bucket') && env('USE_S3_STORAGE', false);

        // Try S3 first when enabled; fall back to public if S3 is down or file missing.
        if ($useS3) {
            try {
                if (Storage::disk('s3')->exists($key)) {
                    $stream = Storage::disk('s3')->readStream($key);
                    if ($stream !== false) {
                        $mime = $this->mimeFromPath($relativePath);
                        return response()->stream(function () use ($stream) {
                            fpassthru($stream);
                            fclose($stream);
                        }, 200, [
                            'Content-Type' => $mime,
                            'Cache-Control' => 'private, max-age=3600',
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                // S3 down or error; fall through to public.
            }
        }

        if (!Storage::disk('public')->exists($key)) {
            abort(404);
        }

        $mime = $this->mimeFromPath($relativePath);
        $stream = Storage::disk('public')->readStream($key);
        if ($stream === false) {
            abort(404);
        }

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function mimeFromPath(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }
}
