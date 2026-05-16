<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Processing Complete</title>
</head>

<body style="margin: 0; padding: 20px; background-color: #ffffff; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333333;">
    <div style="max-width: 600px; margin: 0 auto; border: 1px solid #eeeeee; padding: 20px; border-radius: 8px;">

        <h2 style="color: #4f46e5; margin-top: 0;">Batch Processing Complete</h2>
        <p>Hello <strong>{{ $batch->user->name }}</strong>,</p>
        <p>The AI analysis for your recent bill upload is finished. Here is the summary for <em>"{{ $batch->title }}"</em>.</p>

        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; margin-bottom: 25px;">
            <tr>
                <td style="padding: 20px;">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td width="33%" valign="top">
                                <span style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Category</span><br>
                                <strong style="font-size: 14px;">{{ $batch->category->name }}</strong>
                            </td>
                            <td width="33%" valign="top">
                                <span style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Currency</span><br>
                                <strong style="font-size: 14px;">{{ $batch->currency }}</strong>
                            </td>
                            <td width="33%" valign="top">
                                <span style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Submitted</span><br>
                                <strong style="font-size: 14px;">{{ $batch->created_at->format('M d, Y') }}</strong>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 25px;">
            <tr>
                <td width="48%" style="background-color: #ecfdf5; color: #065f46; border-radius: 8px; border: 1px solid #d1fae5; text-align: center; padding: 15px;">
                    <span style="font-size: 24px; font-weight: bold;">{{ count($validBills) }}</span><br>
                    <span style="font-size: 14px;">Valid Bills</span>
                </td>
                <td width="4%">&nbsp;</td>
                <td width="48%" style="background-color: #fef2f2; color: #991b1b; border-radius: 8px; border: 1px solid #fee2e2; text-align: center; padding: 15px;">
                    <span style="font-size: 24px; font-weight: bold;">{{ count($invalidBills) }}</span><br>
                    <span style="font-size: 14px;">Needs Attention</span>
                </td>
            </tr>
        </table>

        @if(count($validBills) > 0)
        <h4 style="color: #065f46; margin-bottom: 10px;">✅ Successfully Registered</h4>
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border: 1px solid #d1fae5; border-radius: 8px; margin-bottom: 25px;">
            @foreach($validBills as $bill)
            <tr>
                <td style="padding: 12px 15px; background-color: #ffffff; border-bottom: 1px solid #d1fae5; font-size: 13px;">
{{--                    <div style="float: right;">--}}
{{--                        <a href="{{ $bill->file_preview_url }}" style="color: #4f46e5; text-decoration: none; font-weight: bold;">View File</a>--}}
{{--                    </div>--}}
                    <div style="font-size: 13px; font-weight: bold; color: #374151;">{{ $bill->getFirstMedia('bills')?->name ?? 'Unknown' }}</div>
                    <strong>Bill #{{ $bill->bill_no }}</strong> &bull; {{ $batch->currency }} {{ number_format($bill->amount, 2) }}
                </td>
            </tr>
            @endforeach
        </table>
        @endif

        @if(count($invalidBills) > 0)
        <h4 style="color: #b91c1c; margin-bottom: 10px;">⚠️ Issues Identified</h4>
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border: 1px solid #fee2e2; border-radius: 8px; margin-bottom: 25px;">
            @foreach($invalidBills as $item)
            <tr>
                <td style="padding: 12px 15px; background-color: #ffffff; border-bottom: 1px solid #fee2e2;">
{{--                    <div style="float: right;">--}}
{{--                        <a href="{{ $item->file_preview_url }}" style="color: #dc2626; text-decoration: none; font-weight: bold; font-size: 12px;">View File</a>--}}
{{--                    </div>--}}
                    <div style="font-size: 13px; font-weight: bold; color: #374151;">{{ $item->getFirstMedia('bills')?->name ?? 'Unknown' }}</div>
                    <strong> {{ $batch->currency }} {{ number_format($item->amount, 2) }}</strong>
                    <div style="font-size: 12px; color: #dc2626; margin-top: 2px;">Reason: {{ $item->validation_error }}</div>
                </td>
            </tr>
            @endforeach
        </table>
        @endif

        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="text-align: center; margin-top: 30px;">
            <tr>
                <td>
                    <a href="{{ $previewUrl }}"
                        style="background-color: #4f46e5; color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
                        Preview and Finalize Submission
                    </a>
                </td>
            </tr>
            <tr>
                <td style="padding-top: 15px;">
                    <p style="font-size: 12px; color: #9ca3af; margin: 0;">
                        Note: You can manually fix or re-upload invalid bills on the preview page.
                    </p>
                </td>
            </tr>
        </table>

        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 40px; border-top: 1px solid #e5e7eb;">
            <tr>
                <td style="padding-top: 20px; text-align: center; font-size: 11px; color: #9ca3af;">
                    {{ config('mail.footer') }}
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
