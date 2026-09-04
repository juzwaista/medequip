<x-mail::message>
# Purchase Order: {{ $po->po_number }}

Dear {{ $po->supplier->contact_person ?? $po->supplier->name }},

Please find attached the official Purchase Order (**{{ $po->po_number }}**) from **{{ $distributorName }}**.

**Order Summary:**
- Total Amount: ₱{{ number_format($po->total_amount, 2) }}
- Expected Delivery: {{ $po->expected_delivery_date ? \Carbon\Carbon::parse($po->expected_delivery_date)->format('M d, Y') : 'TBD' }}

Please review the attached PDF for the full list of items and instructions.

If you have any questions, please reply directly to this email.

Thanks,<br>
{{ $distributorName }} via {{ config('app.name') }}
</x-mail::message>
