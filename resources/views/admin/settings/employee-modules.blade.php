@extends('layouts.admin', [
    'title' => 'HRIS | Employee Modules'
])

@section('content')
<div class="main-content flex-grow-1 p-4">
    <div class="container pb-5">
        <div class="d-lg-flex justify-content-between align-items-center mt-5 mb-4">
            <div class="section-title">
                <h1>Employee Modules</h1>
                <p class="text-muted mb-0">Enable or disable modules on the employee portal. Superadmin only.</p>
            </div>
        </div>
        <div class="mt-3">
            @livewire('admin.settings.employee-modules')
        </div>
    </div>
</div>
@endsection
