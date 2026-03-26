<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BNPL Application Submitted</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <h2 style="color: #273e8e;">BNPL Application Submitted</h2>
    <p>Hello {{ trim(($user->first_name ?? '') . ' ' . ($user->sur_name ?? '')) ?: 'Customer' }},</p>

    <p>Your Buy Now Pay Later application has been submitted successfully.</p>

    <p>
        <strong>Application ID:</strong> #{{ $application->id }}<br>
        <strong>Status:</strong> {{ strtoupper($application->status ?? 'pending') }}
    </p>

    <p>Our team will review your application and share feedback within 24-48 hours.</p>

    <p>
        You can track your application here:<br>
        <a href="{{ $applicationUrl }}">{{ $applicationUrl }}</a>
    </p>

    <p>Thank you,<br>Troosolar Team</p>
</body>
</html>

