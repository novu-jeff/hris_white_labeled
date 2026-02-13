<?php

namespace App\Livewire\Admin\Settings\Roles;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Show extends Component
{

    public $id;
    public $permissions = [];
    public $selectedPermissions = [];
    public $roleId; 

    protected $listeners = ['updatePermissions'];

    public function mount()
    {
        $this->loadDefaultPermissions();

        $this->loadSavedPermissions();
    }

    public function loadDefaultPermissions() {
        $this->roleId = $this->id;
    
        $role = Role::find($this->roleId);
    
        // Initialize the full set of permissions
        $this->permissions = [
            'recruitment' => ['jobs', 'applicants'],
            'hris' => ['hris'],
            'timekeeping' => ['timelogs', 'correction-timelogs'],
            'payroll' => [],
            'ess' => [
                'leave', 'obs', 'offset', 'atro', 'time-adjustments', 'payslip-request', 'announcements', 
                'employee-profile-approval', 'messages', 'faqs'],
            'reports' => ['dtr', 'bir-2316'],
            'settings' => [
                'company-information', 'scheduler', 'branches', 'departments', 'sections', 'assessments', 'requirements',
                'users', 'roles', 'bank-information', 'employment-type', 'positions', 'violations',
                'leave-types', 'leave-credits', 'offset-credits', 'gsis-billing', 'employee-earnings', 'employee-deductions', 'other-earnings', 'other-deductions',
                'shift-schedule', 'employee-schedule', 'holidays', 'payroll-period', 'payroll-configuration',
            ],
            'employee' => [
                'apply-leave', 'clock-in-out', 'apply-atro', 'apply-time-adjustments', 'payslip', 'employee-payslip-request', 'employee-messages',
                'apply-obs', 'apply-offset', 'employee-dtr', 'my-directory', 'my-team', 'employee-announcements', 'my-profile',
            ]
        ];
    
        if ($role->name === 'employee') {
            // Show only 'employee' permissions
            $this->permissions = [
                'employee' => [
                    'apply-leave', 'clock-in-out', 'remaining-credit', 'apply-atro', 'apply-time-adjustments', 'payslip', 'employee-messages',
                    'apply-obs', 'apply-offset', 'employee-dtr', 'my-directory', 'my-team', 'employee-announcements', 'my-profile',
                ]
            ];
        } else {
            // If not 'employee', remove 'employee' permissions
            unset($this->permissions['employee']);
        }
    
        // Initialize default values for selectedPermissions
        foreach ($this->permissions as $module => $actions) {
            foreach ($actions as $action) {
                // Apply the condition only for the 'employee' module
                if ($module === 'employee') {
                    // Check if the action is one of the specified ones
                    if (in_array($action, ['my-directory', 'my-team', 'employee-announcements', 'payslip'])) {
                        // Only set 'read' for these actions
                        $this->selectedPermissions["$module.$action"] = [
                            'read' => true,
                            'write' => 'disabled',
                        ];
                    } else {
                        // Set both 'read' and 'write' to false for other actions in the 'employee' module
                        $this->selectedPermissions["$module.$action"] = [
                            'read' => false,
                            'write' => false,
                        ];
                    }
                } else {
                    // For other modules, set both 'read' and 'write' to false (default behavior)
                    $this->selectedPermissions["$module.$action"] = [
                        'read' => false,
                        'write' => false,
                    ];
                }
            }
        }
    }
    
    

    public function loadSavedPermissions()
    {
        // Fetch the role and its permissions
        $role = Role::with('permissions')->find($this->roleId);

        if (!$role) {
            return;
        }

        // Iterate over the permissions of the role
        foreach ($role->permissions as $permission) {
            // Split the permission name into action and module (e.g., "read jobs" => ["read", "jobs"])
            $actionParts = explode(' ', $permission->name); // "read jobs" => ['read', 'jobs']

            if (count($actionParts) === 2) {
                [$action, $moduleAction] = $actionParts; // Destructure the parts

                // Generate the key for selectedPermissions like "recruitment.jobs"
                $permissionKey = "$permission->module_name.$moduleAction"; // e.g., "jobs.read"
                // Ensure the module-action exists in the selectedPermissions
                if (isset($this->selectedPermissions[$permissionKey])) {
                    // Set the corresponding action (read/write/delete) to true
                    $this->selectedPermissions[$permissionKey][$action] = true;
                }
            }
        }


    }

    public function updatePermissions($data)
    {
        // Extract necessary data from the input array
        $module = $data['module']; // Example: "recruitment"
        $action = $data['action']; // Example: "jobs"
        $permission = $data['permission']; // Example: "read" or "write"
        $value = $data['value']; // Example: true or false

        // Update the selected permissions array
        return $this->selectedPermissions[$module. '.' . $action][$permission] = $value;
    }


    public function savePermissions()
    {
        if (Gate::denies('write roles')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!', 
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        // Validate and prepare the permissions for saving
        $permissionsToSave = [];

        // Loop through the structured permissions array
        foreach ($this->selectedPermissions as $moduleAction => $permissions) {
            foreach ($permissions as $permission => $enabled) {
                // Only include enabled (true) permissions
                if ($enabled) {
                    // Split the module and action from the key (e.g., "recruitment.jobs")
                    $parts = explode('.', $moduleAction);

                    // Check if the key is properly formatted with two parts
                    if (count($parts) === 2) {
                        list($module, $action) = $parts;

                        // Exclude 'write' permission for specified actions
                        if (in_array($action, ['my-directory', 'my-team', 'employee-announcements', 'payslip']) && $permission === 'write') {
                            continue; // Skip the write permission for these actions
                        }

                        // Format the permission name
                        $permissionName = "$permission $action";

                        // Add the permission name to the array
                        $permissionsToSave[] = $permissionName;
                    }
                }
            }
        }

        try {
            // Fetch the role by ID
            $role = Role::findOrFail($this->roleId);

            // Sync only the permission names
            $role->syncPermissions($permissionsToSave);

            // Dispatch a success alert
            $this->dispatch('alert', [
                'status' => 'success',
                'title' => 'Success!',
                'showAlert' => true,
                'message' => 'Permissions have been saved.'
            ]);
        } catch (\Exception $e) {
            // Dispatch an error alert
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Oops!',
                'showAlert' => true,
                'message' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.roles.show');
    }
}
