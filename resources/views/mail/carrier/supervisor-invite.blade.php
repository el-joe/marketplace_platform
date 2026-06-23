<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrier Portal Invitation</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #f9fafb; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #0f766e; padding: 32px 40px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; }
        .body { padding: 36px 40px; color: #374151; line-height: 1.7; }
        .cred-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 20px 24px; margin: 24px 0; }
        .cred-box p { margin: 4px 0; font-size: 15px; }
        .cred-box strong { color: #065f46; }
        .footer { padding: 20px 40px; background: #f3f4f6; color: #9ca3af; font-size: 12px; text-align: center; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>You've been invited to {{ $company->name }}</h1>
    </div>
    <div class="body">
        <p>Hi {{ $supervisor->name }},</p>
        <p>
            You have been added as a supervisor for <strong>{{ $company->name }}</strong>
            on the carrier portal. Use the credentials below to log in.
        </p>
        <div class="cred-box">
            <p><strong>Email:</strong> {{ $supervisor->email }}</p>
            <p><strong>Temporary Password:</strong> {{ $temporaryPassword }}</p>
        </div>
        <p>Please change your password after your first login.</p>
        <p>If you did not expect this invitation, please ignore this email or contact platform support.</p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} Platform. All rights reserved.
    </div>
</div>
</body>
</html>
