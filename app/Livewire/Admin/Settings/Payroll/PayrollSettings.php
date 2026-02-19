<?php

namespace App\Livewire\Admin\Settings\Payroll;

use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class PayrollSettings extends Component
{
    public string $night_shift_differential = '10';

    public string $payroll_bank_default = '';

    public string $payroll_bank_options = '';

    public function mount(): void
    {
        $this->night_shift_differential = Setting::get('night_shift_differential', '10');
        $this->payroll_bank_default = Setting::get('payroll_bank_default', '');
        $this->payroll_bank_options = Setting::get('payroll_bank_options', 'BDO,BPI,Metro Bank,Landbank,Unionbank,Other');
    }

    protected function rules(): array
    {
        return [
            'night_shift_differential' => 'required|numeric|min:0|max:100',
            'payroll_bank_default' => 'nullable|string|max:128',
            'payroll_bank_options' => 'nullable|string|max:500',
        ];
    }

    protected function messages(): array
    {
        return [
            'night_shift_differential.required' => 'Night shift differential is required.',
            'night_shift_differential.numeric' => 'Must be a number.',
            'night_shift_differential.min' => 'Must be at least 0.',
            'night_shift_differential.max' => 'Must not exceed 100.',
        ];
    }

    public function save(): void
    {
        $user = request()->user();
        $canSave = ($user && method_exists($user, 'hasRole') && $user->hasRole('superadmin'))
            || Gate::allows('write holidays');
        if (!$canSave) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!',
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        $this->validate();

        Setting::set('night_shift_differential', $this->night_shift_differential);
        Setting::set('payroll_bank_default', $this->payroll_bank_default);
        Setting::set('payroll_bank_options', $this->payroll_bank_options ?: 'BDO,BPI,Metro Bank,Landbank,Unionbank,Other');

        $this->dispatch('alert', [
            'status' => 'success',
            'title' => 'Saved!',
            'showAlert' => true,
            'message' => 'Payroll settings have been updated.',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.settings.payroll.payroll-settings');
    }
}
