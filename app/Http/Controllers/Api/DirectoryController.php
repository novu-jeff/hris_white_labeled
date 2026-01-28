<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeInformation;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    public function index() {
        
        $records = EmployeeInformation::real()
            ->with('branch', 'department', 'positions', 'personal', 'account')
            ->get();

        $sortedRecords = $records->sort(function ($a, $b) {
            $branchA = $a->branch_id ?? PHP_INT_MAX;
            $branchB = $b->branch_id ?? PHP_INT_MAX;
        
            if ($branchA === $branchB) {
                $departmentA = $a->department_id ?? PHP_INT_MAX;
                $departmentB = $b->department_id ?? PHP_INT_MAX;
        
                if ($departmentA === $departmentB) {
                    return ($a->position_id ?? PHP_INT_MAX) <=> ($b->position_id ?? PHP_INT_MAX);
                }
                return $departmentA <=> $departmentB;
            }
            return $branchA <=> $branchB;
        });
        
        // Initialize the nested array
        $nestedArray = [];
        
        // Grouping into the nested structure
        foreach ($sortedRecords as $record) {
            // Get branch, department, and position details
            $branchId = $record->branch_id ?? 'No Branch';
            $departmentId = $record->department_id ?? 'No Department';
            $positionId = $record->position_id ?? 'No Position';
            $positionName = $record->positions->name ?? 'Unknown Position'; 
        
            // Get branch name and department name from relationships
            $branchName = $record->branch->name ?? 'Unknown Branch';
            $departmentName = $record->department->name ?? 'Unknown Department';
        
            // Initialize the branch if it doesn't exist
            if (!isset($nestedArray[$branchId])) {
                $nestedArray[$branchId] = [
                    'branch_id' => $branchId,
                    'branch_name' => $branchName,
                    'departments' => []
                ];
            }
        
            // Initialize the department if it doesn't exist
            if (!isset($nestedArray[$branchId]['departments'][$departmentId])) {
                $nestedArray[$branchId]['departments'][$departmentId] = [
                    'department_id' => $departmentId,
                    'department_name' => $departmentName,
                    'positions' => []
                ];
            }
        
            // Initialize the position if it doesn't exist
            if (!isset($nestedArray[$branchId]['departments'][$departmentId]['positions'][$positionId])) {
                $nestedArray[$branchId]['departments'][$departmentId]['positions'][$positionId] = [
                    'position_id' => $positionId,
                    'position_name' => $positionName, // Store the position name
                    'employees' => [] // Store employees under this position
                ];
            }
        
            // Add the full employee record to the respective position
            $nestedArray[$branchId]['departments'][$departmentId]['positions'][$positionId]['employees'][] = $record->toArray();
        }
        
        // Convert associative arrays to zero-based indexed arrays
        foreach ($nestedArray as &$branch) {
            $branch['departments'] = array_values($branch['departments']);
            foreach ($branch['departments'] as &$department) {
                $department['positions'] = array_values($department['positions']);
            }
        }

        $nestedArray = array_values($nestedArray);
        
        return response()->json([
            'status' => true,
            'data' => $nestedArray
        ]);
    }
}
