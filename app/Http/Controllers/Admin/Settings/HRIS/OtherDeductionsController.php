<?php

namespace App\Http\Controllers\Admin\Settings\HRIS;

use App\Http\Controllers\Controller;
use App\Models\OtherDeductions;
use Illuminate\Http\Request;

class OtherDeductionsController extends Controller
{

    public function __construct() {
        $this->middleware('permission:read other-deductions')->only(['index', 'show']);
        $this->middleware('permission:write other-deductions')->only(['create', 'edit']);
    }

    public function index()
    {
        return view('admin.settings.hris.deductions.index');
    }

    public function create()
    {
        return view('admin.settings.hris.deductions.create');
    }

    public function edit(int $id)
    {
        return view('admin.settings.hris.deductions.edit', compact('id'));
    }

    public function show(Request $request, int $id)
    {
        $action = $request->action ?? '';
        $employee = $request->employee ?? '';
        
        $deduction = OtherDeductions::find($id);
        $deductionName = $deduction ? $deduction->name : 'Other Deduction';
        
        return view('admin.settings.hris.deductions.show', compact('id', 'action', 'employee', 'deductionName'));
    }
}
