<div class="card border-0 shadow">
    <div class="card-body p-4">
        <h5 class="card-title mb-4">Payroll Settings</h5>
        <form wire:submit.prevent="save">
            <div class="row">
                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label" for="night_shift_differential">Night Shift Differential (%)</label>
                    <input type="number" step="0.01" min="0" max="100" wire:model="night_shift_differential"
                           id="night_shift_differential" class="form-control" placeholder="10">
                    <small class="text-muted">Default: 10%. Applied to eligible night-shift hours.</small>
                    @error('night_shift_differential')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label" for="payroll_bank_default">Default Payroll Bank</label>
                    <input type="text" wire:model="payroll_bank_default" id="payroll_bank_default"
                           class="form-control" placeholder="e.g. BDO">
                    <small class="text-muted">Default bank shown in employee Salary &amp; Payroll when empty. Must match one of the options below.</small>
                    @error('payroll_bank_default')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12 col-md-8 col-lg-6">
                    <label class="form-label" for="payroll_bank_options">Payroll Bank Options</label>
                    <input type="text" wire:model="payroll_bank_options" id="payroll_bank_options"
                           class="form-control" placeholder="BDO, BPI, Metro Bank, Landbank, Unionbank, Other">
                    <small class="text-muted">Comma-separated list. Include "Other" to allow custom bank names.</small>
                    @error('payroll_bank_options')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>
