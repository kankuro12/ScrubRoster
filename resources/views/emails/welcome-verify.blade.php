<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Welcome to ScrubRoster</title>
</head>
<body style="margin: 0; padding: 24px; background: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <div style="max-width: 580px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(15,23,42,0.06);">
        <div style="background: #0f172a; color: #ffffff; padding: 18px 24px;">
            <div style="font-size: 16px; font-weight: 800; letter-spacing: -0.02em;">
                ScrubRoster <span style="color: #f97316; font-size: 12px; font-family: monospace;">· WELCOME ABOARD</span>
            </div>
        </div>

        <div style="padding: 24px;">
            <h1 style="margin: 0 0 12px; font-size: 20px; color: #0f172a; font-weight: 800; line-height: 1.3;">
                Welcome, {{ $user->name }}!
            </h1>

            <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.6; color: #334155;">
                Your workspace
                <strong>{{ $tenant?->name ?? 'ScrubRoster' }}</strong>
                has been created successfully.
                @if($tenant)
                    Your crew can sign in at
                    <code style="font-family: monospace; color: #0284c7;">/{{ $tenant->slug }}/tenant</code>.
                @endif
            </p>

            <p style="margin: 0 0 24px; font-size: 14px; line-height: 1.6; color: #334155;">
                One more step: please confirm this email address to secure your account.
                This link is valid for 30 days.
            </p>

            <div style="text-align: center; margin-bottom: 24px;">
                <a href="{{ $verificationUrl }}" style="display: inline-block; background: #ea580c; color: #0f172a; text-decoration: none; padding: 13px 26px; font-size: 13px; font-weight: 800; border-radius: 6px; font-family: monospace; text-transform: uppercase; letter-spacing: 0.05em;">
                    Verify Email Address →
                </a>
            </div>

            <p style="margin: 0 0 8px; font-size: 12px; color: #94a3b8; line-height: 1.5;">
                Button not working? Copy this link into your browser:
            </p>
            <p style="margin: 0 0 24px; font-size: 12px; color: #0284c7; line-height: 1.5; word-break: break-all;">
                {{ $verificationUrl }}
            </p>

            <p style="margin: 0; font-size: 12px; color: #94a3b8; text-align: center; line-height: 1.5;">
                If you did not create this workspace, you can safely ignore this email.
            </p>
        </div>
    </div>
</body>
</html>
