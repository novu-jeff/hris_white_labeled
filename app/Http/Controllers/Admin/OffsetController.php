<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OffsetController extends Controller
{

    public function __construct() {
        $this->middleware('permission:read offset')->only('index');
    }

    public function index(Request $request)
    {
        $status = $request->status ?? 'pending';

        return view('admin.ess.offset.index', compact('status'));
    }
}
