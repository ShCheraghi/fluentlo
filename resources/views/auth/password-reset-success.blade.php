<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Successful - {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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

        .success-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .success-title {
            color: #1e293b;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .success-message {
            color: #64748b;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .info-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .info-box h4 {
            color: #0c4a6e;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .info-box p {
            color: #0369a1;
            font-size: 14px;
        }

        @media (max-width: 480px) {
            .card {
                padding: 30px 20px;
            }

            .success-title {
                font-size: 20px;
            }

            .success-icon {
                font-size: 48px;
            }
        }
    </style>
</head>
<body>
<div class="card">
    <div class="success-icon">✅</div>

    <h1 class="success-title">Password Reset Successful!</h1>

    <p class="success-message">
        Your password has been successfully reset. You can now login to your mobile app using your new password.
    </p>

    <div class="info-box">
        <h4>📱 Next Steps:</h4>
        <p>Go back to your mobile app and login with your new password. All your previous login sessions have been
            terminated for security.</p>
    </div>

    <p style="color: #94a3b8; font-size: 14px;">
        You can safely close this browser window.
    </p>
</div>
</body>
</html>
