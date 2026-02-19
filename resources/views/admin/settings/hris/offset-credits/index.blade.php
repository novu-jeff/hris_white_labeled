@extends('layouts.admin', [
    'title' => 'HRIS | Offset Credits'
])

@section('content')
<div class="main-content flex-grow-1 p-4">
<div class="container pb-5">
    <div class="d-lg-flex justify-content-between align-items-center mt-5 mb-4">
        <div class="section-title">
            <h1>Offset Credits</h1>
            <p>Manage employee offset credits.</p>
        </div>
    </div>
    <div class="mt-3">
        @livewire('admin.settings.hris.offset-credits.index')
    </div>
</div>
</div>
@endsection
