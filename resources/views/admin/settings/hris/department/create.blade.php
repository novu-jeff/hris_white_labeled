@extends('layouts.admin', [
    'title' => 'HRIS | Add Department'
])

@section('content')
<div class="main-content flex-grow-1 p-4">  
<div class="container pb-5">
    <div class="d-lg-flex justify-content-between align-items-center mt-5 mb-4">
        <div class="section-title">
            <h1>Add Department</h1>
            <p>Create new department</p>
        </div>
    </div>
    <div class="mt-3">
        @livewire('admin.settings.hris.department.create')
    </div>
</div>
</div>
@endsection
