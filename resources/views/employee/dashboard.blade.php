@extends('layouts.employee', [
    'title' => 'ESS | Dashboard'
])

@section('content')

<div class="pb-5">
    @livewire('employee.dashboard')
</div>

@endsection