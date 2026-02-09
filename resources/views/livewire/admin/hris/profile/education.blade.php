<form wire:submit.prevent="save" wire:target="save">
    <div>
        <div class="d-flex justify-content-end mb-4">
            <button type="button" class="btn btn-info px-3 py-2 text-uppercase fw-bold" wire:click="addRecord">
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
                            <th rowspan="2" class="text-center">Level</th>
                            <th rowspan="2" class="text-center">Name of School</th>
                            <th rowspan="2" class="text-center">Basic Education / Degree / Course</th>
                            <th colspan="2" class="text-center">Period of Attendance</th>
                            <th rowspan="2" class="text-center">Highest Level / Units Earned <br> (if not graduated)</th>
                            <th rowspan="2" class="text-center">Year Graduated</th>
                            <th rowspan="2" class="text-center">Scholarship / Academic <br> Honors Received</th>
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
                                <select style="width: 300px" wire:model="records.{{$key}}.level" id="records.{{$key}}.level" class="form-select text-uppercase text-center">
                                    <option value=""> - CHOOSE - </option>
                                    <option value="elementary">Elementary</option>
                                    <option value="secondary">Secondary</option>
                                    <option value="vocational">Vocational</option>
                                    <option value="highschool">High School</option>
                                    <option value="senior_highschool">Senior High School</option>
                                    <option value="college">College</option>
                                    <option value="masters">Masters</option>
                                    <option value="doctoral">Doctoral</option>
                                </select>                                                
                                <div class="error-field">
                                    @error('records.'.$key.'.level') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </td>
                            <td>
                                <input type="text" style="width: 600px" wire:model="records.{{$key}}.school_name" id="records.{{$key}}.school_name" class="form-control text-uppercase text-center">
                                <div class="error-field">
                                    @error('records.'.$key.'.school_name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </td>
                            <td>
                                @php $isBasicLevel = in_array($item['level'] ?? '', ['elementary', 'highschool']); @endphp
                                <input type="text" style="width: 800px" wire:model="records.{{$key}}.course" id="records.{{$key}}.course" class="form-control text-uppercase text-center {{ $isBasicLevel ? 'bg-light' : '' }}" @if($isBasicLevel) readonly @endif>
                                <div class="error-field">
                                    @error('records.'.$key.'.course') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </td>
                            <td>
                                <input type="text" style="width: 300px" wire:model="records.{{$key}}.from_year" id="records.{{$key}}.from_year" class="form-control text-uppercase text-center" placeholder="ex. 2020">
                                <div class="error-field">
                                    @error('records.'.$key.'.from_year') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </td>
                            <td>
                                <input type="text" style="width: 300px" wire:model="records.{{$key}}.to_year" id="records.{{$key}}.to_year" class="form-control text-uppercase text-center" placeholder="ex. 2024">
                                <div class="error-field">
                                    @error('records.'.$key.'.to_year') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </td>
                            <td>
                                <input type="text" style="width: 300px" wire:model="records.{{$key}}.highest_level" id="records.{{$key}}.highest_level" class="form-control text-uppercase text-center {{ $isBasicLevel ? 'bg-light' : '' }}" @if($isBasicLevel) readonly @endif>
                                <div class="error-field">
                                    @error('records.'.$key.'.highest_level') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </td>
                            <td>
                                <input type="text" style="width: 300px" wire:model="records.{{$key}}.year_graduated" id="records.{{$key}}.year_graduated" class="form-control text-uppercase text-center" placeholder="ex. 2024">
                                <div class="error-field">
                                    @error('records.'.$key.'.year_graduated') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </td>
                            <td>
                                <input type="text" style="width: 800px" wire:model="records.{{$key}}.scholarship_honors" id="records.{{$key}}.scholarship_honors" class="form-control text-uppercase text-center">
                                <div class="error-field">
                                    @error('records.'.$key.'.scholarship_honors') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </td>
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

