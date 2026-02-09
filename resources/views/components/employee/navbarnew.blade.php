@php
    $employee = Auth::guard('employee')->user()->load(['personal', 'information.positions']);

    // Detect real profile photo
    $profilePhoto = null;
    if (!empty($employee->personal->profile)) {
        $storagePath = 'storage/' . $employee->personal->profile;
        if (file_exists(public_path($storagePath))) {
            $profilePhoto = asset($storagePath);
        }
    }

    // Fallback UI Avatar
    if (!$profilePhoto) {
        $profilePhoto = "https://ui-avatars.com/api/?background=005668&color=ffffff&font-size=0.4&bold=true&name="
                        . urlencode($employee->personal->firstname . ' ' . $employee->personal->lastname);
    }
@endphp

<nav class="navbar navbar-light bg-white shadow-sm fixed-top">
    <div class="container-fluid px-2 px-lg-4 d-flex align-items-center">

        <!-- LEFT: Sidebar toggle (visible on all viewports for collapse/expand) -->
        <button class="btn me-2" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="fa-solid fa-bars"></i>
        </button>

        <!-- RIGHT: Always visible -->
        <div id="navbarTopContent"
             class="ms-auto d-flex align-items-center gap-1 gap-lg-3 flex-nowrap">

            <!-- Notifications -->
            <div class="nav-item flex-shrink-0">
                @livewire('notifications')
            </div>

            <!-- Profile Dropdown -->
            @if($employee)
                <div class="nav-item dropdown flex-shrink-0" style="position: relative;">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-1 gap-lg-2"
                       href="#"
                       role="button"
                       data-bs-toggle="dropdown"
                       aria-expanded="false">

                        <!-- Avatar -->
                        <img src="{{ $profilePhoto }}"
                             class="rounded-circle border"
                             width="36"
                             height="36"
                             alt="Profile">

                        <!-- Name (always visible) -->
                        <span class="fw-bold text-uppercase "> 
                            {{ $employee->personal->firstname }} {{ $employee->personal->lastname }}
                        </span>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item"
                               href="{{ route('employee.profile', ['form' => 'personal']) }}">
                                <i class="fa-solid fa-user me-2"></i> My Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item"
                               href="{{ route('employee.profile', ['form' => 'security-notifications']) }}">
                                <i class="fa-solid fa-shield-halved me-2"></i> Security &amp; Notifications
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item"
                               href="{{ route('employee.logout') }}">
                                <i class="fa-solid fa-door-open me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            @endif

        </div>
    </div>
</nav>