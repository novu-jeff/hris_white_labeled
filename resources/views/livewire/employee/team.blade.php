<div>
    @foreach ($records as $section)
        <div class="text-uppercase mb-3">
            <h5 class="mb-2 fw-bold">Department: <span class="ms-1 text-decoration-underline">{{ $section['department_name'] }}</span></h5>
            <h5 class="mb-2 fw-bold">Supervisor: <span class="ms-1 text-decoration-underline">{{ $section['supervisor_name'] }}</span></h5>
            <hr class="mt-3 mb-3">

            @foreach ($section['positions'] as $position)
                <div class="mt-4 mb-2">
                    <div class="d-flex justify-content-between">
                        <h5 class="text-uppercase fw-bold">Position: {{ $position['position_name'] }}</h5>
                        <h5 class="text-uppercase text-muted fw-bold">
                            ({{ count($position['employees']) }} Employee{{ count($position['employees']) > 1 ? 's' : '' }})
                        </h5>
                    </div>

                    <div class="row">
                        @foreach ($position['employees'] as $employee)
                            @php
                                $personal = $employee['personal'] ?? null;
                                $profile = $personal['profile'] ?? null;
                                $fullname = $personal ? ($personal['firstname'] . ' ' . $personal['lastname']) : 'No Name';
                            @endphp

                            <div class="col-12 col-md-6 mb-4">
                                <div class="d-md-flex align-items-center gap-3">
                                    <div>
                                        @if($profile && file_exists(public_path('storage/' . $profile)))
                                            <img src="{{ asset('storage/' . $profile) }}" 
                                                 alt="Profile Photo" 
                                                 style="width: 60px; height: 100px; object-fit: cover; border-radius: 5px;">
                                        @else
                                            <img src="https://ui-avatars.com/api/?background=005668&color=ffffff&font-size=0.4&bold=true&name={{ urlencode($fullname) }}" 
                                                 alt="Avatar" 
                                                 style="width: 60px; height: 100px; object-fit: cover; border-radius: 5px;">
                                        @endif
                                    </div>
                                    <ul class="list-unstyled mb-0 fs-6">
                                        <li>Full Name: <strong>{{ ucwords($fullname) }}</strong></li>
                                        <li>Email: <strong>{{ $employee['account']['email'] ?? $employee['account']['email_id'] ?? 'No Email' }}</strong></li>
                                    </ul>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</div>
