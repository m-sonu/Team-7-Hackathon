<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Reimbursement Report — {{ $monthYear }}</title>
</head>

<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1f2937; -webkit-font-smoothing: antialiased;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f3f4f6; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table width="640" cellpadding="0" cellspacing="0" border="0" style="max-width: 640px; width: 100%; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.08);">

                    {{-- Header bar --}}
                    <tr>
                        <td style="background-color: #312e81; padding: 28px 36px;">
                            <p style="margin: 0; font-size: 20px; font-weight: 700; color: #ffffff; letter-spacing: 0.3px;">{{ config('app.name') }}</p>
                        </td>
                    </tr>

                    {{-- Greeting & title --}}
                    <tr>
                        <td style="padding: 36px 36px 24px 36px;">
                            <p style="margin: 0 0 8px 0; font-size: 15px; color: #6b7280;">Hello, <strong style="color: #111827;">{{ $employee->name }}</strong></p>
                            <h1 style="margin: 0 0 6px 0; font-size: 24px; font-weight: 700; color: #111827; line-height: 1.3;">Reimbursement Report</h1>
                            <p style="margin: 0; font-size: 15px; color: #6b7280;">{{ $monthYear }}</p>
                        </td>
                    </tr>

                    {{-- Summary stats --}}
                    <tr>
                        <td style="padding: 0 36px 32px 36px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="32%" style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px 14px; text-align: center;">
                                        <p style="margin: 0 0 4px 0; font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.6px;">Total Bills</p>
                                        <p style="margin: 0; font-size: 22px; font-weight: 700; color: #111827;">{{ $bills->count() }}</p>
                                    </td>
                                    <td width="4%">&nbsp;</td>
                                    <td width="32%" style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px 14px; text-align: center;">
                                        <p style="margin: 0 0 4px 0; font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.6px;">Total Requested</p>
                                        <p style="margin: 0; font-size: 18px; font-weight: 700; color: #111827;">{{ $currency }} {{ number_format($totalRequestedAmount, 2) }}</p>
                                    </td>
                                    <td width="4%">&nbsp;</td>
                                    <td width="32%" style="background-color: #ecfdf5; border: 1px solid #d1fae5; border-radius: 8px; padding: 16px 14px; text-align: center;">
                                        <p style="margin: 0 0 4px 0; font-size: 11px; color: #065f46; text-transform: uppercase; letter-spacing: 0.6px;">Total Reimbursed</p>
                                        <p style="margin: 0; font-size: 18px; font-weight: 700; color: #065f46;">{{ $currency }} {{ number_format($totalReimbursedAmount, 2) }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Per-category sections --}}
                    @foreach($bills->groupBy(fn($bill) => $bill->category->name) as $categoryName => $categoryBills)
                    <tr>
                        <td style="padding: 0 36px 28px 36px;">

                            {{-- Category heading --}}
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 12px;">
                                <tr>
                                    <td style="border-left: 4px solid #312e81; padding-left: 12px;">
                                        <p style="margin: 0 0 2px 0; font-size: 16px; font-weight: 700; color: #111827;">{{ $categoryName }}</p>
                                        @if($categoryBills->first()?->category?->monthly_limit)
                                        <p style="margin: 0; font-size: 12px; color: #6b7280;">Monthly Limit: {{ $currency }} {{ number_format((float) $categoryBills->first()->category->monthly_limit, 2) }}</p>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            {{-- Bills table --}}
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border: 1px solid #e5e7eb; border-radius: 8px; border-collapse: collapse; overflow: hidden;">
                                <thead>
                                    <tr style="background-color: #f3f4f6;">
                                        <th style="padding: 10px 12px; text-align: left; font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb; white-space: nowrap;">#</th>
                                        <th style="padding: 10px 12px; text-align: left; font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb;">Vendor</th>
                                        <th style="padding: 10px 12px; text-align: left; font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb; white-space: nowrap;">Date</th>
                                        <th style="padding: 10px 12px; text-align: right; font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb; white-space: nowrap;">Requested</th>
                                        <th style="padding: 10px 12px; text-align: right; font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb; white-space: nowrap;">Reimbursed</th>
                                        <th style="padding: 10px 12px; text-align: center; font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categoryBills->sortBy('created_at') as $index => $bill)
                                    @php
                                        $isFullyReimbursed = (float) $bill->amount === (float) $bill->approve_amount;
                                        $rowBg = $isFullyReimbursed ? '#f0fdf4' : '#fffbeb';
                                    @endphp
                                    <tr style="background-color: {{ $rowBg }};">
                                        <td style="padding: 11px 12px; font-size: 13px; color: #374151; border-bottom: 1px solid #e5e7eb; vertical-align: top;">{{ $loop->iteration }}</td>
                                        <td style="padding: 11px 12px; font-size: 13px; color: #374151; border-bottom: 1px solid #e5e7eb; vertical-align: top;">
                                            {{ $bill->vendorContact?->company_name ?? 'Unknown Vendor' }}
                                        </td>
                                        <td style="padding: 11px 12px; font-size: 13px; color: #374151; border-bottom: 1px solid #e5e7eb; vertical-align: top; white-space: nowrap;">
                                            {{ $bill->created_at->format('d M Y') }}
                                        </td>
                                        <td style="padding: 11px 12px; font-size: 13px; color: #374151; border-bottom: 1px solid #e5e7eb; vertical-align: top; text-align: right; white-space: nowrap;">
                                            {{ $currency }} {{ number_format((float) $bill->amount, 2) }}
                                        </td>
                                        <td style="padding: 11px 12px; font-size: 13px; color: #374151; border-bottom: 1px solid #e5e7eb; vertical-align: top; text-align: right; white-space: nowrap;">
                                            {{ $currency }} {{ number_format((float) $bill->approve_amount, 2) }}
                                        </td>
                                        <td style="padding: 11px 12px; font-size: 13px; border-bottom: 1px solid #e5e7eb; vertical-align: top; text-align: center;">
                                            @if($isFullyReimbursed)
                                                <span style="display: inline-block; background-color: #16a34a; color: #ffffff; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 999px; white-space: nowrap;">Fully Reimbursed</span>
                                            @else
                                                <span style="display: inline-block; background-color: #d97706; color: #ffffff; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 999px; white-space: nowrap;">Adjusted</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach

                                    {{-- Category subtotal --}}
                                    <tr style="background-color: #f9fafb;">
                                        <td colspan="3" style="padding: 10px 12px; font-size: 12px; color: #6b7280; border-top: 2px solid #e5e7eb;">&nbsp;</td>
                                        <td colspan="3" style="padding: 10px 12px; font-size: 13px; font-weight: 600; color: #374151; border-top: 2px solid #e5e7eb; text-align: right; white-space: nowrap;">
                                            Category Total:&nbsp;
                                            <span style="color: #6b7280; font-weight: 400;">{{ $currency }} {{ number_format((float) $categoryBills->sum('amount'), 2) }}</span>
                                            &nbsp;/&nbsp;
                                            <span style="color: #065f46;">{{ $currency }} {{ number_format((float) $categoryBills->sum('approve_amount'), 2) }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                        </td>
                    </tr>
                    @endforeach

                    {{-- Grand total --}}
                    <tr>
                        <td style="padding: 0 36px 36px 36px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #312e81; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="font-size: 14px; font-weight: 700; color: #e0e7ff;">Grand Total</td>
                                                <td style="text-align: right;">
                                                    <span style="font-size: 13px; color: #a5b4fc;">Requested:&nbsp;</span>
                                                    <span style="font-size: 14px; font-weight: 700; color: #ffffff;">{{ $currency }} {{ number_format($totalRequestedAmount, 2) }}</span>
                                                    <span style="font-size: 13px; color: #a5b4fc;">&nbsp;&nbsp;Reimbursed:&nbsp;</span>
                                                    <span style="font-size: 14px; font-weight: 700; color: #6ee7b7;">{{ $currency }} {{ number_format($totalReimbursedAmount, 2) }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding: 0 36px 32px 36px; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 20px 0 0 0; font-size: 11px; color: #9ca3af; text-align: center; line-height: 1.6;">
                                This is an automated notification generated by {{ config('app.name') }}.<br>
                                Please do not reply to this email.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>

</html>
