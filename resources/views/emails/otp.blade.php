<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 30px;
            margin: 20px 0;
        }
        .otp-code {
            background-color: #fff;
            border: 2px solid #007bff;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
            letter-spacing: 8px;
            margin: 20px 0;
        }
        .message {
            color: #666;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #999;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Your OTP Code</h2>
        
        @if(isset($customMessage) && !empty($customMessage))
            <div class="message">
                {{ $customMessage }}
            </div>
        @else
            <p>Please use the following OTP code to complete your verification:</p>
        @endif
        
        <div class="otp-code">
            {{ $otpCode }}
        </div>
        
        <p style="color: #999; font-size: 12px;">
            This code will expire in 10 minutes. Please do not share this code with anyone.
        </p>
        
        <div class="footer">
            <p>This is an automated message from {{ config('app.name') }}. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
