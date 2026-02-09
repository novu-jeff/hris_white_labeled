<div class="card border-0 shadow">
    <div class="card-body p-4">
        <h5 class="card-title mb-2">Employee Modules</h5>
        <p class="text-muted small mb-3">
            Toggle modules per employment type. Disabled modules are hidden from the employee sidebar (and floating clock when applicable) and access is blocked. Clock In/Out is shown only as a floating button, not in the sidebar.
        </p>

        <div class="alert alert-info d-flex justify-content-between align-items-center mb-4">
            <div>
                <strong>Supervisor approvals</strong><br>
                <small>
                    When enabled, members' applications are approved by their assigned supervisor.
                    HR can still approve if no supervisor is configured for the employee; otherwise HR has view-only access, while superadmins can always approve.
                </small>
            </div>
            <div class="form-check form-switch ms-3">
                <input class="form-check-input" type="checkbox" role="switch"
                       id="supervisor_approval_toggle"
                       @checked($supervisorApprovalEnabled)
                       wire:click="toggleSupervisorApproval">
                <label class="form-check-label" for="supervisor_approval_toggle">
                    {{ $supervisorApprovalEnabled ? 'Enabled' : 'Disabled' }}
                </label>
            </div>
        </div>

        @foreach ($employmentTypes as $type)
            <div class="card border mb-4">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <strong>{{ $type->name }}</strong>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-success" wire:click="enableAllForType({{ $type->id }})">Enable all</button>
                        <button type="button" class="btn btn-outline-secondary" wire:click="disableAllForType({{ $type->id }})">Disable all</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Module</th>
                                    <th class="text-center" style="width: 120px;">Enabled</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($moduleKeys as $key)
                                    <tr>
                                        <td>{{ $moduleLabels[$key] ?? $key }}</td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                       id="mod_{{ $type->id }}_{{ $key }}"
                                                       @checked($settings[$type->id][$key] ?? true)
                                                       wire:click="toggle({{ $type->id }}, '{{ $key }}')">
                                                <label class="form-check-label" for="mod_{{ $type->id }}_{{ $key }}">
                                                    {{ ($settings[$type->id][$key] ?? true) ? 'Yes' : 'No' }}
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
