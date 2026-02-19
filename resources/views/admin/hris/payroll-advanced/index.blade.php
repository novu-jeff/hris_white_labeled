@extends('layouts.admin', [
    'title' => 'HRIS | Payroll Advanced'
])

@section('content')
<div class="main-content flex-grow-1 p-4">
    <div class="container pb-5">
        <div class="d-lg-flex justify-content-between align-items-center mt-5 mb-4">
            <div class="section-title">
                <h1>Payroll Advanced Setup</h1>
                <p class="mb-0">
                    Employee: <strong>{{ $employee->employee_no }}</strong>
                </p>
            </div>
            <div class="actions mt-3 mt-lg-0">
                <a href="{{ route('hris.show', ['employee_no' => $employee->employee_no, 'form' => 'information']) }}"
                    class="btn btn-outline-primary text-uppercase px-4 py-2 fw-medium">
                    Back to Employee Information
                </a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h5 class="fw-bold text-uppercase">Allowance Sub Items</h5>
                        <p class="text-muted small mb-4">
                            Manage allowance-related earning items and employee assignment rules.
                        </p>
                        <a href="{{ route('hris.payroll-advanced.allowances', ['employee_no' => $employee->employee_no]) }}"
                            class="btn btn-primary mt-auto text-uppercase">
                            Open
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h5 class="fw-bold text-uppercase">De Minimis Sub Items</h5>
                        <p class="text-muted small mb-4">
                            Manage de minimis earning item definitions and employee allocations.
                        </p>
                        <a href="{{ route('hris.payroll-advanced.de-minimis', ['employee_no' => $employee->employee_no]) }}"
                            class="btn btn-primary mt-auto text-uppercase">
                            Open
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h5 class="fw-bold text-uppercase">Government Sub Items</h5>
                        <p class="text-muted small mb-4">
                            Manage government deduction items and per-employee deduction entries.
                        </p>
                        <a href="{{ route('hris.payroll-advanced.government', ['employee_no' => $employee->employee_no]) }}"
                            class="btn btn-primary mt-auto text-uppercase">
                            Open
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
