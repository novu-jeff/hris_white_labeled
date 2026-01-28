@extends('layouts.admin', [
    'title' => 'HRIS | ESS Offset Application'
])

@section('content')
<div class="main-content flex-grow-1 p-4">
<div class="container pb-5">
    <div class="d-lg-flex justify-content-between align-items-center mt-5 mb-4">
        <div class="section-title">
            <h1>Offset Application</h1>
            <p>Manage all offset applications. Employees work on non-working days (e.g., weekends) to compensate for absences or planned time off.</p>
        </div>
    </div>
    <div class="mt-3">
        @livewire('admin.ess.offset.index', [
            'status' => $status
        ])
    </div>
</div>
</div>
@endsection
