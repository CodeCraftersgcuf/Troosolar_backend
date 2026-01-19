<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Code - Troosolar</title>
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
            border-radius: 10px;
            padding: 30px;
            margin: 20px 0;
        }
        .otp-code {
            background-color: #fff;
            border: 2px solid #4CAF50;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            font-size: 32px;
            font-weight: bold;
            color: #4CAF50;
            letter-spacing: 8px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Your OTP Code</h2>
        <p>Hello,</p>
        <p>You have requested an OTP code. Please use the following code to verify your email:</p>
        
        <div class="otp-code">
            {{ $otp }}
        </div>
        
        <p><strong>This code will expire in 10 minutes.</strong></p>
        
        <p>If you did not request this code, please ignore this email.</p>
        
        <div class="footer">
            <p>This is an automated message from Troosolar. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} Troosolar. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
