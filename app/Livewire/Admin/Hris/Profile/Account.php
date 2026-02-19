<?php

namespace App\Livewire\Admin\Hris\Profile;

use App\Http\Controllers\Admin\Services\HRISProcessingService;
use App\Models\EmployeeAccount;
use App\Notifications\Notifications;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use App\Models\EmployeeInformation;
use Livewire\Component;

class Account extends Component
{

    public $employee_id;
    public $employee_no;
    public $originalData;
    public $records;

    protected $listeners = ['save', 'resetPasswordToDefault'];

    public function mount() {
        $this->loadRecords();
    }

    public function loadRecords() {
        $data = EmployeeAccount::where('employee_no', $this->employee_no)->first();
        $this->originalData = $data;
        $this->records['employee_account'] = $this->formatRecords($data);
    }

    protected function formatRecords($data) {
        $personalEmail = $data->email ?? ($data->account['email'] ?? null);
        $companyEmail = $data->company_email ?? null;

        $fields = [
            'email_id', 'company_email', 'personal_email'
        ];

        $formattedAccount = array_combine(
            $fields,
            array_map(fn($field) => match ($field) {
                'personal_email' => $personalEmail,
                'company_email' => $companyEmail,
                default => $data[$field] ?? null,
            }, $fields)
        );

        return $formattedAccount;
    }

    protected function rules(?string $employee_no = null) {
        return [
            'records.employee_account.company_email' => 'nullable|email',
            'records.employee_account.personal_email' => 'required|email',
            'records.employee_account.notify_user' => 'nullable|boolean',
            'records.employee_account.password' => 'nullable|min:8|same:records.employee_account.confirm_password',
            'records.employee_account.confirm_password' => 'required_with:records.employee_account.password|min:8'
        ];
    }

    protected function messages() {
        return [
            'records.employee_account.company_email.email' => 'The company email must be a valid email.',
            'records.employee_account.personal_email.required' => 'The personal email is required.',
            'records.employee_account.personal_email.email' => 'The personal email must be a valid email.',        
            'records.employee_account.notify_user.boolean' => 'The notify user field must be true or false.',        
            'records.employee_account.password.required' => 'The password is required.',
            'records.employee_account.password.min' => 'The password must be at least 8 characters.',
            'records.employee_account.password.same' => 'The password and confirmation password must match.',
            'records.employee_account.confirm_password.required_with' => 'The confirm password field is required.',
            'records.employee_account.confirm_password.min' => 'The confirm password must be at least 8 characters.',
        ];
    }

    public function hasChanges()
    {
        $originalData = $this->originalData?->toArray() ?? [];
        $records = $this->records;

        foreach ($records as $key => $newValue) {
            if (!array_key_exists($key, $originalData)) {
                return true;
            }

            $oldValue = $originalData[$key];

            if ($newValue !== $oldValue) {
                return true;
            }
        }

        return false;
    }
    public function setErrorActiveTabAccordions(array $errorKeys) {
        $this->dispatch('scrollToError', $errorKeys);
    }

    public function save(bool $isNotify = true) {

        if (Gate::denies('write hris')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!', 
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        $id = $this->employee_no;

        try {
            $this->validate($this->rules($id));
        } catch (ValidationException $e) {
            $errors = $e->validator->errors()->keys();
            $this->setErrorActiveTabAccordions($errors);
            $this->dispatch('scrollToError', $errors);
            throw $e;
        }

        if(!$this->hasChanges()) {
            return $this->dispatch('alert', [
                'status' => 'info',
                'title' => 'Please be informed!',
                'isRemoveRowDT' => false,
                'showAlert' => true,
                'message' => 'Unable to save because no changes were made, feel free to edit or update your informations first before saving. Thank you!'
            ]);
        }

        if($isNotify) {
            $title = 'Are you sure to continue?';
            $message = 'The action cannot be undone or reverted!';
            $action = 'save';
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

            return;
        }

        $record = EmployeeInformation::where('employee_no', $id)->first();
        if (!$record) {
            return $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Oops',
                'isRemoveRowDT' => false,
                'showAlert' => true,
                'message' => 'Error: You\'re saving a non-existent employee!'
            ]);
        }

        DB::beginTransaction();

        try {

            $process = new HRISProcessingService;
            $process->save(false, $id, $id, 'account', $this->records);

            DB::commit();

            $this->records['employee_account']['password'] = null;
            $this->records['employee_account']['confirm_password'] = null;
            $this->records['employee_account']['notify_user'] = null;

            return $this->dispatch('alert', [
                'status' => 'success',
                'title' => 'Success!',
                'isRemoveRowDT' => false,
                'isReloadDT' => false,
                'message' => 'Employee ' . strtoupper($id) . ' records saved successfully.',
                'redirect' => '_stay'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Oops!',
                'isRemoveRowDT' => true,
                'showAlert' => true,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function resetPasswordToDefault(bool $isNotify = true)
    {
        if (Gate::denies('write hris')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!',
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        if ($isNotify) {
            $this->dispatch('showConfirmation', [
                'title' => 'Reset employee password?',
                'message' => 'This will reset the password to the default and force the employee to change it on next login.',
                'action' => 'resetPasswordToDefault',
            ]);
            return;
        }

        $account = EmployeeAccount::where('employee_no', $this->employee_no)->first();

        if (!$account) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Oops!',
                'showAlert' => true,
                'message' => 'Employee account not found.',
            ]);
            return;
        }

        // Reset to default password and force "first time login" flow
        $account->password = Hash::make('password');
        $account->isNew = true;
        $account->isToUpdatePassword = false;
        $account->last_password_updated = null;
        $account->save();

        // Notify employee (in-app + optional email based on employee preference)
        try {
            $message = 'Your password was reset by HR. Please log in and set a new password.';
            $redirect = route('employee.dashboard');
            $account->notify(new Notifications('info', $message, $redirect, 'employee'));
        } catch (\Exception $e) {
            // ignore notification failures, but keep reset successful
        }

        $this->dispatch('alert', [
            'status' => 'success',
            'title' => 'Success!',
            'message' => 'Employee password was reset to default and the account was set to first-time login state.',
            'redirect' => '_stay',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.hris.profile.account');
    }
}
