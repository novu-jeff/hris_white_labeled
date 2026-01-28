<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function post(Request $request) {
        // Disabled placeholder endpoint (was causing parse errors).
        // If monitoring is required, implement using a real model/table.
        return response()->json(['status' => false, 'message' => 'Monitoring endpoint not implemented.'], 501);
    }
}
