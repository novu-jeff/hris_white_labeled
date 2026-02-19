<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email', [
            'title' => 'Password Recovery'
        ]);
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Find by company email or personal email
        $employeeAccount = EmployeeAccount::where('email', $request->email)
            ->orWhere('company_email', $request->email)
            ->first();

        if (!$employeeAccount) {
            return back()->withErrors([
                'email' => 'The email provided does not exist.'
            ]);
        }

        if ($employeeAccount->isLocked) {
            return back()->withErrors([
                'email' => 'Unable to send reset link because your account is locked.',
            ]);
        }

        // Broker finds user by the 'email' column; we pass personal email so token is stored correctly
        $status = Password::broker('employees')->sendResetLink(
            ['email' => $employeeAccount->email]
        );

        // Check the status and respond accordingly
        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __('auth.' . $status))
            : back()->withErrors(['email' => __('auth.' . $status)]);
    }

}
