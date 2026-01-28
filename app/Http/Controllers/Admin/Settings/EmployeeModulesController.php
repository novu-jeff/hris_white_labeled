<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeModulesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $isSuperadmin = method_exists($user, 'hasRole') && $user->hasRole('superadmin');
        if (!$isSuperadmin) {
            abort(403, 'Only Superadmins can manage employee modules.');
        }
        return view('admin.settings.employee-modules');
    }
}
