<?php

namespace App\Http\Controllers\Admin\Settings\Payroll;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PayrollSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if ($user && method_exists($user, 'hasRole') && $user->hasRole('superadmin')) {
                return $next($request);
            }
            if ($user && method_exists($user, 'can') && $user->can('read holidays')) {
                return $next($request);
            }
            abort(403, 'Unauthorized.');
        })->only('index');
    }

    public function index()
    {
        return view('admin.settings.payroll.settings');
    }
}
