<div class="card border-0 mt-3">
    <div class="card-body p-0">
        <div class="row mb-4">
            <div class="col-md-6 d-flex align-items-center gap-2">
                <label for="entries" class="form-label mb-0">Show entries:</label>
                <select id="entries" wire:model.live="entries" class="form-select w-auto">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="30">30</option>
                    <option value="40">40</option>
                    <option value="50">50</option>
                    <option value="60">60</option>
                    <option value="70">70</option>
                    <option value="80">80</option>
                    <option value="90">90</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div class="col-md-6 text-end d-flex justify-content-end align-items-center gap-2">
                <label for="search" class="form-label mb-0">Search:</label>
                <input id="search" wire:model.live="search" type="text" class="form-control w-50" placeholder="Search employee...">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered w-100">
                <thead>
                    <tr>
                        <th>Employee No</th>
                        <th>Name</th>
                        <th>Credits</th>
                        <th>As Of</th>
                        <th style="max-width: 160px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        @php
                            $employeeNo = $record->employee_no;
                            $fullName = trim(($record->personal->firstname ?? '') . ' ' . ($record->personal->lastname ?? ''));
                        @endphp
                        <tr>
                            <td>{{ $employeeNo }}</td>
                            <td>{{ $fullName !== '' ? $fullName : 'N/A' }}</td>
                            <td>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    wire:model.defer="credits.{{ $employeeNo }}"
                                    class="form-control"
                                >
                                <div class="error-field">
                                    @error("credits.$employeeNo") <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </td>
                            <td>
                                <input
                                    type="date"
                                    wire:model.defer="as_of.{{ $employeeNo }}"
                                    class="form-control"
                                >
                                <div class="error-field">
                                    @error("as_of.$employeeNo") <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </td>
                            <td>
                                <button wire:click="save('{{ $employeeNo }}')" class="btn btn-primary">
                                    <i class="fa-solid fa-save"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center fw-bold py-3">No data was found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $records->links(data: ['scrollTo' => false]) }}
        </div>
    </div>
</div>
