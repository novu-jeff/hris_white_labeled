<?php

namespace App\Livewire\Employee;

use App\Models\EmployeeInformation;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Directory extends Component
{

    public $records;
    public $viewMode = 'list'; // 'list' or 'orgchart'
    public $orgChartData = [];

    protected $listeners = [];

    public function mount() {
        $this->loadRecords();
    }

    public function setViewMode($mode) {
        $this->viewMode = $mode;
    }

    private function isCeo(string $positionName): bool {
        if (!$positionName) return false;
        $p = strtolower($positionName);
        return str_contains($p, 'ceo') || str_contains($p, 'chief executive') || str_contains($p, 'president');
    }

    private function isHro(string $positionName): bool {
        if (!$positionName) return false;
        $p = strtolower($positionName);
        return str_contains($p, 'hro') || str_contains($p, 'hr officer') || str_contains($p, 'human resource');
    }

    private function isSecretary(string $positionName): bool {
        if (!$positionName) return false;
        return str_contains(strtolower($positionName), 'secretary');
    }

    /** @return array{ceo: array, hro: array, secretary: array, dept_supervisors: array, other: array} */
    private function classifyEmployees($records): array {
        $ceo = [];
        $hro = [];
        $secretary = [];
        $deptSupervisorNos = [];
        $sectionIdToSupervisorNo = [];
        foreach (DB::table('sections')->select('id', 'supervisor_id')->get() as $s) {
            if ($s->supervisor_id) {
                $deptSupervisorNos[$s->supervisor_id] = true;
                $sectionIdToSupervisorNo[$s->id] = $s->supervisor_id;
            }
        }
        $membersBySupervisor = [];
        $other = [];

        foreach ($records as $record) {
            $positionName = $record->positions?->name ?? '';
            $employeeNo = $record->employee_no;
            $sectionId = $record->section_id;
            $empData = $record->toArray();
            $empData['hierarchy_level'] = '';

            if ($this->isCeo($positionName)) {
                $ceo[] = $empData;
                continue;
            }
            if ($this->isHro($positionName)) {
                $hro[] = $empData;
                continue;
            }
            if ($this->isSecretary($positionName)) {
                $secretary[] = $empData;
                continue;
            }

            $isDeptSupervisor = isset($deptSupervisorNos[$employeeNo]);
            if ($isDeptSupervisor) {
                if (!isset($membersBySupervisor[$employeeNo])) {
                    $membersBySupervisor[$employeeNo] = ['supervisor' => $empData, 'employees' => []];
                }
                continue;
            }

            if ($sectionId && isset($sectionIdToSupervisorNo[$sectionId])) {
                $supNo = $sectionIdToSupervisorNo[$sectionId];
                if (!isset($membersBySupervisor[$supNo])) {
                    $supRecord = $records->firstWhere('employee_no', $supNo);
                    $membersBySupervisor[$supNo] = [
                        'supervisor' => $supRecord ? $supRecord->toArray() : null,
                        'employees' => [],
                    ];
                }
                $membersBySupervisor[$supNo]['employees'][] = $empData;
            } else {
                $other[] = $empData;
            }
        }

        $deptSupervisors = array_values($membersBySupervisor);
        return compact('ceo', 'hro', 'secretary', 'deptSupervisors', 'other');
    }

    public function loadRecords() {

        $records = EmployeeInformation::real()
            ->with('section.department', 'section.branch', 'positions', 'personal', 'account')
            ->get();

        $sortedRecords = $records->sort(function ($a, $b) {
            $branchA = $a->section->branch_id ?? PHP_INT_MAX;
            $branchB = $b->section->branch_id ?? PHP_INT_MAX;
        
            if ($branchA === $branchB) {
                $departmentA = $a->section->department_id ?? PHP_INT_MAX;
                $departmentB = $b->section->department_id ?? PHP_INT_MAX;
        
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
            // Handle null section as "Unassigned Employees"
            if (!$record->section) {
                if (!isset($nestedArray['unassigned'])) {
                    $nestedArray['unassigned'] = [
                        'group_name' => 'Unassigned Employees',
                        'employees' => []
                    ];
                }
                $nestedArray['unassigned']['employees'][] = $record->toArray();
                continue;
            }
        
            // Get branch, department, and section details
            $branchId = $record->section->branch_id;
            $branchName = $record->section->branch->name ?? 'Unknown Branch';
            $departmentId = $record->section->department_id;
            $departmentName = $record->section->department->name ?? 'Unknown Department';
            $sectionId = $record->section_id;
            $sectionName = $record->section->name ?? 'Unknown Section';
        
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
                    'sections' => []
                ];
            }
        
            // Skip sections with name "1"
            if ($sectionName === '1' || trim($sectionName) === '1') {
                continue;
            }
            
            // Initialize the section if it doesn't exist
            if (!isset($nestedArray[$branchId]['departments'][$departmentId]['sections'][$sectionId])) {
                $nestedArray[$branchId]['departments'][$departmentId]['sections'][$sectionId] = [
                    'section_id' => $sectionId,
                    'section_name' => $sectionName,
                    'employees' => []
                ];
            }
            
            // Add the full employee record to the respective section
            $nestedArray[$branchId]['departments'][$departmentId]['sections'][$sectionId]['employees'][] = $record->toArray();
        }
        
        // Convert associative arrays to zero-based indexed arrays
        foreach ($nestedArray as &$branch) {
            if (isset($branch['departments'])) {
                $branch['departments'] = array_values($branch['departments']);
                foreach ($branch['departments'] as &$department) {
                    if (isset($department['sections'])) {
                        $department['sections'] = array_values($department['sections']);
                    }
                }
            }
        }
        
        if (isset($nestedArray['unassigned'])) {
            $nestedArray['unassigned']['employees'] = array_values($nestedArray['unassigned']['employees']);
        }
        
        // Re-index the outer array
        $nestedArray = array_values($nestedArray);
        
        $this->records = $nestedArray;
        
        // Prepare org chart hierarchy data
        $this->prepareOrgChartData($records);
    }

    private function prepareOrgChartData($records) {
        $classified = $this->classifyEmployees($records);
        $this->orgChartData = [
            'ceo' => $classified['ceo'],
            'hro' => $classified['hro'],
            'secretary' => $classified['secretary'],
            'dept_supervisors' => $classified['deptSupervisors'],
            'other' => $classified['other'],
        ];
    }


    public function render()
    {
        return view('livewire.employee.directory');
    }
}
