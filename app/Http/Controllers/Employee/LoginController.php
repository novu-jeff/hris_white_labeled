<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public function index() {
        return view('auth.employee.login');
    }

    public function store(Request $request)
    {
        $rules = [
            'email' => 'required',
            'password' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator);
        }

        // Allow login by email address, email_id (E-ID), or employee_no (E-No.)
        $input = $request->email;
        $isEmailFormat = filter_var($input, FILTER_VALIDATE_EMAIL);

        if ($isEmailFormat) {
            $employeeAccount = EmployeeAccount::where('email', $input)->first();
            if ($employeeAccount) {
                $identifier = 'email';
            } else {
                $employeeAccount = EmployeeAccount::where('email_id', $input)->first();
                $identifier = 'email_id';
            }
        } else {
            $identifier = 'employee_no';
            $employeeAccount = EmployeeAccount::where('employee_no', $input)->first();
        }

        // ---------------------------------------------------
        // 🔥 AUTO-UNLOCK AFTER 30 MINUTES
        // ---------------------------------------------------
        if ($employeeAccount && $employeeAccount->isLocked) {

            // Check if 30 minutes passed
            if ($employeeAccount->locked_at && now()->diffInMinutes($employeeAccount->locked_at) >= 30) {
                $employeeAccount->isLocked = false;
                $employeeAccount->login_attempts = 0;
                $employeeAccount->locked_at = null;
                $employeeAccount->save();
            } else {
                $minutesRemaining = 30 - now()->diffInMinutes($employeeAccount->locked_at);

                return redirect()->back()
                    ->with(['error' => "Your account is locked. Try again in {$minutesRemaining} minutes."])
                    ->withInput();
            }
        }
        // ---------------------------------------------------

        // Try login
        if ($employeeAccount && Auth::guard('employee')->attempt([
            $identifier => $input,
            'password' => $request->password
        ])) {
            // Reset attempts
            if ($employeeAccount) {
                $employeeAccount->login_attempts = 0;
                $employeeAccount->isLocked = false;
                $employeeAccount->locked_at = null;
                $employeeAccount->save();
            }

            $employee = Auth::guard('employee')->user();

            if ($employee->information->status !== 'active') {
                Auth::guard('employee')->logout();
                return redirect()->back()->with(['error' => 'Oops, your account is currently inactive.'])->withInput();
            }

            return redirect()->route('employee.dashboard');
        }

        // ---------------------------------------------------
        // 🔥 FAILED LOGIN LOGIC
        // ---------------------------------------------------
        if ($employeeAccount) {
            $employeeAccount->increment('login_attempts');

            $remaining = max(0, 5 - $employeeAccount->login_attempts);

            // Lock account
            if ($employeeAccount->login_attempts >= 5) {
                $employeeAccount->isLocked = true;
                $employeeAccount->locked_at = now(); // 🔥 record locked time
                $employeeAccount->save();

                return redirect()->back()
                    ->with(['error' => 'Too many failed attempts. Your account is locked for 30 minutes.'])
                    ->withInput();
            }

            $employeeAccount->save();

            $errorMessage = 'Invalid login or password.';
            if ($remaining > 0) {
                $errorMessage .= "<br>You have {$remaining} attempt(s) remaining.";
            }

            return redirect()->back()->with(['error' => $errorMessage])->withInput();
        }
        // ---------------------------------------------------

        return redirect()->back()->with(['error' => 'Invalid login or password'])->withInput();
    }


    public function store_bk(Request $request)
    {
        $rules = [
            'email' => 'required',
            'password' => 'required',
        ];
    
        $validator = Validator::make($request->all(), $rules);
    
        if ($validator->fails()) {
            return redirect()->back()
                ->withInput()
                ->withErrors($validator);
        }
    
        // Allow login by email address, email_id (E-ID), or employee_no (E-No.)
        $input = $request->email;
        $isEmailFormat = filter_var($input, FILTER_VALIDATE_EMAIL);
        if ($isEmailFormat) {
            $employeeAccount = EmployeeAccount::where('email', $input)->first();
            $identifier = $employeeAccount ? 'email' : 'email_id';
            if (!$employeeAccount) {
                $employeeAccount = EmployeeAccount::where('email_id', $input)->first();
            }
        } else {
            $identifier = 'employee_no';
            $employeeAccount = EmployeeAccount::where('employee_no', $input)->first();
        }
    
        // Check if account exists and is locked
        if ($employeeAccount && $employeeAccount->isLocked) {
            return redirect()->back()
                ->with(['error' => 'Your account has been locked due to too many failed login attempts. Please contact HR Support.'])
                ->withInput();
        }
    
        if ($employeeAccount && Auth::guard('employee')->attempt([
            $identifier => $input,
            'password' => $request->password
        ])) {
            // Reset login attempts on successful login
            $employeeAccount->login_attempts = 0;
            $employeeAccount->save();

            $employee = Auth::guard('employee')->user();

            if ($employee->information->status !== 'active') {
                Auth::guard('employee')->logout();
                return redirect()->back()
                    ->with(['error' => 'Oops, your account is currently inactive.'])
                    ->withInput();
            }

            return redirect()->route('employee.dashboard');

        } else {

            if ($employeeAccount) {
                $employeeAccount->increment('login_attempts');
    
                $remaining = max(0, 5 - $employeeAccount->login_attempts);
    
                if ($employeeAccount->login_attempts >= 5) {
                    $employeeAccount->isLocked = true;
                    $employeeAccount->save();
                    return redirect()->back()
                        ->with(['error' => 'Your account has been locked due to too many failed login attempts. Please contact HR Support.'])
                        ->withInput();
                }
    
                $employeeAccount->save();
    
                $errorMessage = 'Invalid login or password.';
                if ($remaining > 0) {
                    if ($remaining === 1) {
                        $errorMessage .= "<br>One failed attempt remaining, your account will be locked.";
                    } else {
                        $errorMessage .= "<br>You have {$remaining} attempt(s) remaining.";
                    }
                }
    
                return redirect()->back()
                    ->with(['error' => $errorMessage])
                    ->withInput();
            }
    
            return redirect()->back()
                ->with(['error' => 'Invalid login or password'])
                ->withInput();
        }
    }
    

    public function logout() {
        Auth::guard('employee')->logout();
        return redirect()->route('employee.login');
    }
}