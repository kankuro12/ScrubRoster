<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $alert->title }}</title>
</head>
<body style="margin: 0; padding: 24px; background: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <div style="max-width: 580px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(15,23,42,0.06);">
        <!-- Header -->
        <div style="background: #0f172a; color: #ffffff; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 16px; font-weight: 800; letter-spacing: -0.02em;">
                ScrubRoster <span style="color: #38bdf8; font-size: 12px; font-family: monospace;">· WORKSPACE ALERT</span>
            </div>
            <div style="font-family: monospace; font-size: 11px; color: #94a3b8; text-transform: uppercase;">
                {{ $tenant->name }}
            </div>
        </div>

        <!-- Alert Notification Box -->
        <div style="padding: 24px;">
            @php
                $badgeBg = match($alert->severity) {
                    'danger' => '#fee2e2',
                    'warning' => '#fef3c7',
                    'success' => '#dcfce7',
                    default => '#e0f2fe',
                };
                $badgeColor = match($alert->severity) {
                    'danger' => '#b91c1c',
                    'warning' => '#b45309',
                    'success' => '#15803d',
                    default => '#0369a1',
                };
            @endphp
            <div style="display: inline-block; background: {{ $badgeBg }}; color: {{ $badgeColor }}; font-family: monospace; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase; margin-bottom: 12px;">
                ● {{ strtoupper($alert->severity) }} NOTIFICATION
            </div>

            <h1 style="margin: 0 0 12px; font-size: 20px; color: #0f172a; font-weight: 800; line-height: 1.3;">
                {{ $alert->title }}
            </h1>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid {{ $badgeColor }}; padding: 16px; border-radius: 6px; font-size: 14px; line-height: 1.6; color: #334155; margin-bottom: 24px;">
                {!! nl2br(e($alert->message)) !!}
            </div>

            @if($alert->action_url)
                <div style="text-align: center; margin-bottom: 24px;">
                    <a href="{{ $alert->action_url }}" style="display: inline-block; background: #0284c7; color: #ffffff; text-decoration: none; padding: 12px 24px; font-size: 13px; font-weight: 700; border-radius: 6px; font-family: monospace; text-transform: uppercase; letter-spacing: 0.05em;">
                        {{ $alert->action_label ?: 'View In Workspace →' }}
                    </a>
                </div>
            @endif

            <p style="margin: 0; font-size: 12px; color: #94a3b8; text-align: center; line-height: 1.5;">
                This automated operational alert was generated for tenant administrators of <strong>{{ $tenant->name }}</strong>.
            </p>
        </div>
    </div>
</body>
</html>
