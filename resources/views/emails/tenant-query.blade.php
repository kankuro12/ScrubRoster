<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Query: {{ $query->title }}</title>
</head>
<body style="margin: 0; padding: 24px; background: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <div style="max-width: 580px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(15,23,42,0.06);">
        <!-- Header -->
        <div style="background: #0f172a; color: #ffffff; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 16px; font-weight: 800; letter-spacing: -0.02em;">
                ScrubRoster <span style="color: #38bdf8; font-size: 12px; font-family: monospace;">· INQUIRY & SUPPORT HUB</span>
            </div>
            <div style="font-family: monospace; font-size: 11px; color: #94a3b8; text-transform: uppercase;">
                #QUERY-{{ $query->id }}
            </div>
        </div>

        <div style="padding: 24px;">
            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                <span style="display: inline-block; background: #e0f2fe; color: #0369a1; font-family: monospace; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 4px; text-transform: uppercase;">
                    CATEGORY: {{ strtoupper(str_replace('_', ' ', $query->category)) }}
                </span>
                <span style="display: inline-block; background: #fef3c7; color: #b45309; font-family: monospace; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 4px; text-transform: uppercase;">
                    PRIORITY: {{ strtoupper($query->priority) }}
                </span>
            </div>

            <h1 style="margin: 0 0 8px; font-size: 20px; color: #0f172a; font-weight: 800;">
                {{ $query->title }}
            </h1>
            <p style="margin: 0 0 20px; font-size: 13px; color: #64748b;">
                Workspace: <strong>{{ $tenant->name }}</strong> (<code>/{{ $tenant->slug }}</code>)
            </p>

            @if($msg)
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #0284c7; padding: 16px; border-radius: 6px; font-size: 14px; line-height: 1.6; color: #334155; margin-bottom: 24px;">
                    <div style="font-size: 12px; font-family: monospace; color: #64748b; margin-bottom: 8px; font-weight: 600;">
                        {{ $msg->isFromPlatform() ? '★ PLATFORM ADMINISTRATOR' : '👤 TENANT ADMINISTRATOR' }}:
                    </div>
                    {!! nl2br(e($msg->message)) !!}
                </div>
            @endif

            <div style="text-align: center; margin-bottom: 24px;">
                @if($recipientType === 'tenant_admin')
                    <a href="{{ route('tenant.queries.show', $query) }}" style="display: inline-block; background: #0284c7; color: #ffffff; text-decoration: none; padding: 12px 24px; font-size: 13px; font-weight: 700; border-radius: 6px; font-family: monospace; text-transform: uppercase; letter-spacing: 0.05em;">
                        💬 Open Query & Submit Response →
                    </a>
                @else
                    <a href="{{ route('tenant-manager.index', ['tab' => 'queries', 'query_id' => $query->id]) }}" style="display: inline-block; background: #0284c7; color: #ffffff; text-decoration: none; padding: 12px 24px; font-size: 13px; font-weight: 700; border-radius: 6px; font-family: monospace; text-transform: uppercase; letter-spacing: 0.05em;">
                        ★ Review Response in Tenant Manager →
                    </a>
                @endif
            </div>

            <p style="margin: 0; font-size: 12px; color: #94a3b8; text-align: center; line-height: 1.5;">
                You can respond directly from the ScrubRoster operations portal.
            </p>
        </div>
    </div>
</body>
</html>
