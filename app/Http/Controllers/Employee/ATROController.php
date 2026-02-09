<?php

namespace App\Http\Controllers\Employee;


use App\Models\EmployeeAtro;
use App\Http\Controllers\Controller;

class ATROController extends Controller
{
    
    public function __construct() {
        $this->middleware('permission:read apply-atro')->only('index');
        $this->middleware('permission:write apply-atro')->only(['create', 'edit']);
    }

    public function index() {
        return view('employee.atro', [
            'action' => 'view',
            'title' => 'ESS | Authority to render overtime',
            'header' => 'Authority to render overtime ',
            'sub' => 'Track and monitor your overtime applications.'
        ]);
    }

    public function create()
    {
        return view('employee.atro', [
            'action' => 'create',
            'title' => 'ESS | Authority to render overtime',
            'header' => 'Apply Authority to render overtime',
            'sub' => 'By proceeding, you\'ll be able to apply for rendering overtime.'
        ]);

    }

    public function edit(int $id) {
        $record = EmployeeAtro::findOrFail($id);

       
        if($record->status == 'pending'){
            $action = 'Edit';
        }

        if($record->status == 'disapproved'){
            $action = 'View';
        }


        return view('employee.atro', [
            'id' => $id,
            'action' => $action,
            'status' => $record->status,   // ⬅ added here
            'title' => ''.$action .' | Authority to render overtime',
            'header' => ''.$action .' Authority to render overtime',
            'sub' => ''
        ]);
    }

    
}
