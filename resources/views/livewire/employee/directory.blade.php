<div>
    <div class="mb-3 d-flex justify-content-end gap-2">
        <button wire:click="setViewMode('list')" 
                wire:loading.attr="disabled"
                type="button"
                class="btn {{ $viewMode === 'list' ? 'btn-primary' : 'btn-outline-primary' }} text-uppercase">
            <i class="fa-solid fa-list me-2"></i>List View
        </button>
        <button wire:click="setViewMode('orgchart')" 
                wire:loading.attr="disabled"
                type="button"
                class="btn {{ $viewMode === 'orgchart' ? 'btn-primary' : 'btn-outline-primary' }} text-uppercase">
            <i class="fa-solid fa-sitemap me-2"></i>Organizational Chart
        </button>
    </div>
    
    <div wire:loading wire:target="setViewMode" class="text-center mb-3">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
    
    @if($viewMode === 'list')
    <div>
        <div class="accordion" id="accordionExample">
        @foreach ($records as $recordIndex => $record)
            @if (isset($record['branch_id']))
                {{-- Branch Listing --}}
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingBranch{{ $record['branch_id'] }}">
                        <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBranch{{ $record['branch_id'] }}" aria-expanded="{{ $recordIndex === 0 ? 'true' : 'false' }}" aria-controls="collapseBranch{{ $record['branch_id'] }}">
                            {{ $record['branch_name'] }}
                        </button>
                    </h2>
                    <div id="collapseBranch{{ $record['branch_id'] }}" class="accordion-collapse collapse {{ $recordIndex === 0 ? 'show' : '' }}" aria-labelledby="headingBranch{{ $record['branch_id'] }}" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            @foreach ($record['departments'] as $department)
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingDepartment{{ $department['department_id'] }}">
                                        <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDepartment{{ $department['department_id'] }}" aria-expanded="false" aria-controls="collapseDepartment{{ $department['department_id'] }}">
                                            {{ $department['department_name'] }}
                                        </button>
                                    </h2>
                                    <div id="collapseDepartment{{ $department['department_id'] }}" class="accordion-collapse collapse show" aria-labelledby="headingDepartment{{ $department['department_id'] }}" data-bs-parent="#collapseBranch{{ $record['branch_id'] }}">
                                        <div class="accordion-body">
                                            @foreach ($department['sections'] as $section)
                                                {{-- Section Listing --}}
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header" id="headingSection{{ $section['section_id'] }}">
                                                        <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSection{{ $section['section_id'] }}" aria-expanded="false" aria-controls="collapseSection{{ $section['section_id'] }}">
                                                            <div class="d-flex justify-content-between w-100 pe-4">
                                                                <div>
                                                                    {{ $section['section_name'] }} 
                                                                </div>
                                                                @php 
                                                                    $empCount = !empty($section['employees']) ? count($section['employees']) : 0;
                                                                @endphp
                                                                <div class="text-muted">
                                                                    {{ $empCount }} Employee{{ $empCount > 1 ? 's' : '' }}
                                                                </div>
                                                            </div>
                                                        </button>
                                                    </h2>
                                                    <div id="collapseSection{{ $section['section_id'] }}" class="accordion-collapse collapse {{ $recordIndex === 0 ? 'show' : '' }}" aria-labelledby="headingSection{{ $section['section_id'] }}" data-bs-parent="#collapseDepartment{{ $department['department_id'] }}">
                                                        <div class="accordion-body">
                                                            <div class="row">
                                                               @if (!empty($section['employees']))
                                                                    @foreach ($section['employees'] as $employee)
                                                                    {{-- Employee Card --}}
                                                                    <div class="col-12 col-md-6 mb-4">
                                                                        <div class="d-lg-flex align-items-center justify-content-center justify-content-lg-start gap-3">
                                                                            <div class="mb-3 mb-lg-0">
                                                                                @php
                                                                                    $fname = data_get($employee, 'personal.firstname', 'Unknown');
                                                                                    $lname = data_get($employee, 'personal.lastname', '');
                                                                                    $profile = data_get($employee, 'personal.profile');
                                                                                    $hasProfile = $profile && file_exists(public_path('storage/' . $profile));
                                                                                @endphp

                                                                                @if($hasProfile)
                                                                                    <img
                                                                                        src="{{ asset('storage/' . $profile) }}"
                                                                                        alt="Profile Photo"
                                                                                        style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%;"
                                                                                    >
                                                                                @else
                                                                                    <img
                                                                                        src="https://ui-avatars.com/api/?background=005668&color=ffffff&font-size=0.4&bold=true&name={{ urlencode($fname . ' ' . $lname) }}"
                                                                                        alt="Avatar"
                                                                                        style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%;"
                                                                                    >
                                                                                @endif
                                                                            </div>
                                                                            <ul class="list-unstyled mb-0">
                                                                                <li>Employee No: <strong>{{ $employee['employee_no'] }}</strong></li>
                                                                                <li>Full Name: <strong>{{ ucwords($fname . ' ' . $lname) }}</strong></li>
                                                                                <li>Position: <strong>{{ data_get($employee, 'positions.name', 'N/A') }}</strong></li>
                                                                                <li>Email: <strong>{{ data_get($employee, 'account.email', 'N/A') }}</strong></li>
                                                                            </ul>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @elseif (isset($record['group_name']))
                {{-- Unassigned Employees --}}
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingUnassigned">
                        <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUnassigned" aria-expanded="false" aria-controls="collapseUnassigned">
                            {{ $record['group_name'] }}
                        </button>
                    </h2>
                    <div id="collapseUnassigned" class="accordion-collapse collapse show" aria-labelledby="headingUnassigned" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <div class="row">
                                @foreach ($record['employees'] as $employee)
                                    {{-- Employee Card --}}
                                    <div class="col-12 col-md-6 mb-4">
                                        <div class="d-lg-flex align-items-center justify-content-center justify-content-lg-start gap-3">
                                            <div class="mb-3 mb-lg-0">
                                                @php
                                                    $fname = data_get($employee, 'personal.firstname', 'Unknown');
                                                    $lname = data_get($employee, 'personal.lastname', '');
                                                    $profile = data_get($employee, 'personal.profile');
                                                    $hasProfile = $profile && file_exists(public_path('storage/' . $profile));
                                                @endphp

                                                @if($hasProfile)
                                                    <img
                                                        src="{{ asset('storage/' . $profile) }}"
                                                        alt="Profile Photo"
                                                        style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%;"
                                                    >
                                                @else
                                                    <img
                                                        src="https://ui-avatars.com/api/?background=005668&color=ffffff&font-size=0.4&bold=true&name={{ urlencode($fname . ' ' . $lname) }}"
                                                        alt="Avatar"
                                                        style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%;"
                                                    >
                                                @endif
                                            </div>
                                            <ul class="list-unstyled mb-0">
                                                <li>Employee No: <strong>{{ $employee['employee_no'] }}</strong></li>
                                                <li>Full Name: <strong>{{ ucwords($fname . ' ' . $lname) }}</strong></li>
                                                <li>Position: <strong>{{ data_get($employee, 'positions.name', 'N/A') }}</strong></li>
                                                <li>Email: <strong>{{ data_get($employee, 'account.email', 'N/A') }}</strong></li>
                                            </ul>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
    </div>
    @else
    {{-- Organizational Chart: 1. CEO | 2. HRO, Dept Supervisors, Secretary | Under supervisors: members | Bottom: other --}}
    <div class="org-chart-container" style="overflow-x: auto; padding: 20px;">
        <style>
            .org-chart { display: flex; flex-direction: column; align-items: center; min-width: 100%; }
            .org-level { display: flex; justify-content: center; flex-wrap: wrap; gap: 20px; margin: 20px 0; width: 100%; }
            .org-level-2 { align-items: flex-start; }
            .org-node {
                background: #fff; border: 2px solid #005668; border-radius: 8px; padding: 15px;
                min-width: 180px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                transition: transform 0.2s;
            }
            .org-node:hover { transform: translateY(-5px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
            .org-node.ceo {
                background: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%);
                color: #000; font-weight: bold; font-size: 1.2em; padding: 25px; border: 3px solid #b8941a;
            }
            .org-node.hro {
                background: linear-gradient(135deg, #005668 0%, #007a8c 100%);
                color: white; font-weight: bold; padding: 20px;
            }
            .org-node.secretary {
                background: linear-gradient(135deg, #2c5f2d 0%, #3d8b40 100%);
                color: white; font-weight: bold; padding: 20px;
            }
            .org-node.supervisor {
                background: linear-gradient(135deg, #005668 0%, #007a8c 100%);
                color: white; font-weight: bold; font-size: 1.05em; padding: 20px;
            }
            .org-node.member { background: #e8f4f6; border-color: #005668; padding: 12px; }
            .org-node.other { background: #f8f9fa; border-color: #6c757d; padding: 12px; }
            .org-node.employee img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-bottom: 8px; }
            .org-connector.vertical { height: 20px; width: 2px; background: #005668; margin: 0 auto; }
            .org-employees { display: flex; flex-wrap: wrap; justify-content: center; gap: 15px; margin-top: 10px; }
            .org-employee-card { min-width: 160px; }
            .org-node-subtitle { font-size: 0.85em; opacity: 0.9; }
            .org-employee-name { font-weight: 600; margin: 5px 0; }
            .org-employee-details { font-size: 0.8em; color: #6c757d; margin-top: 5px; }
            .org-dept-block { display: flex; flex-direction: column; align-items: center; margin: 0 15px; }
        </style>

        {{-- 1. CEO --}}
        @if (!empty($orgChartData['ceo']))
            <div class="org-level" style="margin-bottom: 30px;">
                @foreach ($orgChartData['ceo'] as $employee)
                    @php
                        $fname = data_get($employee, 'personal.firstname', 'Unknown');
                        $lname = data_get($employee, 'personal.lastname', '');
                        $profile = data_get($employee, 'personal.profile');
                        $hasProfile = $profile && file_exists(public_path('storage/' . $profile));
                        $position = data_get($employee, 'positions.name', 'N/A');
                        $email = data_get($employee, 'account.email', 'N/A');
                    @endphp
                    <div class="org-node ceo org-employee-card">
                        @if($hasProfile)
                            <img src="{{ asset('storage/' . $profile) }}" alt="Profile" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 10px; border: 3px solid #b8941a;">
                        @else
                            <img src="https://ui-avatars.com/api/?background=d4af37&color=000000&font-size=0.4&bold=true&name={{ urlencode($fname . ' ' . $lname) }}" alt="Avatar" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 10px; border: 3px solid #b8941a;">
                        @endif
                        <div class="org-employee-name" style="font-size: 1.1em; margin: 10px 0;">{{ ucwords($fname . ' ' . $lname) }}</div>
                        <div class="org-node-subtitle" style="font-size: 0.9em; margin-bottom: 8px;">CEO</div>
                        <div class="org-employee-details" style="color: rgba(0,0,0,0.7);">
                            <div><strong>ID:</strong> {{ $employee['employee_no'] }}</div>
                            <div><strong>Position:</strong> {{ $position }}</div>
                            <div><strong>Email:</strong> {{ $email }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="org-connector vertical" style="height: 30px;"></div>
        @endif

        {{-- 2. HRO, Department Supervisors (with members), Secretary --}}
        @if (!empty($orgChartData['hro']) || !empty($orgChartData['dept_supervisors']) || !empty($orgChartData['secretary']))
            <div class="org-level org-level-2" style="margin: 30px 0;">
                {{-- HRO --}}
                @foreach ($orgChartData['hro'] ?? [] as $employee)
                    @php
                        $fname = data_get($employee, 'personal.firstname', 'Unknown');
                        $lname = data_get($employee, 'personal.lastname', '');
                        $profile = data_get($employee, 'personal.profile');
                        $hasProfile = $profile && file_exists(public_path('storage/' . $profile));
                        $position = data_get($employee, 'positions.name', 'N/A');
                        $email = data_get($employee, 'account.email', 'N/A');
                    @endphp
                    <div class="org-node hro org-employee-card">
                        @if($hasProfile)
                            <img src="{{ asset('storage/' . $profile) }}" alt="Profile" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-bottom: 8px;">
                        @else
                            <img src="https://ui-avatars.com/api/?background=005668&color=ffffff&font-size=0.4&bold=true&name={{ urlencode($fname . ' ' . $lname) }}" alt="Avatar" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-bottom: 8px;">
                        @endif
                        <div class="org-employee-name">{{ ucwords($fname . ' ' . $lname) }}</div>
                        <div class="org-node-subtitle" style="color: rgba(255,255,255,0.9);">HRO</div>
                        <div class="org-employee-details" style="color: rgba(255,255,255,0.85);">
                            <div><strong>ID:</strong> {{ $employee['employee_no'] }}</div>
                            <div><strong>Position:</strong> {{ $position }}</div>
                            <div><strong>Email:</strong> {{ $email }}</div>
                        </div>
                    </div>
                @endforeach

                {{-- Department Supervisors + members --}}
                @foreach ($orgChartData['dept_supervisors'] ?? [] as $group)
                    <div class="org-dept-block">
                        @if (!empty($group['supervisor']))
                            @php
                                $s = $group['supervisor'];
                                $fname = data_get($s, 'personal.firstname', 'Unknown');
                                $lname = data_get($s, 'personal.lastname', '');
                                $profile = data_get($s, 'personal.profile');
                                $hasProfile = $profile && file_exists(public_path('storage/' . $profile));
                                $position = data_get($s, 'positions.name', 'N/A');
                                $email = data_get($s, 'account.email', 'N/A');
                            @endphp
                            <div class="org-node supervisor org-employee-card">
                                @if($hasProfile)
                                    <img src="{{ asset('storage/' . $profile) }}" alt="Profile" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-bottom: 8px;">
                                @else
                                    <img src="https://ui-avatars.com/api/?background=005668&color=ffffff&font-size=0.4&bold=true&name={{ urlencode($fname . ' ' . $lname) }}" alt="Avatar" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-bottom: 8px;">
                                @endif
                                <div class="org-employee-name">{{ ucwords($fname . ' ' . $lname) }}</div>
                                <div class="org-node-subtitle" style="color: rgba(255,255,255,0.9);">Department Supervisor</div>
                                <div class="org-employee-details" style="color: rgba(255,255,255,0.85);">
                                    <div><strong>ID:</strong> {{ $s['employee_no'] }}</div>
                                    <div><strong>Position:</strong> {{ $position }}</div>
                                    <div><strong>Email:</strong> {{ $email }}</div>
                                </div>
                            </div>
                        @endif
                        @if (!empty($group['employees']))
                            <div class="org-connector vertical" style="height: 20px; margin-top: 10px;"></div>
                            <div class="org-employees" style="margin-top: 10px;">
                                @foreach ($group['employees'] as $employee)
                                    @php
                                        $ef = data_get($employee, 'personal.firstname', 'Unknown');
                                        $el = data_get($employee, 'personal.lastname', '');
                                        $ep = data_get($employee, 'personal.profile');
                                        $eHas = $ep && file_exists(public_path('storage/' . $ep));
                                        $epos = data_get($employee, 'positions.name', 'N/A');
                                        $em = data_get($employee, 'account.email', 'N/A');
                                    @endphp
                                    <div class="org-node member org-employee-card">
                                        @if($eHas)
                                            <img src="{{ asset('storage/' . $ep) }}" alt="Profile" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-bottom: 8px;">
                                        @else
                                            <img src="https://ui-avatars.com/api/?background=e8f4f6&color=005668&font-size=0.4&bold=true&name={{ urlencode($ef . ' ' . $el) }}" alt="Avatar" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-bottom: 8px;">
                                        @endif
                                        <div class="org-employee-name">{{ ucwords($ef . ' ' . $el) }}</div>
                                        <div class="org-employee-details">
                                            <div><strong>ID:</strong> {{ $employee['employee_no'] }}</div>
                                            <div><strong>Position:</strong> {{ $epos }}</div>
                                            <div><strong>Email:</strong> {{ $em }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach

                {{-- Secretary --}}
                @foreach ($orgChartData['secretary'] ?? [] as $employee)
                    @php
                        $fname = data_get($employee, 'personal.firstname', 'Unknown');
                        $lname = data_get($employee, 'personal.lastname', '');
                        $profile = data_get($employee, 'personal.profile');
                        $hasProfile = $profile && file_exists(public_path('storage/' . $profile));
                        $position = data_get($employee, 'positions.name', 'N/A');
                        $email = data_get($employee, 'account.email', 'N/A');
                    @endphp
                    <div class="org-node secretary org-employee-card">
                        @if($hasProfile)
                            <img src="{{ asset('storage/' . $profile) }}" alt="Profile" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-bottom: 8px;">
                        @else
                            <img src="https://ui-avatars.com/api/?background=2c5f2d&color=ffffff&font-size=0.4&bold=true&name={{ urlencode($fname . ' ' . $lname) }}" alt="Avatar" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-bottom: 8px;">
                        @endif
                        <div class="org-employee-name">{{ ucwords($fname . ' ' . $lname) }}</div>
                        <div class="org-node-subtitle" style="color: rgba(255,255,255,0.9);">Secretary</div>
                        <div class="org-employee-details" style="color: rgba(255,255,255,0.85);">
                            <div><strong>ID:</strong> {{ $employee['employee_no'] }}</div>
                            <div><strong>Position:</strong> {{ $position }}</div>
                            <div><strong>Email:</strong> {{ $email }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- 3. Other positions (bottom) --}}
        @if (!empty($orgChartData['other']))
            <div class="org-connector vertical" style="height: 30px; margin-top: 20px;"></div>
            <div class="org-level" style="margin-top: 20px; padding-top: 20px; border-top: 2px dashed #dee2e6;">
                <div style="width: 100%; text-align: center; margin-bottom: 15px; font-weight: bold; color: #6c757d; text-transform: uppercase;">Other Positions</div>
                <div class="org-employees" style="width: 100%; justify-content: center;">
                    @foreach ($orgChartData['other'] as $employee)
                        @php
                            $ef = data_get($employee, 'personal.firstname', 'Unknown');
                            $el = data_get($employee, 'personal.lastname', '');
                            $ep = data_get($employee, 'personal.profile');
                            $eHas = $ep && file_exists(public_path('storage/' . $ep));
                            $epos = data_get($employee, 'positions.name', 'N/A');
                            $em = data_get($employee, 'account.email', 'N/A');
                        @endphp
                        <div class="org-node other org-employee-card">
                            @if($eHas)
                                <img src="{{ asset('storage/' . $ep) }}" alt="Profile" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-bottom: 8px;">
                            @else
                                <img src="https://ui-avatars.com/api/?background=f8f9fa&color=6c757d&font-size=0.4&bold=true&name={{ urlencode($ef . ' ' . $el) }}" alt="Avatar" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-bottom: 8px;">
                            @endif
                            <div class="org-employee-name">{{ ucwords($ef . ' ' . $el) }}</div>
                            <div class="org-employee-details">
                                <div><strong>ID:</strong> {{ $employee['employee_no'] }}</div>
                                <div><strong>Position:</strong> {{ $epos }}</div>
                                <div><strong>Email:</strong> {{ $em }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    @endif
</div>
