@extends('layouts.admin', [
    'title' => 'HRIS | Government Sub Items'
])

@section('content')
<div class="main-content flex-grow-1 p-4">
    <div class="container pb-5">
        <div class="d-lg-flex justify-content-between align-items-center mt-5 mb-4">
            <div class="section-title">
                <h1>Government Sub Items</h1>
                <p class="mb-0">Employee: <strong>{{ $employee->employee_no }}</strong></p>
            </div>
            <div class="actions mt-3 mt-lg-0 d-flex gap-2">
                <a href="{{ route('hris.payroll-advanced.index', ['employee_no' => $employee->employee_no]) }}"
                    class="btn btn-outline-primary text-uppercase px-4 py-2 fw-medium">
                    Back
                </a>
            </div>
        </div>

        <div class="alert alert-info border-0">
            <strong>Note:</strong> Statutory values shown in Employee Information are calculated automatically.
            This page is for configuring government-related deduction types and employee-specific deduction values.
        </div>

        <div class="d-flex flex-wrap gap-2 mb-4">
            <a href="{{ route('other-deductions.index') }}" class="btn btn-primary text-uppercase">Deductions Settings</a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <h5 class="mb-0 text-uppercase fw-bold">Government-Related Deduction Items</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th class="text-center" style="width: 180px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($governmentItems as $item)
                                <tr>
                                    <td>{{ $item->code }}</td>
                                    <td>{{ $item->name }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('other-deductions.show', ['other_deduction' => $item->id]) }}"
                                            class="btn btn-sm btn-info text-white">
                                            Manage Employees
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No government-related deduction item found yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
