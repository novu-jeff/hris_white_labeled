<?php

namespace App\Livewire\Admin\Settings;

use App\Models\EmployeeModuleSetting;
use App\Models\EmployementTypes;
use App\Models\Setting;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EmployeeModules extends Component
{
    /** @var array<int, array<string, bool>> [employment_type_id => [module_key => enabled]] */
    public array $settings = [];

    /** Global toggle for supervisor-based approvals. */
    public bool $supervisorApprovalEnabled = false;

    public function mount(): void
    {
        $user = Auth::user();
        if (!(method_exists($user, 'hasRole') && $user->hasRole('superadmin'))) {
            abort(403, 'Only Superadmins can manage employee modules.');
        }

        $this->supervisorApprovalEnabled = Setting::getBool('ess.supervisor_approval_enabled', false);
        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        $types = EmployementTypes::orderBy('name')->get();
        $keys = SettingsSeeder::moduleKeys();
        foreach ($types as $type) {
            foreach ($keys as $key) {
                $row = EmployeeModuleSetting::where('employment_type_id', $type->id)
                    ->where('module_key', $key)
                    ->first();
                if (!$row) {
                    EmployeeModuleSetting::setEnabled($type->id, $key, true);
                    $this->settings[$type->id][$key] = true;
                } else {
                    $this->settings[$type->id][$key] = $row->enabled;
                }
            }
        }
    }

    public function toggle(int $employmentTypeId, string $key): void
    {
        $current = $this->settings[$employmentTypeId][$key] ?? true;
        $next = !$current;
        $this->settings[$employmentTypeId][$key] = $next;
        EmployeeModuleSetting::setEnabled($employmentTypeId, $key, $next);
        $this->dispatch('alert', [
            'status' => 'success',
            'title' => 'Updated',
            'showAlert' => true,
            'message' => 'Module setting saved.',
        ]);
    }

    public function enableAllForType(int $employmentTypeId): void
    {
        foreach (SettingsSeeder::moduleKeys() as $key) {
            $this->settings[$employmentTypeId][$key] = true;
            EmployeeModuleSetting::setEnabled($employmentTypeId, $key, true);
        }
        $this->dispatch('alert', [
            'status' => 'success',
            'title' => 'Updated',
            'showAlert' => true,
            'message' => 'All modules enabled for this employment type.',
        ]);
    }

    public function disableAllForType(int $employmentTypeId): void
    {
        foreach (SettingsSeeder::moduleKeys() as $key) {
            $this->settings[$employmentTypeId][$key] = false;
            EmployeeModuleSetting::setEnabled($employmentTypeId, $key, false);
        }
        $this->dispatch('alert', [
            'status' => 'success',
            'title' => 'Updated',
            'showAlert' => true,
            'message' => 'All modules disabled for this employment type.',
        ]);
    }

    /**
     * Toggle the global setting that enables supervisor-based approvals for ESS applications.
     */
    public function toggleSupervisorApproval(): void
    {
        $next = !$this->supervisorApprovalEnabled;
        $this->supervisorApprovalEnabled = $next;
        Setting::set('ess.supervisor_approval_enabled', $next ? '1' : '0');

        $this->dispatch('alert', [
            'status' => 'success',
            'title' => 'Updated',
            'showAlert' => true,
            'message' => 'Supervisor approval setting updated.',
        ]);
    }

    public static function moduleLabels(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'announcements' => 'Announcements',
            'dtr' => 'Daily Time Record',
            'time_adjustments' => 'Time Adjustments',
            'payslip' => 'Payslip',
            'leave' => 'Leave Application',
            'atro' => 'Overtime Application',
            'obs' => 'Official Business',
            'offset' => 'Offset Application',
            'team' => 'My Team',
            'messages' => 'Contact HR',
            'directory' => 'Directory',
            'tutorial' => 'Tutorials',
            'profile' => 'My Profile',
            'security_notifications' => 'Security & Notifications',
            'clock' => 'Clock In / Out (floating only)',
        ];
    }

    public function render()
    {
        $employmentTypes = EmployementTypes::orderBy('name')->get();
        $moduleKeys = SettingsSeeder::moduleKeys();
        $moduleLabels = self::moduleLabels();
        return view('livewire.admin.settings.employee-modules', [
            'employmentTypes' => $employmentTypes,
            'moduleKeys' => $moduleKeys,
            'moduleLabels' => $moduleLabels,
            'supervisorApprovalEnabled' => $this->supervisorApprovalEnabled,
        ]);
    }
}
