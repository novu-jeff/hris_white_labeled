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
                        <div class="col-12 mb-4">
                            <label class="mb-2" for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" wire:model="name" id="name" class="form-control">
                            <div class="error-field">
                                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-12 mb-4">
                            <label class="mb-2" for="description">Description <span class="text-danger">*</span></label>
                            <div wire:ignore>
                                <textarea wire:model="description" id="ckeditor" class="form-control text-uppercase" rows="5"></textarea>
                            </div>                            
                            <div class="error-field">
                                @error('description') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-12 mb-4">
                            <label class="mb-2" for="shift_duration" id="shift_duration">Shift Duration <span class="text-danger">*</span></label>
                            <select wire:model="shift_duration" id="shift_duration" class="form-select">
                                <option value=""> - CHOOSE - </option>
                                <option value="flexible">Flexible (8 Hours)</option>
                                <option value="standard">Standard Shift (8 Hours)</option>
                                <option value="extended">Extended Shift (12 Hours)</option>
                                <option value="full-day">Full-Day Shift (24 Hours)</option>
                                <option value="compressed">Compressed Workweek (10 Hours)</option>
                                <option value="support">Support (8 Hours)</option>
                                <option value="part-time">Part Time Shift (Below 8 Hours)</option>
                            </select>
                            <div class="error-field">
                                @error('shift_duration') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6 mb-4">
                            <label class="mb-2 d-block">Breaktime</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_breaktime_required" wire:model="is_breaktime_required">
                                <label class="form-check-label" for="is_breaktime_required">
                                    Require lunch break (show Lunch In/Out)
                                </label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 mb-4">
                            <label class="mb-2 d-block">Clocking Rules</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="allow_anytime_clockin" wire:model="allow_anytime_clockin">
                                <label class="form-check-label" for="allow_anytime_clockin">
                                    Allow clock-in anytime (ignore earliest/latest)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="allow_anytime_clockout" wire:model="allow_anytime_clockout">
                                <label class="form-check-label" for="allow_anytime_clockout">
                                    Allow clock-out anytime (ignore minimum hours)
                                </label>
                            </div>
                        </div>
                        @if(!$isSupport)
                            <div class="col-12 col-md-3 mb-3">
                                <label class="mb-2" for="{{ $isFlexible ? 'earliest_in' : 'start_shift' }}">
                                    {{ $isFlexible ? 'Earliest In' : 'Shift Start' }} <span class="text-danger">*</span>
                                </label>
                                <input type="time" wire:model="{{ $isFlexible ? 'earliest_in' : 'start_shift' }}" 
                                    id="{{ $isFlexible ? 'earliest_in' : 'start_shift' }}" class="form-control">
                                <div class="error-field">
                                    @error($isFlexible ? 'earliest_in' : 'start_shift') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            @if($is_breaktime_required)
                                <div class="col-12 col-md-3 mb-3">
                                    <label class="mb-2" for="break_out">Lunch Out <span class="text-danger">*</span></label>
                                    <input type="time" wire:model="break_out" id="break_out" class="form-control">
                                    <div class="error-field">
                                        @error('break_out') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-12 col-md-3 mb-3">
                                    <label class="mb-2" for="break_in">Lunch In <span class="text-danger">*</span></label>
                                    <input type="time" wire:model="break_in" id="break_in" class="form-control">
                                    <div class="error-field">
                                        @error('break_in') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            @endif
                            <div class="col-12 col-md-3 mb-3">
                                <label class="mb-2" for="{{ $isFlexible ? 'latest_in' : 'end_shift' }}">
                                    {{ $isFlexible ? 'Latest In' : 'End Shift' }} <span class="text-danger">*</span>
                                </label>
                                <input type="time" wire:model="{{ $isFlexible ? 'latest_in' : 'end_shift' }}" 
                                    id="{{ $isFlexible ? 'latest_in' : 'end_shift' }}" class="form-control">
                                <div class="error-field">
                                    @error($isFlexible ? 'latest_in' : 'end_shift') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        @endif
                        <div class="col-12 col-md-12 mb-4">
                            <label class="mb-2" for="work_setup" id="work_setup">Work Setup <span class="text-danger">*</span></label>
                            <select wire:model="work_setup" wire:change="changeWorkSetup" id="work_setup" class="form-select">
                                <option value=""> - CHOOSE - </option>
                                <option value="onsite">On Site</option>
                                <option value="hybrid">Hybrid</option>
                            </select>
                            <div class="error-field">
                                @error('work_setup') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 col-md-4 mb-4">
                                <label class="mb-2" for="min_ot_mins" id="min_ot_mins">Minimum Overtime Hours (in minutes) <span class="text-danger">*</span></label>
                                <input type="number" wire:model="min_ot_mins" id="min_ot_mins" class="form-control text-uppercase">
                                <div class="error-field">
                                    @error('min_ot_mins') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-4 mb-4">
                                <label class="mb-2" for="max_ot_time" id="max_ot_time">Overtime Time Until <span class="text-danger">*</span></label>
                                <input type="time" wire:model="max_ot_time" id="max_ot_time" class="form-control" value="22:00">
                                <div class="error-field">
                                    @error('max_ot_time') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    @if($isHybrid)
                        <hr class="mx-3 mb-5">
                        <div class="header mb-4">
                            <h5 class="text-uppercase fw-bold">For Mobile Timekeeping</h5>
                        </div>
                        <div class="row">
                            <div class="col-12 col-md-6 mb-4">
                                <label class="mb-2" for="mobile_earliest_clockin">Earliest Clock In <span class="text-danger">*</span></label>
                                <input type="time" wire:model="mobile_earliest_clockin" id="mobile_earliest_clockin" class="form-control">
                                <div class="error-field">
                                    @error('mobile_earliest_clockin') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>     
                            <div class="col-12 col-md-6 mb-4">
                                <label class="mb-2" for="mobile_latest_clockin">Latest Clock In <span class="text-danger">*</span></label>
                                <input type="time" wire:model="mobile_latest_clockin" id="mobile_latest_clockin" class="form-control">
                                <div class="error-field">
                                    @error('mobile_latest_clockin') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div> 
                        </div>
                        <hr class="mx-3 mb-5">
                        <div class="header mb-4">
                            <h5 class="text-uppercase fw-bold">For Website Timekeeping</h5>
                        </div>
                        <div class="row">
                            <div class="col-12 col-md-6 mb-4">
                                <label class="mb-2" for="web_earliest_clockin">Earliest Clock In <span class="text-danger">*</span></label>
                                <input type="time" wire:model="web_earliest_clockin" id="web_earliest_clockin" class="form-control">
                                <div class="error-field">
                                    @error('web_earliest_clockin') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 mb-4">
                                <label class="mb-2" for="web_latest_clockin">Latest Clock In <span class="text-danger">*</span></label>
                                <input type="time" wire:model="web_latest_clockin" id="web_latest_clockin" class="form-control">
                                <div class="error-field">
                                    @error('web_latest_clockin') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>   
                    @endif
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
