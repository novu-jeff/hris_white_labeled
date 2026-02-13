<div>
    <form wire:submit.prevent="save">
        <div class="card mb-4 border-0">
            <div class="card-body px-4">
                @if(empty($records['employee_account']['email_id']) && empty($records['employee_account']['company_email']) && empty($records['employee_account']['personal_email']))
                    <div class="alert alert-primary text-center text-uppercase fw-bold">Please complete the required personal information before proceeding with account setup.</div>
                @else
                    <div class="row">
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="records.employee_account.company_email">Company Email</label>
                            <input type="email" class="form-control text-lowercase" wire:model="records.employee_account.company_email" placeholder="Optional — if empty, personal email is used for login and notifications">
                            <small class="text-muted">Used for login and system notifications. Leave empty to use personal email.</small>
                            <div class="error-field">
                                @error('records.employee_account.company_email') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="records.employee_account.personal_email">Personal Email</label>
                            <input type="email" class="form-control text-lowercase" wire:model="records.employee_account.personal_email">
                            <div class="error-field">
                                @error('records.employee_account.personal_email') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="records.employee_account.password">Password <span class="text-danger">*</span></label>
                            <input type="password" wire:model="records.employee_account.password" id="records.employee_account.password" class="form-control">
                            <div class="error-field">
                                @error('records.employee_account.password') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="records.employee_account.confirm_password">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" wire:model="records.employee_account.confirm_password" id="records.employee_account.confirm_password" class="form-control">
                            <div class="error-field">
                                @error('records.employee_account.confirm_password') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="records.employee_account.notify_user">Notify User</label>
                            <input type="checkbox" wire:model="records.employee_account.notify_user" id="records.employee_account.notify_user" class="form-check-input ms-1">
                            <p class="text-uppercase fw-bold text-muted" style="font-size:10px">By Checking this, we will send a notification to the employee's personal email associated with their updated password.</p>
                            <div class="error-field">
                                @error('records.employee_account.notify_user') 
                                    <span class="text-danger">{{ $message }}</span> 
                                @enderror
                            </div>
                        </div>   
                    </div>
                @endif
            </div>
        </div>
        @if (!empty($records) && (filled($records['employee_account']['email_id']) || filled($records['employee_account']['company_email']) || filled($records['employee_account']['personal_email'])))
            <hr class="mb-4">
            <div class="card-footer d-flex justify-content-end bg-transparent border-0">
                <div class="text-end">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <button
                            type="button"
                            class="btn btn-warning px-4 py-3 text-uppercase fw-bold"
                            wire:click="resetPasswordToDefault"
                            wire:loading.attr="disabled"
                            wire:target="resetPasswordToDefault"
                        >
                            <span wire:loading.remove wire:target="resetPasswordToDefault">Reset Password to Default</span>
                            <span wire:loading wire:target="resetPasswordToDefault">Resetting <i class="fa-solid fa-spinner ms-2 fa-spin"></i></span>
                        </button>

                        <button type="submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                            <span wire:loading.remove wire:target="save">Save <i class="fa-solid fa-arrow-right ms-2"></i></span>
                            <span wire:loading wire:target="save">Saving <i class="fa-solid fa-spinner ms-2 fa-spin"></i></span>
                        </button>
                    </div>
                    <div class="mt-3 pb-5">
                        @if ($errors->any())
                            <small class="text-danger">There's an error upon submitting, please review your form.</small>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </form>
</div>
