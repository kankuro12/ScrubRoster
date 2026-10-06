<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
</head>
<body style="margin: 0; padding: 24px; background: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <div style="max-width: 620px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(15,23,42,0.06);">
        <!-- Header -->
        <div style="background: #0f172a; color: #ffffff; padding: 24px 28px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="font-size: 20px; font-weight: 800; letter-spacing: -0.02em; color: #ffffff;">
                    {{ $tenant->name }}
                </div>
                <div style="font-size: 12px; font-family: monospace; color: #38bdf8; text-transform: uppercase;">
                    Commercial & Facility Operations
                </div>
            </div>
            <div style="text-align: right; font-family: monospace; font-size: 12px; color: #94a3b8;">
                INVOICE #{{ $invoice->invoice_number }}
            </div>
        </div>

        <!-- Body -->
        <div style="padding: 28px;">
            <div style="border-bottom: 2px solid #e2e8f0; padding-bottom: 18px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h1 style="margin: 0 0 6px; font-size: 22px; color: #0f172a; font-weight: 800;">
                            Invoice #{{ $invoice->invoice_number }}
                        </h1>
                        <p style="margin: 0; font-size: 13px; color: #64748b;">
                            Issued Date: <strong>{{ $invoice->invoice_date->format('M j, Y') }}</strong>
                            @if($invoice->due_date)
                                · Due Date: <strong>{{ $invoice->due_date->format('M j, Y') }}</strong>
                            @endif
                        </p>
                    </div>
                    <div>
                        @if($invoice->status === 'paid')
                            <span style="display: inline-block; background: #dcfce7; color: #15803d; font-family: monospace; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">
                                ✔ PAID
                            </span>
                        @else
                            <span style="display: inline-block; background: #fef3c7; color: #b45309; font-family: monospace; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">
                                {{ strtoupper($invoice->status) }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Billed To Metadata -->
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 24px;">
                <tr>
                    <td style="padding: 6px 0; color: #64748b; width: 30%; vertical-align: top;">Billed To:</td>
                    <td style="padding: 6px 0; color: #0f172a; vertical-align: top;">
                        <strong style="font-size: 14px;">{{ $invoice->recipient_name ?: $client->name }}</strong><br>
                        @if($client->company_name)
                            <span style="color: #64748b;">{{ $client->company_name }}</span><br>
                        @endif
                        @if($invoice->billing_address)
                            <span style="color: #64748b;">{!! nl2br(e($invoice->billing_address)) !!}</span><br>
                        @elseif($client->billing_address)
                            <span style="color: #64748b;">{!! nl2br(e($client->billing_address)) !!}</span><br>
                        @endif
                        <span style="font-family: monospace; color: #0284c7;">{{ $invoice->recipient_email ?: $client->email }}</span>
                    </td>
                </tr>
            </table>

            <!-- Line Items Table -->
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 24px; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left;">
                        <th style="padding: 10px 12px; font-family: monospace; font-size: 11px; text-transform: uppercase; color: #475569;">Description / Service</th>
                        <th style="padding: 10px 12px; font-family: monospace; font-size: 11px; text-transform: uppercase; color: #475569; text-align: center;">Qty</th>
                        <th style="padding: 10px 12px; font-family: monospace; font-size: 11px; text-transform: uppercase; color: #475569; text-align: right;">Rate</th>
                        <th style="padding: 10px 12px; font-family: monospace; font-size: 11px; text-transform: uppercase; color: #475569; text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px 12px;">
                                <strong>{{ $item->description }}</strong>
                                @if($item->property)
                                    <div style="font-size: 11px; color: #64748b; font-family: monospace;">Property: {{ $item->property->name }}</div>
                                @endif
                            </td>
                            <td style="padding: 10px 12px; text-align: center; font-family: monospace;">
                                {{ number_format($item->quantity, 2) }}
                            </td>
                            <td style="padding: 10px 12px; text-align: right; font-family: monospace;">
                                ${{ number_format($item->unit_price, 2) }}
                            </td>
                            <td style="padding: 10px 12px; text-align: right; font-family: monospace; font-weight: 600;">
                                ${{ number_format($item->total_price, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot style="background: #f8fafc; font-family: monospace;">
                    <tr>
                        <td colspan="3" style="padding: 8px 12px; text-align: right; color: #64748b;">Subtotal:</td>
                        <td style="padding: 8px 12px; text-align: right; font-weight: 600;">${{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    @if($invoice->discount_amount > 0)
                        <tr>
                            <td colspan="3" style="padding: 8px 12px; text-align: right; color: #16a34a;">Discount:</td>
                            <td style="padding: 8px 12px; text-align: right; color: #16a34a; font-weight: 600;">-${{ number_format($invoice->discount_amount, 2) }}</td>
                        </tr>
                    @endif
                    @if($invoice->tax_amount > 0)
                        <tr>
                            <td colspan="3" style="padding: 8px 12px; text-align: right; color: #64748b;">Tax ({{ $invoice->tax_rate }}%):</td>
                            <td style="padding: 8px 12px; text-align: right; font-weight: 600;">${{ number_format($invoice->tax_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr style="border-top: 2px solid #cbd5e1; font-size: 15px;">
                        <td colspan="3" style="padding: 10px 12px; text-align: right; font-weight: 800; color: #0f172a;">Total Due ({{ $invoice->currency }}):</td>
                        <td style="padding: 10px 12px; text-align: right; font-weight: 800; color: #0284c7;">${{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            @if($invoice->notes)
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #0284c7; padding: 12px 16px; border-radius: 4px; font-size: 13px; color: #475569; margin-bottom: 20px;">
                    <strong>Notes / Instructions:</strong><br>
                    {!! nl2br(e($invoice->notes)) !!}
                </div>
            @endif

            @if($invoice->terms)
                <div style="font-size: 12px; color: #64748b; margin-bottom: 24px;">
                    <strong>Terms & Conditions:</strong> {!! nl2br(e($invoice->terms)) !!}
                </div>
            @endif

            <div style="padding-top: 20px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 12px; color: #94a3b8; font-family: monospace;">
                Generated by {{ $tenant->name }} via ScrubRoster Field Operations System.
            </div>
        </div>
    </div>
</body>
</html>
