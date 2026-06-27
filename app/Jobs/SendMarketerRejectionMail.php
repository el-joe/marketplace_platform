<?php

namespace App\Jobs;

use App\Mail\MarketerRejectionMail;
use App\Models\Marketer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMarketerRejectionMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Marketer $marketer,
        public readonly string $reason,
    ) {
    }

    public function handle(): void
    {
        Mail::to($this->marketer->email)->send(new MarketerRejectionMail($this->marketer, $this->reason));
    }
}
