<x-mail::message>
# Hello {{ $userName }},

The status of your bill (ID: **#{{ $bill->id }}**) has been updated.

<x-mail::panel>
**New Status:** {{ $statusMessage }}
**Amount:** ${{ number_format($bill->amount, 2) }}
</x-mail::panel>

You can view the full details of your bill by clicking the button below:

<x-mail::button :url="$url" color="primary">
View Bill
</x-mail::button>

If you have any questions, feel free to reply to this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
