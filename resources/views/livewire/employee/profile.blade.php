<div>
    <div class="card mb-4">
        <div class="card-body  px-5">
                <ul class="nav nav-pills mb-3 d-flex justify-content-center gap-3 py-4" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a href="{{ route('employee.profile', ['form' => 'personal']) }}" class="px-4 py-2 text-uppercase fw-bold nav-link {{$form == 'personal' ? 'active' : ''}}" id="pills-personal-tab" role="tab" aria-controls="pills-personal" aria-selected="{{$form == 'personal' ? 'true' : 'false'}}">I. Personal Information</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{ route('employee.profile', ['form' => 'family']) }}" class="px-4 py-2 text-uppercase fw-bold nav-link {{$form == 'family' ? 'active' : ''}}" id="pills-family-tab" role="tab" aria-controls="pills-family" aria-selected="{{$form == 'family' ? 'true' : 'false'}}">II. Family Background (A)</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{ route('employee.profile', ['form' => 'children']) }}" class="px-4 py-2 text-uppercase fw-bold nav-link {{$form == 'children' ? 'active' : ''}}" id="pills-children-tab" role="tab" aria-controls="pills-children" aria-selected="{{$form == 'children' ? 'true' : 'false'}}">II. Family Background (B)</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{ route('employee.profile', ['form' => 'education']) }}" class="px-4 py-2 text-uppercase fw-bold nav-link {{$form == 'education' ? 'active' : ''}}" id="pills-education-tab" role="tab" aria-controls="pills-education" aria-selected="{{$form == 'education' ? 'true' : 'false'}}">III. Educational Background</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{ route('employee.profile', ['form' => 'civil-service']) }}" class="px-4 py-2 text-uppercase fw-bold nav-link {{$form == 'civil-service' ? 'active' : ''}}" id="pills-civil-service-tab" role="tab" aria-controls="pills-civil-service" aria-selected="{{$form == 'civil-service' ? 'true' : 'false'}}">IV. Civil Service Eligibility</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{ route('employee.profile', ['form' => 'employment-history']) }}" class="px-4 py-2 text-uppercase fw-bold nav-link {{$form == 'employment-history' ? 'active' : ''}}" id="pills-employment-tab" role="tab" aria-controls="pills-history" aria-selected="{{$form == 'employment' ? 'true' : 'false'}}">V. Work Experience</a>
                </li>
                    <li class="nav-item" role="presentation">
                    <a href="{{ route('employee.profile', ['form' => 'other-works']) }}" class="px-4 py-2 text-uppercase fw-bold nav-link {{$form == 'other-works' ? 'active' : ''}}" id="pills-other-works-tab" role="tab" aria-controls="pills-others" aria-selected="{{$form == 'other-works' ? 'true' : 'false'}}">VI. Voluntary Work or Involvement in Civic / Non-Government / People / Voluntary Organizations</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{ route('employee.profile', ['form' => 'trainings']) }}" class="px-4 py-2 text-uppercase fw-bold nav-link {{$form == 'trainings' ? 'active' : ''}}" id="pills-trainings-tab" role="tab" aria-controls="pills-trainings" aria-selected="{{$form == 'trainings' ? 'true' : 'false'}}">VII. Learning and Development (L&D) Interventions / Training Programs Attended</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{ route('employee.profile', ['form' => 'skills']) }}" class="px-4 py-2 text-uppercase fw-bold nav-link {{$form == 'skills' ? 'active' : ''}}" id="pills-skills-tab" role="tab" aria-controls="pills-skills" aria-selected="{{$form == 'skills' ? 'true' : 'false'}}">VIII. Skills or Hobbies</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="{{ route('employee.profile', ['form' => 'security-notifications']) }}" class="px-4 py-2 text-uppercase fw-bold nav-link {{$form == 'security-notifications' ? 'active' : ''}}" id="pills-security-notifications-tab" role="tab" aria-controls="pills-security-notifications" aria-selected="{{$form == 'security-notifications' ? 'true' : 'false'}}">Security &amp; Notifications</a>
                </li>
            </ul>
            <div class="tab-content" id="pills-tabContent">
                @php
                    $viewForms = [
                        'personal' => 'employee.profile.personal',
                        'family' => 'employee.profile.family',
                        'children' => 'employee.profile.children',
                        'education' => 'employee.profile.education',
                        'employment-history' => 'employee.profile.employment',
                        'civil-service' => 'employee.profile.civil-service',
                        'trainings' => 'employee.profile.trainings',
                        'other-works' => 'employee.profile.other-works',
                        'skills' => 'employee.profile.skills',
                        'security-notifications' => 'employee.profile.security-notifications',
                    ];

                    $view = $viewForms[$form];
                @endphp
                <hr class="pt-2">
                <div class="mt-4">
                    @livewire($view)
                </div>
            </div>
        </div>
    </div>
</div>
