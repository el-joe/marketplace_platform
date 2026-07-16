<x-mail::message>
# You've received a gift card!

{{ $giftCard->recipient_name }}, someone sent you a noon gift card worth
{{ number_format($giftCard->denomination / 100, 2) }} {{ $giftCard->currency }}.

@if($giftCard->personal_message)
> {{ $giftCard->personal_message }}
@endif

Your gift card code:

**{{ $giftCard->code }}**

Use it at checkout to redeem your balance.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
