<?php

namespace App\Livewire\Admin\Settings\Hris\Deductions;

use App\Models\OtherDeductions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Create extends Component
{
    public $job_category;
    public $fields = [
        'amount_type' => 'amount',
        'computation_mode' => 'manual',
    ];

    protected $listeners = ['populateField'];

    public function rules() {
        $rules = [
            'fields.code' => 'required|string|max:255|unique:other_deductions,code',
            'fields.name' => 'required|string|max:255',
            'fields.amount' => 'required|numeric|min:0',
            'fields.amount_type' => 'required|in:amount,percentage',
            'fields.maximum_amount' => 'nullable|numeric|min:0',
            'fields.computation_mode' => 'required|in:manual,automatic',
        ];

        // If percentage, ensure it's between 0 and 100
        if (isset($this->fields['amount_type']) && $this->fields['amount_type'] === 'percentage') {
            $rules['fields.amount'] = 'required|numeric|min:0|max:100';
        }

        return $rules;
    }

    public function messages() {
        return [
            'fields.code.required' => 'The code field is required.',
            'fields.code.string' => 'The code must be a string.',
            'fields.code.max' => 'The code may not be greater than 255 characters.',

            'fields.name.required' => 'The name field is required.',
            'fields.name.string' => 'The name must be a string.',
            'fields.name.max' => 'The name may not be greater than 255 characters.',

            'fields.amount.required' => 'The amount field is required.',
            'fields.amount.numeric' => 'The amount must be a number.',
            'fields.amount.min' => 'The amount must be 0 or greater.',
            'fields.amount.max' => 'The percentage must not exceed 100%.',
            'fields.amount_type.required' => 'The amount type is required.',
            'fields.amount_type.in' => 'The amount type must be either amount or percentage.',
            'fields.code.unique' => 'The code has already been taken.',
            'fields.maximum_amount.numeric' => 'The maximum amount must be a number.',
            'fields.maximum_amount.min' => 'The maximum amount must be 0 or greater.',
        ];
    }

    public function save() {
        if (Gate::denies('write other-earnings')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!', 
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        $this->validate();

        DB::beginTransaction();

        try {
            OtherDeductions::create([
                'code' => $this->fields['code'],
                'name' => $this->fields['name'],
                'amount' => $this->fields['amount'],
                'amount_type' => $this->fields['amount_type'] ?? 'amount',
                'maximum_amount' => $this->fields['maximum_amount'] ?? null,
                'computation_mode' => $this->fields['computation_mode'] ?? 'manual',
            ]);

            $this->dispatch('alert', [
                'status' => 'success',
                'title' => 'Success!', 
                'showAlert' => true,
                'message' => 'Additional Earning ' . strtoupper($this->fields['code']) . ' was added successfully.'
            ]);
            
            DB::commit();
        
            $this->reset('fields');

        } catch (\Exception $e) {
            DB::rollBack();

            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Oops!', 
                'showAlert' => true,
                'message' => 'Error occured: ' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.hris.deductions.create');
    }
}
