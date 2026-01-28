<?php

namespace App\Livewire\Employee\Modals;

use App\Models\EmployeeAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class ChangePassword extends Component
{
    public $old_password;
    public $new_password;
    public $confirm_password;

    protected $listeners = ['employee_change_password'];

    public function rules()
    {
        return [
            'old_password' => 'required',
            'new_password' => 'required|min:8|same:confirm_password|different:old_password',
            'confirm_password' => 'required|min:8',
        ];
    }

    public function employee_change_password(bool $isNotify = true)
    {
        $this->validate();

        if ($isNotify) {
            return $this->dispatch('showConfirmation', [
                'title' => 'Change password?',
                'message' => 'Please make sure to remember your new password.',
                'action' => 'employee_change_password',
            ]);
        }

        // Use employee guard explicitly (default guard may be 'web')
        $user = Auth::guard('employee')->user();

        if (!$user) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Session Expired',
                'message' => 'Please log in again to change your password.',
                'redirect' => route('employee.login'),
            ]);
        }

        $record = EmployeeAccount::where('employee_no', $user->employee_no)->first();

        if (!$record) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops!',
                'message' => 'Account not found.',
            ]);
        }

        if (!Hash::check($this->old_password, $record->password)) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops!',
                'message' => 'Old password is incorrect.',
            ]);
        }

        $record->password = Hash::make($this->new_password);
        $record->isNew = false;
        $record->isToUpdatePassword = false;
        $record->last_password_updated = Carbon::now();
        $record->save();

        $this->reset();

        return $this->dispatch('alert', [
            'status' => 'success',
            'title' => 'Password Changed Successfully',
            'message' => 'Your password has been changed.',
            'redirect' => '_stay',
        ]);
    }

    public function render()
    {
        return view('livewire.employee.modals.change-password');
    }
}

