<?php

namespace App\Livewire\Employee;

use App\Models\EmployeeInformation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Team extends Component
{
    public $records;

    public function mount() {
        $this->loadRecords();
    }

    public function loadRecords()
    {
        $employeeUser = Auth::guard('employee')->user();

        if (!$employeeUser) {
            $this->records = [];
            return;
        }

        $user = EmployeeInformation::with('section.department')
            ->where('employee_no', $employeeUser->employee_no)
            ->first();

        if (!$user || !$user->section_id) {
            // If no section assigned, redirect or show empty
            $this->records = [];
            return;
        }

        $sectionId = $user->section_id;

        // Get all employees in the same section
        $employees = EmployeeInformation::with([
            'section.department',
            'positions',
            'personal',
            'account'
        ])
            ->where('section_id', $sectionId)
            ->where('isDeleted', false)
            ->where('status', 'active')
            // If an employee account was deleted, don't show it in team listing
            ->whereHas('account')
            ->get();

        // Get supervisor from section
        $supervisorName = 'N/A';
        $sectionData = DB::table('sections')->where('id', $sectionId)->first();
        
        if ($sectionData && $sectionData->supervisor_id) {
            $supervisor = DB::table('employee_personal')
                ->where('employee_no', $sectionData->supervisor_id)
                ->select('firstname', 'lastname')
                ->first();
            
            if ($supervisor) {
                $supervisorName = strtoupper($supervisor->firstname . ' ' . $supervisor->lastname);
            }
        }

        // Group employees by position within the section
        $section = [
            'section_id' => $sectionId,
            // Department should display the section name (e.g., Lazarus)
            'department_name' => $user->section->name ?? 'Unassigned Department',
            'supervisor_name' => $supervisorName,
            'positions' => []
        ];

        foreach ($employees as $emp) {
            $positionId = $emp->position_id ?? 0;
            $positionName = $emp->positions?->name ?? 'Unassigned Position';

            if (!isset($section['positions'][$positionId])) {
                $section['positions'][$positionId] = [
                    'position_id' => $positionId,
                    'position_name' => $positionName,
                    'employees' => []
                ];
            }

            $section['positions'][$positionId]['employees'][] = $emp->toArray();
        }

        // Convert positions associative array to indexed array
        $section['positions'] = array_values($section['positions']);

        // Only one section for logged-in user
        $this->records = [$section];
    }

    public function render()
    {
        return view('livewire.employee.team');
    }
}
