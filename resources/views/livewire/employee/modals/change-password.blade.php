<div>
    <div class="modal fade" wire:ignore.self id="employee-change-password-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="employee-change-password-modalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5 text-uppercase fw-bold" id="employee-change-password-modalLabel">Change Password</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 col-md-12 mb-3">
                            <label for="employee_old_password" class="mb-2">Old Password <span class="text-danger">*</span></label>
                            <input type="password" wire:model="old_password" id="employee_old_password" class="form-control">
                            <div class="error-field mt-2">
                                @error('old_password') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-12 mb-3">
                            <label for="employee_new_password" class="mb-2">New Password <span class="text-danger">*</span></label>
                            <input type="password" wire:model="new_password" id="employee_new_password" class="form-control">
                            <div class="error-field mt-2">
                                @error('new_password') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-12 mb-3">
                            <label for="employee_confirm_password" class="mb-2">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" wire:model="confirm_password" id="employee_confirm_password" class="form-control">
                            <div class="error-field mt-2">
                                @error('confirm_password') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" wire:click="employee_change_password" wire:loading.attr="disabled" wire:target="employee_change_password">
                        <span wire:loading.remove wire:target="employee_change_password">Proceed</span>
                        <span wire:loading wire:target="employee_change_password">Processing <i class="fa-solid fa-spinner ms-2 fa-spin"></i></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

