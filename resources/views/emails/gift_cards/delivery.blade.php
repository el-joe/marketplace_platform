<x-mail::message>
@if($batch?->image_url)
<img src="{{ $batch->image_url }}" alt="Noon Gift Card" style="max-width: 100%; border-radius: 8px;">
@endif

@if($isGift)
# You've received a gift card from {{ $buyerName }}!
@else
# Your gift card is ready!
@endif

Hi {{ $recipientName }},

@if($isGift)
{{ $buyerName }} sent you a Noon gift card. Here are your redemption details:
@else
Here are your redemption details:
@endif

@if($isGift && $giftMessage)
> {{ $giftMessage }}
@endif

**Code**

<div style="font-family: monospace; font-size: 24px; letter-spacing: 2px; font-weight: bold; margin: 12px 0;">
{{ $card->code }}
</div>

**PIN:** {{ $plainPin }}

**Amount:** {{ $purchase->currency_code }} {{ $purchase->amount_paid }}

**Expiry:** {{ $card->expires_at?->format('d M Y') }}

**How to use**

Go to profile → Wallet → Redeem → Gift Card tab → enter your code and PIN.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
