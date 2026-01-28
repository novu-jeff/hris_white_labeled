<?php

namespace App\Http\Controllers\Admin\Settings\Payroll;

use App\Http\Controllers\Controller;

class PayrollSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read holidays')->only('index');
    }

    public function index()
    {
        return view('admin.settings.payroll.settings');
    }
}
