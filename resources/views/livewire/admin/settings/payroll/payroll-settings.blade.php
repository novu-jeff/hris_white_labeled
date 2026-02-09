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
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>
