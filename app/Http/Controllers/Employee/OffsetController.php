<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;

class OffsetController extends Controller
{

    public function __construct() {
        $this->middleware('permission:read apply-offset')->only('index');
        $this->middleware('permission:write apply-offset')->only(['create', 'edit']);
    }

    public function index()
    {
        return view('employee.offset', [
            'action' => 'view',
            'title' => 'ESS | Offset Applications',
            'header' => 'Manage Offset Applications',
            'sub' => 'Work on non-working days (e.g., weekends) to compensate for absences or planned time off.'
        ]);
    }

    public function create()
    {
        return view('employee.offset', [
            'action' => 'create',
            'title' => 'Apply Offset',
            'header' => 'Offset Application',
            'sub' => 'Apply to work on a non-working day (e.g., weekend) to compensate for a past absence or a future planned absence.'
        ]);

    }

    public function edit(int $id)
    {
        return view('employee.offset', [
            'id' => $id,
            'action' => 'edit',
            'title' => 'Edit Offset Application',
            'header' => 'Edit Application',
            'sub' => 'Feel free to edit or update your offset application.'
        ]);

    }
}
