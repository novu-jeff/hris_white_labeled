<form wire:submit.prevent="save" wire:target="save">
    <div>
        <h6 class="text-uppercase fw-bold mb-3">Certifications / Trainings / Seminars</h6>
        <div class="d-flex justify-content-end mb-4">
            <button type="button" class="btn btn-info px-3 py-2 text-uppercase fw-bold" wire:click="addRecord">
                <span wire:loading.remove wire:target="addRecord">Add Certification / Training / Seminar</span>
                <span wire:loading wire:target="addRecord"><i class="fa-solid fa-spinner px-2 fa-spin"></i></span>
            </button>
        </div>
        @if (!empty($records))
            <div class="table-responsive">
                <table class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th rowspan="2" class="text-uppercase text-center"></th>
                            <th rowspan="2" class="text-uppercase text-center">Title of learning and development <br> interventions / training programs <br> (Write in full)</th>
                            <th colspan="2" class="text-uppercase text-center">Inclusive Dates of Attendances</th>
                            <th rowspan="2" class="text-uppercase text-center">Number of Hours</th>
                            <th rowspan="2" class="text-uppercase text-center">Type of LD (Managerial / Supervisory / <br> Technician / etc )</th>
                            <th rowspan="2" class="text-uppercase text-center">Conducted / Sponsored By <br> (Write in full)</th>
                            <th rowspan="2" class="text-uppercase text-center">Documents</th>
                        </tr>
                        <tr>
                            <th class="text-uppercase text-center">From</th>
                            <th class="text-uppercase text-center">To</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $key => $item)
                            <tr>
                                <td>
                                    <button type="button" class="btn btn-danger" wire:click="removeRecord('true', {{$key}})">
                                        <i class="fa-solid fa-circle-minus"></i>
                                    </button>
                                </td>
                                <td>
                                    <input style="width: 600px" type="text" wire:model="records.{{$key}}.name" id="records.{{$key}}.name" class="form-control text-uppercase text-center">
                                    <div class="error-field">
                                        @error('records.'.$key.'.name') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </td>
                                <td>
                                    <input style="width: 300px" type="date" wire:model="records.{{$key}}.date_from" id="records.{{$key}}.date_from" class="form-control text-uppercase text-center">
                                    <div class="error-field">
                                        @error('records.'.$key.'.date_from') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </td>
                                <td>
                                    <input style="width: 300px" type="date" wire:model="records.{{$key}}.date_to" id="records.{{$key}}.date_to" class="form-control text-uppercase text-center">
                                    <div class="error-field">
                                        @error('records.'.$key.'.date_to') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </td>  
                                <td>
                                    <input style="width: 300px" type="number" wire:model="records.{{$key}}.consumed_hours" id="records.{{$key}}.consumed_hours" class="form-control text-uppercase text-center">
                                    <div class="error-field">
                                        @error('records.'.$key.'.consumed_hours') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </td>     
                                <td>
                                    <input style="width: 600px" type="text" wire:model="records.{{$key}}.type" id="records.{{$key}}.type" class="form-control text-uppercase text-center">
                                    <div class="error-field">
                                        @error('records.'.$key.'.type') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </td>                                    
                                <td>
                                    <input style="width: 800px" type="text" wire:model="records.{{$key}}.sponsored_by" id="records.{{$key}}.sponsored_by" class="form-control text-uppercase text-center">
                                    <div class="error-field">
                                        @error('records.'.$key.'.sponsored_by') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </td>     
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div>
                                            <input type="file" style="width: 300px;" wire:model="records.{{$key}}.documents" id="records.{{$key}}.documents" class="form-control">
                                        </div>
                                        @if($records[$key]['documents'])
                                            <div>
                                                <a href="javascript:void(0)" wire:click.prevent="download('{{$key}}')" class="btn btn-primary">
                                                    <i class="fa-solid fa-download"></i>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="error-field">
                                        @error('records.'.$key.'.documents') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </td>                                       
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-danger text-uppercase fw-medium text-center">No data found.</div>
        @endif            
        <div class="card-footer mt-5 pb-3 d-flex justify-content-end bg-transparent border-0">
            <div class="text-end">
                <button type="submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                    <span wire:loading.remove wire:target="save">Save <i class="fa-solid fa-arrow-right ms-2"></i></span>
                    <span wire:loading wire:target="save">Saving <i class="fa-solid fa-spinner ms-2 fa-spin"></i></span>
                </button>
            </div>
        </div>
    </div>
</form>

