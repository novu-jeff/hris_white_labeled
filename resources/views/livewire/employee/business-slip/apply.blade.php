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

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="mb-2" for="type">Department <span class="text-danger">*</span></label>
                               <input type="text" wire:model="section" class="form-control text-uppercase"  disabled>
                                <div class="error-field">
                                    @error('section') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="mb-2" for="type">Branch <span class="text-danger">*</span></label>
                               <input type="text" class="form-control text-uppercase" wire:model="branch" disabled>
                                <div class="error-field">
                                    @error('branch') <span class="text-danger">{{ $message }}</span> @enderror
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
                            <div class="col-12 mb-4">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="mb-2" for="type">Date Filed <span class="text-danger">*</span></label>
                                       <input type="date" class="form-control text-uppercase" wire:model="date_filed">
                                        <div class="error-field">
                                            @error('date_filed') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="mb-2" for="type">Destination <span class="text-danger">*</span></label>
                                <textarea wire:model="destination" id="destination" cols="30" rows="5" class="form-control text-uppercase" placeholder="Write something..."></textarea>
                                <div class="error-field">
                                    @error('destination') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="mb-2" for="type">Purpose <span class="text-danger">*</span></label>
                                <textarea wire:model="purpose" id="purpose" cols="30" rows="5" class="form-control text-uppercase" placeholder="Write something..."></textarea>
                                <div class="error-field">
                                    @error('purpose') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="mb-2" for="type">Departure Time <span class="text-danger">*</span></label>
                               <input type="text" class="timepicker form-control" wire:model="departure_time">
                                <div class="error-field">
                                    @error('departure_time') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="mb-2" for="type">Arrival Time <span class="text-danger">*</span></label>
                               <input type="text" class="timepicker form-control" wire:model="arrival_time">
                                <div class="error-field">
                                    @error('arrival_time') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
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
