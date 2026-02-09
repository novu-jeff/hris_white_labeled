<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClockInOutController extends Controller
{

    public function __construct() {
        $this->middleware('permission:read clock-in-out')->only('index');
        $this->middleware('permission:read clock-in-out')->only('uploadCapture');
    }

    public function index() {
        return view('employee.clock', [
            'action' => 'index',
            'title' => 'ESS | Time Keeping',
            'header' => 'Clock In or Out',
            'sub' => 'Manage your clock in and clock out for work attendance.'
        ]);
    }

    /**
     * Upload captured clock-in/out image via multipart so the Livewire update payload stays small.
     * Returns a path token that the Livewire component will resolve to the file contents.
     */
    public function uploadCapture(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120', // 5MB
        ]);

        $file = $request->file('image');
        $name = Str::random(40) . '.' . $file->getClientOriginalExtension();
        // Temporary only: stored in clock-capture-tmp (not livewire-tmp). Deleted after Proceed; final image is saved to storage/timelogs/ only.
        $path = $file->storeAs('clock-capture-tmp', $name, 'local');

        // Store base64 in session so Proceed can find the image if the temp file is missing (e.g. different server/worker).
        $fullPath = Storage::disk('local')->path($path);
        if (file_exists($fullPath)) {
            $request->session()->put('clock_capture_' . $path, base64_encode(file_get_contents($fullPath)));
        }

        return response()->json([
            'path' => 'capture:' . $path,
        ]);
    }
}
