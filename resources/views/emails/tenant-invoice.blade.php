<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice['invoice_number'] }}</title>
</head>
<body style="margin: 0; padding: 24px; background: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(15,23,42,0.06);">
        <!-- Header -->
        <div style="background: #0f172a; color: #ffffff; padding: 20px 28px; display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 18px; font-weight: 800; letter-spacing: -0.02em;">
                ScrubRoster <span style="color: #38bdf8; font-size: 13px; font-weight: 500; font-family: monospace;">· DISPATCH OPS</span>
            </div>
            <div style="font-family: monospace; font-size: 12px; color: #94a3b8; text-transform: uppercase;">
                Official Invoice
            </div>
        </div>

        <!-- Body -->
        <div style="padding: 28px;">
            <div style="border-bottom: 2px solid #e2e8f0; padding-bottom: 18px; margin-bottom: 20px;">
                <span style="display: inline-block; background: #dcfce7; color: #15803d; font-family: monospace; font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; margin-bottom: 8px;">
                    ✔ Payment Received & Confirmed
                </span>
                <h1 style="margin: 8px 0 4px; font-size: 22px; color: #0f172a; font-weight: 800;">
                    Invoice #{{ $invoice['invoice_number'] }}
                </h1>
                <p style="margin: 0; font-size: 13px; color: #64748b;">
                    Issued to <strong>{{ $tenant->name }}</strong> on {{ $invoice['date'] }}
                </p>
            </div>

            <!-- Details Grid -->
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 24px;">
                <tr>
                    <td style="padding: 6px 0; color: #64748b; width: 35%;">Organization:</td>
                    <td style="padding: 6px 0; font-weight: 600; color: #0f172a;">{{ $tenant->name }}</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #64748b;">Workspace Prefix:</td>
                    <td style="padding: 6px 0; font-family: monospace; color: #0284c7;">/{{ $tenant->slug }}</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #64748b;">Billing Interval:</td>
                    <td style="padding: 6px 0; text-transform: uppercase; font-family: monospace; font-weight: 600;">{{ $payment->billing_cycle }}</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #64748b;">Payment Method:</td>
                    <td style="padding: 6px 0; font-family: monospace;">{{ strtoupper($payment->payment_gateway) }} (Ref: {{ substr($payment->gateway_transaction_id, 0, 16) }}...)</td>
                </tr>
            </table>

            <!-- Line Items Table -->
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 24px; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left;">
                        <th style="padding: 10px 12px; font-family: monospace; font-size: 11px; text-transform: uppercase; color: #475569;">Description</th>
                        <th style="padding: 10px 12px; font-family: monospace; font-size: 11px; text-transform: uppercase; color: #475569; text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px;">
                            <strong>{{ $invoice['package_name'] }}</strong><br>
                            <span style="font-size: 12px; color: #64748b;">Up to {{ $invoice['max_employees'] ?: 'Unlimited' }} Active Cleaners with Cloud Sync</span>
                        </td>
                        <td style="padding: 12px; text-align: right; font-family: monospace; font-weight: 600;">
                            ${{ number_format($invoice['base_price'], 2) }}
                        </td>
                    </tr>
                    @if($invoice['discount'] > 0)
                        <tr style="border-bottom: 1px solid #f1f5f9; background: #f0fdf4;">
                            <td style="padding: 10px 12px; color: #166534;">
                                Coupon Discount (<code>{{ $invoice['coupon_code'] }}</code>)
                            </td>
                            <td style="padding: 10px 12px; text-align: right; color: #166534; font-family: monospace; font-weight: 600;">
                                -${{ number_format($invoice['discount'], 2) }}
                            </td>
                        </tr>
                    @endif
                    <tr style="background: #f8fafc;">
                        <td style="padding: 12px; font-weight: 800; font-size: 14px; color: #0f172a;">
                            Total Paid (USD):
                        </td>
                        <td style="padding: 12px; text-align: right; font-family: monospace; font-weight: 800; font-size: 16px; color: #0f172a;">
                            ${{ number_format($invoice['total_amount'], 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Actions -->
            <div style="text-align: center; margin: 28px 0 16px;">
                <a href="{{ route('billing.invoices.show', $payment) }}" style="display: inline-block; background: #0284c7; color: #ffffff; text-decoration: none; padding: 12px 24px; font-size: 13px; font-weight: 700; border-radius: 6px; font-family: monospace; text-transform: uppercase; letter-spacing: 0.05em;">
                    📄 View & Print Official Invoice
                </a>
            </div>

            <p style="margin: 0; font-size: 12px; color: #94a3b8; text-align: center; line-height: 1.5;">
                Thank you for choosing ScrubRoster. For billing questions or inquiries, reply to this message or visit your workspace support hub.
            </p>
        </div>
    </div>
</body>
</html>
