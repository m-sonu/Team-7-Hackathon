<x-mail::message>
# Claimable Amount Report

Hello {{ $user->name }},

Here is your claimable amount report for **{{ date("F", mktime(0, 0, 0, $month, 10)) }} {{ $year }}**.

### Breakdown per Bill:

@forelse($bills as $bill)
* **Bill #{{ $bill->id }}** @if($bill->bill_no) ({{ $bill->bill_no }}) @endif - *{{ $bill->created_at->format('M d, Y') }}* - **Rs {{ number_format($bill->amount, 2) }}**
@empty
*No claimable bills found for this month.*
@endforelse

---

### **Total Claimable Amount: Rs {{ number_format($totalAmount, 2) }}**

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
