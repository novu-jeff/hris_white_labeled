<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RemainingCreditController extends Controller
{

    public function __construct() {
        $this->middleware('permission:read remaining-credit')->only('index');
    }

    public function index() {
        return view('employee.credit', [
            'title' => 'ESS | My Credits',
            'header' => 'Remaining Credits',
            'sub' => 'All leave and offset credits',
            'action' => 'index'
        ]);  
    }
}
