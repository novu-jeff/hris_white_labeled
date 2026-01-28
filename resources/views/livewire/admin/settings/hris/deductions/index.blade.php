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
                <input id="search" wire:model.live="search" type="text" class="form-control w-50" placeholder="Search something...">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-bordered w-100">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th style="max-width: 200px;">Action</th>
                    </tr>
                </thead>                
                <tbody>
                    @forelse($records as $record)
                        <tr data-id="{{$record->id}}">
                            <td>{{$record->code}}</td>
                            <td>{{$record->name}}</td>
                            <td class="d-flex justify-content-start gap-2">
                               <a href="{{route('other-deductions.show', ['other_deduction' => $record->id])}}" class="btn btn-info mx-1" title="Manage Employees - Add/Edit Deductions">
                                    <i class="fa-solid fa-users text-white me-1"></i>
                                    <span class="d-none d-md-inline">Manage</span>
                                </a>
                                <a href="{{route('other-deductions.edit', ['other_deduction' => $record->id])}}" class="btn btn-primary mx-1" title="Edit Deduction Type">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <button wire:click="remove(true, {{$record->id}})" class="btn btn-danger mx-1" title="Delete Deduction Type">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center fw-bold py-3">No data was found</td>
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