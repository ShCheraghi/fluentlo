<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            line-height: 1.6;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
        }

        .header h1 {
            color: white;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
        }

        .content {
            padding: 40px 30px;
        }

        .message {
            font-size: 16px;
            margin-bottom: 30px;
            color: #64748b;
        }

        .reset-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            text-decoration: none;
            padding: 16px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            transition: transform 0.2s;
        }

        .reset-button:hover {
            transform: translateY(-1px);
        }

        .security-info {
            background: #f1f5f9;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 30px 0;
            border-radius: 4px;
        }

        .security-info h3 {
            color: #1e293b;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .security-info p {
            color: #64748b;
            font-size: 14px;
        }

        .footer {
            background: #f8fafc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .footer p {
            color: #94a3b8;
            font-size: 14px;
        }

        .link-text {
            background: #f1f5f9;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            word-break: break-all;
            font-family: monospace;
            font-size: 13px;
            color: #475569;
        }

        @media (max-width: 600px) {
            .container {
                margin: 10px;
                border-radius: 8px;
            }

            .header, .content {
                padding: 30px 20px;
            }

            .header h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🔐 Reset Your Password</h1>
        <p>Secure password reset for your account</p>
    </div>

    <div class="content">
        <p class="message">
            Hello {{ $user->name }}! We received a request to reset the password for your account.
            If you made this request, click the button below to set a new password.
        </p>

        <div style="text-align: center;">
            <a href="{{ $resetUrl }}" class="reset-button">
                Reset Password
            </a>
        </div>

        <div class="security-info">
            <h3>🛡️ Security Information</h3>
            <p>
                This link will expire in <strong>{{ $expireMinutes }} minutes</strong> for your security.
                If you didn't request this password reset, you can safely ignore this email.
            </p>
        </div>

        <p style="color: #64748b; font-size: 14px; margin-top: 30px;">
            If you're having trouble clicking the button, copy and paste the URL below into your web browser:
        </p>

        <div class="link-text">
            {{ $resetUrl }}
        </div>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        <p style="margin-top: 8px;">This email was sent automatically. Please do not reply.</p>
    </div>
</div>
</body>
</html>
