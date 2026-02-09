<?php

namespace App\Livewire\Employee\Profile;

use App\Models\EmployeeAccount;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SecurityNotifications extends Component
{
    public string $employee_no = '';
    public bool $email_notifications_enabled = false;

    protected $listeners = ['saveNotificationSettings'];

    public function mount()
    {
        $user = Auth::guard('employee')->user();
        $this->employee_no = $user?->employee_no ?? '';

        if ($this->employee_no !== '') {
            $account = EmployeeAccount::where('employee_no', $this->employee_no)->first();
            $this->email_notifications_enabled = (bool) ($account->email_notifications_enabled ?? false);
        }
    }

    public function saveNotificationSettings(bool $isNotify = true)
    {
        if ($isNotify) {
            $this->dispatch('showConfirmation', [
                'title' => 'Save settings?',
                'message' => 'This will update your email notification preference.',
                'action' => 'saveNotificationSettings',
            ]);
            return;
        }

        $user = Auth::guard('employee')->user();
        if (!$user) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Session Expired',
                'message' => 'Please log in again.',
                'redirect' => route('employee.login'),
            ]);
        }

        $account = EmployeeAccount::where('employee_no', $user->employee_no)->first();
        if (!$account) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops!',
                'message' => 'Account not found.',
            ]);
        }

        $account->email_notifications_enabled = (bool) $this->email_notifications_enabled;
        $account->save();

        return $this->dispatch('alert', [
            'status' => 'success',
            'title' => 'Success!',
            'message' => $this->email_notifications_enabled
                ? 'Email notifications are now enabled.'
                : 'Email notifications are now disabled.',
            'redirect' => '_stay',
        ]);
    }

    public function render()
    {
        return view('livewire.employee.profile.security-notifications');
    }
}

