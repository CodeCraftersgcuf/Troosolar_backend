<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f6fb;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
                <tr>
                    <td style="background:#273e8e;padding:18px 24px;color:#ffffff;font-size:20px;font-weight:700;">
                        Troosolar
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;">
                        <h1 style="margin:0 0 14px;font-size:22px;line-height:1.3;color:#111827;">{{ $headingText }}</h1>
                        <p style="margin:0 0 10px;font-size:15px;line-height:1.6;">Hello {{ trim(($user->first_name ?? '').' '.($user->sur_name ?? '')) ?: 'Customer' }},</p>
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.6;">{{ $bodyText }}</p>

                        <div style="margin:18px 0;padding:14px;border:1px solid #e5e7eb;border-radius:8px;background:#fafafa;">
                            <p style="margin:0 0 8px;font-size:14px;"><strong>Request ID:</strong> #{{ $auditRequest->id }}</p>
                            <p style="margin:0 0 8px;font-size:14px;"><strong>Status:</strong> {{ ucfirst($status) }}</p>
                            <p style="margin:0;font-size:14px;"><strong>Audit Type:</strong> {{ $auditRequest->audit_type }}</p>
                        </div>

                        <p style="margin:0;font-size:14px;line-height:1.6;color:#6b7280;">
                            If you have any questions, please reply to this email and our team will assist you.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
