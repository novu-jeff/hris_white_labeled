<?php

namespace App\Http\Controllers\Admin\Settings\HRIS;

use App\Http\Controllers\Controller;

class OffsetCreditsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read offset-credits')->only('index');
    }

    public function index()
    {
        return view('admin.settings.hris.offset-credits.index');
    }
}
