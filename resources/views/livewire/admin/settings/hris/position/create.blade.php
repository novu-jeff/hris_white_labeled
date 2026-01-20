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
                            <label class="mb-2" for="name">File <span class="text-danger">*</span></label>
                            <input type="file" wire:model="file" id="file" class="form-control">
                            <div class="mt-2">
                                <small class="text-muted text-uppercase">(only accepts csv file)</small>
                                <small><a href="{{asset('templates/defaults/positions.csv')}}" class="nav-link text-decoration-underline">Download Template</a></small>
                            </div>
                            <div class="error-field">
                                @error('file') <span class="text-danger">{{ $message }}</span> @enderror
                                @error('records') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        @if($records)
                            <div class="col-12 mb-4">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Position</th>
                                           
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($records as $key => $data)
                                            <tr>
                                                <td>
                                                    <input type="text" wire:model="records.{{ $key }}.Position" class="form-control">
                                                </td>
                                                
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif 
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
