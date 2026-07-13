<?php

namespace App\Jobs;

use App\Models\Marketer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyAdminNewMarketerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly Marketer $marketer) {}

    public function handle(): void
    {
        // Notify admin users that a new marketer registration is pending review.
        // Wire to mail/Slack/notification channel of your choice.
        Log::info('New marketer registration pending review', [
            'marketer_id' => $this->marketer->id,
            'name'        => $this->marketer->name,
            'email'       => $this->marketer->email,
            'type'        => $this->marketer->type?->value,
        ]);
    }
}
