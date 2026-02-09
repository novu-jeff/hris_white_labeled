<form wire:submit.prevent="save" wire:target="save">
    <div>
        <div class="d-flex justify-content-end mb-4">
            <button type="button" class="btn btn-info px-3 py-2 text-uppercase text-center fw-bold" wire:click="addRecord">
                <span wire:loading.remove wire:target="addRecord">Add Record </span>
                <span wire:loading wire:target="addRecord"><i class="fa-solid fa-spinner px-2 fa-spin"></i></span>
            </button>
        </div>
        @if (!empty($records))
            <div class="table-responsive">
                <table class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th rowspan="2" class="text-center"></th>
                            <th colspan="2" class="text-center">Inclusive Dates <br> (Month / Year)</th>
                            <th rowspan="2" class="text-center">Position Title <br> (Write in full / Do not abbreviate)</th>
                            <th rowspan="2" class="text-center">Department / Agency / Office / Company <br> (Write in full / Do not abbreviate)</th>
                            <th rowspan="2" class="text-center">Monthly Salary</th>
                            @if(config('app.product') != 'private')
                            <th rowspan="2" class="text-center">Salary / Job / Pay Grade (if applicable) <br> & Step (Format "00-0") / Increment</th>
                            <th rowspan="2" class="text-center">Status of Appointment</th>
                            <th rowspan="2" class="text-center">Gov't Service (Y / N)</th>
                            @endif
                            <th rowspan="2" class="text-center">Documents</th>
                        </tr>
                        <tr>
                            <th class="text-center">From</th>
                            <th class="text-center">To</th>
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
                                    <input style="width: 300px" type="text" wire:model="records.{{$key}}.from_year" id="records.{{$key}}.from_year" class="form-control text-uppercase text-center" placeholder="MM/YYYY">
                                    <div class="error-field">
                                        @error('records.'.$key.'.from_year') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </td>
                                <td>
                                    <input style="width: 300px" type="text" wire:model="records.{{$key}}.to_year" id="records.{{$key}}.to_year" class="form-control text-uppercase text-center" placeholder="MM/YYYY">
                                    <div class="error-field">
                                        @error('records.'.$key.'.to_year') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </td>
                                <td>
                                    <input style="width: 600px" type="text" wire:model="records.{{$key}}.position" id="records.{{$key}}.position" class="form-control text-uppercase text-center">
                                    <div class="error-field">
                                        @error('records.'.$key.'.position') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </td>
                                <td>
                                    <input style="width: 800px" type="text" wire:model="records.{{$key}}.department" id="records.{{$key}}.department" class="form-control text-uppercase text-center">
                                    <div class="error-field">
                                        @error('records.'.$key.'.department') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </td>
                                <td>
                                    <input style="width: 300px" type="number" wire:model="records.{{$key}}.monthly_salary" id="records.{{$key}}.monthly_salary" class="form-control text-uppercase text-center">
                                    <div class="error-field">
                                        @error('records.'.$key.'.monthly_salary') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </td>
                                @if(config('app.product') != 'private')
                                <td>
                                    <input style="width: 100%" type="text" wire:model="records.{{$key}}.salary_pay_grade" id="records.{{$key}}.salary_pay_grade" class="form-control text-uppercase text-center">
                                    <div class="error-field">
                                        @error('records.'.$key.'.salary_pay_grade') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </td>
                                <td>
                                    <input style="width: 500px" type="text" wire:model="records.{{$key}}.employment_status" id="records.{{$key}}.employment_status" class="form-control text-uppercase text-center">
                                    <div class="error-field">
                                        @error('records.'.$key.'.employment_status') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </td>
                                <td>
                                    <select style="width: 300px" wire:model="records.{{$key}}.isGovernment" id="records.{{$key}}.isGovernment" class="form-select text-uppercase text-center">
                                        <option value=""> - CHOOSE - </option>
                                        <option value="yes">Yes</option>
                                        <option value="no">No</option>
                                    </select>
                                    <div class="error-field">
                                        @error('records.'.$key.'.isGovernment') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </td>
                                @endif
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div>
                                            <input type="file" style="width: 300px;" wire:model="records.{{$key}}.documents" id="records.{{$key}}.documents" class="form-control">
                                        </div>
                                        @if($records[$key]['document_control'])
                                            <div class="d-flex gap-2">
                                                <a href="javascript:void(0)" wire:click.prevent="download('{{$key}}')" class="btn btn-primary">
                                                    <i class="fa-solid fa-download"></i>
                                                </a>
                                                <a href="javascript:void(0)" wire:click.prevent="removeDocument('true', '{{$key}}')" class="btn btn-danger">
                                                    <i class="fa-solid fa-trash"></i>
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
            <div class="alert alert-danger text-uppercase text-center fw-medium text-center">No data Found.</div>
        @endif            
        <div class="card-footer mt-5 pb-3 d-flex justify-content-end bg-transparent border-0">
            <div class="text-end">
                <button type="submit" class="btn btn-primary px-5 py-3 text-uppercase text-center fw-bold">
                    <span wire:loading.remove wire:target="save">Save <i class="fa-solid fa-arrow-right ms-2"></i></span>
                    <span wire:loading wire:target="save">Saving <i class="fa-solid fa-spinner ms-2 fa-spin"></i></span>
                </button> 
            </div>
        </div>
    </div>
</form>

