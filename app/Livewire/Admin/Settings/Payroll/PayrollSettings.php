<?php

namespace App\Livewire\Admin\Settings\Payroll;

use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class PayrollSettings extends Component
{
    public string $night_shift_differential = '10';

    public function mount(): void
    {
        $v = Setting::get('night_shift_differential', '10');
        $this->night_shift_differential = $v;
    }

    protected function rules(): array
    {
        return [
            'night_shift_differential' => 'required|numeric|min:0|max:100',
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
        if (Gate::denies('write holidays')) {
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
