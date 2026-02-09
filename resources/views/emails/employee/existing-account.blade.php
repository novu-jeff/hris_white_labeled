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

    <h4>Your Employee Account Credentials:</h4>
    <ul>
        <li><strong>Login (Employee No):</strong> {{ $data['employee_no'] ?? 'TBF'  }}</li>
        <li><strong>Login (Email ID):</strong> {{$data['email_id']}}</li>
        <li><strong>Password:</strong> {{ $data['password'] }}</li>
    </ul>

    <p>We encourage you to change your password immediately after logging in.</p>

    <p>Best regards,<br>
    HR Department</p>

</body>
</html>
