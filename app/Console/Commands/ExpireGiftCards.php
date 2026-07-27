<?php

namespace App\Console\Commands;

use App\Models\GiftCard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireGiftCards extends Command
{
    protected $signature = 'gift-cards:expire';

    protected $description = 'Mark all active gift cards past their expires_at as expired.';

    public function handle(): int
    {
        $expired = GiftCard::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$expired} gift cards.");

        Log::info("ExpireGiftCards: {$expired} cards expired.", ['timestamp' => now()]);

        return self::SUCCESS;
    }
}
