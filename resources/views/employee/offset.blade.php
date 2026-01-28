@extends('layouts.employee', [
    'title' => $title
])

@section('content')
<div class="main-content flex-grow-1 p-4">
<div class="container pb-5">
    <div class="mt-5 d-lg-flex justify-content-between align-items-start">
        <div class="section-title">
            <h1>{{$header}}</h1>
            <p>{{$sub}}</p>
        </div>
        <div class="action">
            @if ($action === 'view')
                <a href="{{route('employee.offset.apply')}}" class="btn btn-primary text-uppercase px-5 py-3 fw-medium">Apply Now</a>
            @endif
        </div>
    </div>
    <div class="mt-3">
        @if ($action == 'view')
            @livewire('employee.offset.index')
        @else 
            @livewire('employee.offset.apply', [
                'record_id' => $id ?? null
            ])
        @endif
    </div>
</div>
</div>
@endsection
