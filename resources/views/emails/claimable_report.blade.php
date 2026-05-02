<x-mail::message>
# Claimable Amount Report

Hello {{ $user->name }},

Here is your claimable amount report for **{{ date("F", mktime(0, 0, 0, $month, 10)) }} {{ $year }}**.

### Breakdown per Bill:

@forelse($bills as $bill)
**Bill #{{ $bill->id }}** @if($bill->bill_number) ({{ $bill->bill_number }}) @endif - *{{ $bill->created_at->format('M d, Y') }}*
<x-mail::table>
| Item | Price |
| :--- | :--- |
@foreach($bill->items as $item)
| {{ $item->name ?? 'Item #' . $item->id }} | Rs {{ number_format($item->price, 2) }} |
@endforeach
</x-mail::table>
@empty
*No claimable bills found for this month.*
@endforelse

---

### **Total Claimable Amount: Rs {{ number_format($totalAmount, 2) }}**

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
