<form wire:submit.prevent="save" wire:target="save">
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
                            <label class="mb-2" for="date">Date <span class="text-danger">*</span></label>
                            <input type="date" wire:model.live="date" id="date" class="form-control" name="date">
                            <div class="error-field">
                                @error('date') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6 mb-4">
                            <label class="mb-2" for="clock_in">Clock In <span class="text-danger">*</span></label>
                            <input type="text" wire:model="clock_in" id="clock_in" class="timepicker form-control">
                            <div class="error-field">
                                @error('clock_in') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6 mb-4">
                            <label class="mb-2" for="clock_out">Clock Out <span class="text-danger">*</span></label>
                            <input type="text" wire:model="clock_out" id="clock_out" class="timepicker form-control">
                            <div class="error-field">
                                @error('clock_out') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-12 mb-4">
                            <label class="mb-2" for="reason">Reason <span class="text-danger">*</span></label>
                            <textarea wire:model="reason" id="reason" cols="30" rows="10" class="form-control" placeholder="Write here..."></textarea>
                            <div class="error-field">
                                @error('reason') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-12 mb-4">
                            <label class="mb-2" for="attachments">Attachments (optional)</label>
                            <div class="mt-1 mb-3">
                                <small class="text-muted fw-bold">Note: You may provide supporting proof for the reason stated above.</small>
                            </div>
                            <input type="file" wire:model="attachments" id="attachments" class="form-control mb-1" multiple>
                            <small class="text-muted fw-medium fst-italic">Accepts multiple files (jpg, jpeg, png, gif, pdf)</small>
                            <div class="error-field">
                                @error('attachments') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            @if(!is_null($preview_attachments))
                                <div class="attachments mt-3">
                                    <ul class="list-unstyled text-uppercase mt-3">
                                        @foreach($preview_attachments as $item)
                                            <li class="list-unstyled-item">
                                                <div class="d-flex align-items-center gap-2">
                                                    <a class="d-flex align-items-center text-decoration-none" href="{{ Storage::url($item['attachment']) }}" download>
                                                        {{$item['attachment']}}
                                                    </a>
                                                    <button type="button" class="btn text-danger" wire:click="removeAttachment({{$item['id']}})">
                                                        <i class="fa-solid fa-close"></i>
                                                    </button>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <hr class="mx-3">
                <div class="card-footer bg-transparent border-0 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                        <span wire:loading.remove wire:target="save">Proceed <i class="fa-solid fa-arrow-right ms-2"></i></span>
                        <span wire:loading wire:target="save">Proceeding <i class="fa-solid fa-spinner ms-2 fa-spin"></i></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>


    document.addEventListener('livewire:init', () => {
     

        Livewire.on('form-reset', () => {

   

            // Clear file input
            console.log('Resetting file input');
            const fileInput = document.getElementById('attachments');
            if (fileInput) {
                fileInput.value = '';
            }

        });
    });
</script>

