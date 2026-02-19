<div>
    <form wire:submit.prevent="save">
        <div class="row">
            <div class="col-12">
                <div class="card shadow p-4">
                    <div class="card-header bg-transparent border-0">
                        <p class="text-muted mb-0 text-uppercase fst-italic">All <span class="text-danger">*</span> is required</p>
                    </div>
                    <hr class="mx-3">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="d-flex justify-content-end">
                                    <h6 class="text-uppercase fw-bold mb-0">
                                        Remaining Offset Credits: {{ number_format((float) $remaining_offset_credits, 2) }}
                                    </h6>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="mb-2" for="type">Department <span class="text-danger">*</span></label>
                               <input type="text" wire:model="section" class="form-control text-uppercase"  disabled>
                                <div class="error-field">
                                    @error('section') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="mb-2" for="type">Supervisor <span class="text-danger">*</span></label>
                               <input type="text" class="form-control text-uppercase" wire:model="supervisor_name" disabled>
                                <div class="error-field">
                                    @error('supervisor_name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 mb-4">
                                <label class="mb-2" for="type">Lastname <span class="text-danger">*</span></label>
                               <input type="text" class="form-control text-uppercase" wire:model="lastname" disabled>
                                <div class="error-field">
                                    @error('lastname') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-3 mb-4">
                                <label class="mb-2" for="type">Firstname <span class="text-danger">*</span></label>
                               <input type="text" class="form-control text-uppercase" wire:model="firstname" disabled>
                                <div class="error-field">
                                    @error('firstname') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-3 mb-4">
                                <label class="mb-2" for="type">M.I. <span class="text-danger">*</span></label>
                               <input type="text" class="form-control text-uppercase" wire:model="middlename" disabled>
                                <div class="error-field">
                                    @error('middlename') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-3 mb-4">
                                <label class="mb-2" for="type">Position <span class="text-danger">*</span></label>
                               <input type="text" class="form-control text-uppercase" wire:model="position" disabled>
                                <div class="error-field">
                                    @error('position') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="mb-4">  

                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <label class="mb-2" for="date_filed">Date Filed <span class="text-danger">*</span></label>
                               <input type="date" class="form-control" wire:model="date_filed">
                                <div class="error-field">
                                    @error('date_filed') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <label class="mb-2" for="offset_date_from">Offset Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" wire:model="offset_date_from">
                                <div class="error-field">
                                    @error('offset_date_from') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <small class="text-muted">Date of absence or planned time off</small>
                            </div>
                            <div class="col-md-4 mb-4">
                                <label class="mb-2" for="offset_date_to">Activity Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" wire:model="offset_date_to">
                                <div class="error-field">
                                    @error('offset_date_to') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <small class="text-muted">Date to work (non-working day) to compensate</small>
                            </div>
                            <div class="col-12 mb-4">
                                <label class="mb-2" for="purpose">Purpose <span class="text-danger">*</span></label>
                                <textarea wire:model="purpose" id="purpose" cols="30" rows="5" class="form-control text-uppercase" placeholder="State the reason for offset application"></textarea>
                                <div class="error-field">
                                    @error('purpose') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <small class="text-muted">Describe the activity or reason for this offset application</small>
                            </div>
                        </div>
                    </div>
                    <hr class="mx-3">
                    <div class="card-footer bg-transparent border-0 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">Proceed <i class="fa-solid fa-arrow-right ms-1"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </form>    
</div>
