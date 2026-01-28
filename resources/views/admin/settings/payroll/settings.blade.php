@extends('layouts.admin', [
    'title' => 'HRIS | Payroll Settings'
])

@section('content')
<div class="main-content flex-grow-1 p-4">
    <div class="container pb-5">
        <div class="d-lg-flex justify-content-between align-items-center mt-5 mb-4">
            <div class="section-title">
                <h1>Payroll Settings</h1>
                <p class="text-muted mb-0">Night shift differential and other payroll options.</p>
            </div>
        </div>
        <div class="mt-3">
            @livewire('admin.settings.payroll.payroll-settings')
        </div>
    </div>
</div>
@endsection
