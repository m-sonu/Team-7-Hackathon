<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Reimbursement Submission</title>
</head>

<body style="margin: 0; padding: 20px; background-color: #ffffff; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333333;">
    <div style="max-width: 600px; margin: 0 auto; border: 1px solid #eeeeee; padding: 20px; border-radius: 8px;">

        <h2 style="color: #4f46e5; margin-top: 0;">New Reimbursement Submission for {{ $batch->category->name }} ({{ $batch->categoryMonthlyPivot->month_year }})</h2>
        <p>A new reimbursement claim has been submitted and requires your review.</p>

        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; margin-bottom: 25px;">
            <tr>
                <td style="padding: 20px;">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td width="33%" valign="top" style="padding-bottom: 12px;">
                                <span style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Submitted By</span><br>
                                <strong style="font-size: 14px;">{{ $requester->name }}</strong>
                            </td>
                            <td width="33%" valign="top" style="padding-bottom: 12px;">
                                <span style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Email</span><br>
                                <strong style="font-size: 14px;">{{ $requester->email }}</strong>
                            </td>
                            <td width="33%" valign="top" style="padding-bottom: 12px;">
                                <span style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Submission Date</span><br>
                                <strong style="font-size: 14px;">#{{ \Carbon\Carbon::parse($batch->updated_at)->format("Y-m-d") }}</strong>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <h4 style="color: #374151; margin-bottom: 10px;">Bill Summary</h4>

        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 25px; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #f3f4f6;">
                    <th style="padding: 10px 12px; text-align: left; font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb;">#</th>
                    <th style="padding: 10px 12px; text-align: left; font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb;">Vendor</th>
                    <th style="padding: 10px 12px; text-align: left; font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bills as $index => $bill)
                <tr style="background-color: #ffffff;">
                    <td style="padding: 12px; font-size: 13px; color: #374151; border-bottom: 1px solid #f3f4f6; vertical-align: top;">{{ $index + 1 }}</td>
                    <td style="padding: 12px; font-size: 13px; color: #374151; border-bottom: 1px solid #f3f4f6; vertical-align: top;">
                        @if($bill->vendorContact)
                            <strong>{{ $bill->vendorContact->company_name ?? 'Unknown/Not Visible' }}</strong>
                            @if($bill->vendorContact->phone)
                                <br><span style="color: #6b7280; font-size: 12px;">{{ $bill->vendorContact->phone }}</span>
                            @endif
                            @if($bill->vendorContact->email)
                                <br><span style="color: #6b7280; font-size: 12px;">{{ $bill->vendorContact->email }}</span>
                            @endif
                            @if($bill->vendorContact->website)
                                <br><span style="color: #6b7280; font-size: 12px;">{{ $bill->vendorContact->website }}</span>
                            @endif
                        @else
                            Unknown/Not Visible
                        @endif
                    </td>
                    <td style="padding: 12px; font-size: 13px; color: #374151; border-bottom: 1px solid #f3f4f6; vertical-align: top;">
                        {{ isset($bill->amount) ? number_format((float) $bill->amount, 2) : 'Unknown/Not Visible' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" style="padding: 20px; text-align: center; font-size: 13px; color: #9ca3af;">No bills found in this batch.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 40px; border-top: 1px solid #e5e7eb;">
            <tr>
                <td style="padding-top: 20px; text-align: center; font-size: 11px; color: #9ca3af;">
                    This is an automated notification. Please do not reply to this email.
                </td>
            </tr>
            <tr>
                <td style="padding-top: 8px; text-align: center; font-size: 11px; color: #9ca3af;">
                    {{ config('mail.footer') }}
                </td>
            </tr>
        </table>

    </div>
</body>

</html>
