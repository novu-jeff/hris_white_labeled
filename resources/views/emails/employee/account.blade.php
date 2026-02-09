<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Account</title>
</head>
<body>

    @php
        $firstname = $data['firstname'] ?? trim(explode(' ', $data['fullname'] ?? '')[0] ?? '');
    @endphp
    <p>Hello {{ $firstname !== '' ? ucwords($firstname) . ',' : (ucwords($data['fullname'] ?? '') . ',') }}</p>

    @if(isset($data['is_newly_hired']) && $data['is_newly_hired'] == true)

        <p>We are pleased to inform you that you have been hired for the position of <strong>{{ ucwords($data['position']) }}</strong> at <strong>{{ ucwords($data['company_name']) }}</strong>, located in <strong>{{ ucwords($data['location']) }}</strong>.</p>

        <h4><b>Job Details:</b></h4>
        <ul>
            <li><strong>Company:</strong> {{ ucwords($data["company_name"]) }}</li>
            <li><strong>Location:</strong> {{ ucwords($data['location']) }}</li>
            <li><strong>Position:</strong> {{ ucwords($data["position"]) }}</li>
            <li><strong>Salary:</strong> {{ ucwords($data["salary"]) }}</li>
            <li><strong>Starting Date:</strong> {{ ucwords($data["starting_date"]) }}</li>
            <li><strong>Work Setup:</strong> {{ ucwords($data["setup"]) }}</li>
            <li><strong>Employment Type:</strong> {{ ucwords(str_replace("-", " ", $data["type"])) }}</li>
            <li><strong>Job Applied:</strong> <a href="{{ route('home.view-job', ['slug' => $data['slug']]) }}">{{ route('home.view-job', ['slug' => $data['slug']]) }}</a></li>
        </ul>
        <hr>

    @endif
    
    <h4>Your Employee Account Credentials:</h4>
    <ul>
        <li><strong>Login (Employee No):</strong> {{ $data['employee_no'] ?? 'TBF'  }}</li>
        <li><strong>Login (Email ID):</strong> {{$data['email']}}</li>
        <li><strong>Password:</strong> {{ $data['password'] }}</li>
    </ul>

    <p>We encourage you to change your password immediately after logging in.</p>

    @if(isset($data['is_newly_hired']) && $data['is_newly_hired'] == true)
        <p>Congratulations once again!</p>
    @endif

    <p>Best regards,<br>
    HR Department</p>

</body>
</html>
