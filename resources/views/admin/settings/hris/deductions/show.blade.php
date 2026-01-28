@extends('layouts.admin', [
    'title' => 'HRIS | Other Deductions'
])

@section('content')
<div class="main-content flex-grow-1 p-4">
    <div class="container pb-5">
        <div class="d-lg-flex justify-content-between align-items-center mt-5 mb-4">
            <div class="section-title">
                <h1>Manage Employees - {{ $deductionName ?? 'Other Deduction' }}</h1>
                <p class="text-muted mb-0">Add or edit deduction amounts for each employee</p>
            </div>
            <div class="actions">
                <a href="{{route('other-deductions.index')}}" class="btn btn-outline-primary text-uppercase px-5 py-3 fw-medium">Go Back</a>
            </div>
        </div>
        <div class="mt-3">
            @livewire('admin.settings.hris.deductions.show', [
                'id' => $id,
                'employee_no' => $employee ?? null,
            ])
        </div>
    </div>
</div>
@endsection
