<form wire:submit.prevent="save">
    <div class="row">
        <div class="col-12">
            <div class="card shadow p-4">
                <div class="card-header bg-transparent border-0">
                    <p class="text-muted mb-0 text-uppercase fst-italic">All <span class="text-danger">*</span> is required</p>
                </div>
                <hr class="mx-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 col-md-12 mb-4">
                            <label class="mb-2" for="fields.code">Code <span class="text-danger">*</span></label>
                            <input type="text" wire:model="fields.code" id="fields.code" class="form-control text-uppercase">
                            <div class="error-field">
                                @error('fields.code') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-12 mb-4">
                            <label class="mb-2" for="fields.name">Name <span class="text-danger">*</span></label>
                            <input type="text" wire:model="fields.name" id="fields.name" class="form-control text-uppercase">
                            <div class="error-field">
                                @error('fields.name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6 mb-4">
                            <label class="mb-2" for="fields.amount_type">Amount Type <span class="text-danger">*</span></label>
                            <select wire:model="fields.amount_type" id="fields.amount_type" class="form-select">
                                <option value="amount">Amount (Fixed)</option>
                                <option value="percentage">Percentage</option>
                            </select>
                            <div class="error-field">
                                @error('fields.amount_type') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6 mb-4">
                            <label class="mb-2" for="fields.amount">
                                @if(isset($fields['amount_type']) && $fields['amount_type'] == 'percentage')
                                    Percentage <span class="text-danger">*</span>
                                @else
                                    Amount <span class="text-danger">*</span>
                                @endif
                            </label>
                            <div class="input-group">
                                <input type="number" 
                                    wire:model="fields.amount" 
                                    id="fields.amount" 
                                    class="form-control" 
                                    step="{{ isset($fields['amount_type']) && $fields['amount_type'] == 'percentage' ? '0.01' : '0.01' }}"
                                    min="0"
                                    max="{{ isset($fields['amount_type']) && $fields['amount_type'] == 'percentage' ? '100' : '' }}"
                                    placeholder="{{ isset($fields['amount_type']) && $fields['amount_type'] == 'percentage' ? '0.00 - 100.00' : '0.00' }}">
                                <span class="input-group-text">
                                    @if(isset($fields['amount_type']) && $fields['amount_type'] == 'percentage')
                                        %
                                    @else
                                        ₱
                                    @endif
                                </span>
                            </div>
                            <div class="error-field">
                                @error('fields.amount') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            @if(isset($fields['amount_type']) && $fields['amount_type'] == 'percentage')
                                <small class="text-muted">Enter a value between 0 and 100</small>
                            @endif
                        </div>
                        <div class="col-12 col-md-12 mb-4">
                            <label class="mb-2" for="fields.maximum_amount">Maximum Amount</label>
                            <div class="input-group">
                                <input type="number" 
                                    wire:model="fields.maximum_amount" 
                                    id="fields.maximum_amount" 
                                    class="form-control" 
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00">
                                <span class="input-group-text">₱</span>
                            </div>
                            <div class="error-field">
                                @error('fields.maximum_amount') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <small class="text-muted">Optional: Set a maximum cap for this deduction (leave empty for no limit)</small>
                        </div>
                        <div class="col-12 col-md-12 mb-4">
                            <label class="mb-2" for="fields.computation_mode">Computation <span class="text-danger">*</span></label>
                            <select wire:model="fields.computation_mode" id="fields.computation_mode" class="form-select">
                                <option value="manual">Manual (HR inputs per employee)</option>
                                <option value="automatic">Automatic (system calculates where supported)</option>
                            </select>
                            <div class="error-field">
                                @error('fields.computation_mode') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <small class="text-muted">Manual: HR enters amount per employee (e.g. Withholding Tax, Gov't contributions, Social Responsibility). Automatic: system computes where applicable.</small>
                        </div>
                    </div>
                </div>
                <hr class="mx-3">
                <div class="card-footer bg-transparent border-0 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                        <span wire:loading.remove wire:target="save">Save <i class="fa-solid fa-arrow-right ms-2"></i></span>
                        <span wire:loading wire:target="save">Saving <i class="fa-solid fa-spinner ms-2 fa-spin"></i></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
