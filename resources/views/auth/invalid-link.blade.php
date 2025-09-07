<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invalid Link - {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .error-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .error-title {
            color: #1e293b;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .error-message {
            color: #64748b;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .info-box {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .info-box h4 {
            color: #92400e;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .info-box p {
            color: #b45309;
            font-size: 14px;
        }

        @media (max-width: 480px) {
            .card {
                padding: 30px 20px;
            }

            .error-title {
                font-size: 20px;
            }

            .error-icon {
                font-size: 48px;
            }
        }
    </style>
</head>
<body>
<div class="card">
    <div class="error-icon">❌</div>

    <h1 class="error-title">Invalid or Expired Link</h1>

    <p class="error-message">
        This password reset link is invalid or has expired. Please request a new password reset link.
    </p>

    <div class="info-box">
        <h4>📱 What to do next:</h4>
        <p>Go back to your mobile app and request a new password reset link from the login screen.</p>
    </div>

    <p style="color: #94a3b8; font-size: 14px;">
        Password reset links expire after 60 minutes for security reasons.
    </p>
</div>
</body>
</html>
