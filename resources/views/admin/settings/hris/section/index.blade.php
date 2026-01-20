@extends('layouts.admin', [
    'title' => 'HRIS | All Departments',
])

@section('content')
<div class="main-content flex-grow-1 p-4">
<div class="container pb-5">
    <div class="d-lg-flex justify-content-between align-items-center mt-5 mb-4">
        <div class="section-title">
            <h1>All Departments</h1>
        </div>
        <div class="actions">
            <a href="{{route('section.create')}}" class="btn btn-primary text-uppercase px-5 py-3 fw-medium">Create New</a>
        </div>
    </div>
    <div class="mt-3">
        @livewire('admin.settings.hris.section.index')
    </div>
</div>
</div>
@endsection