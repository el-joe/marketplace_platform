<?php

namespace App\Jobs;

use App\Mail\MarketerWelcomeMail;
use App\Models\Marketer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMarketerWelcomeMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly Marketer $marketer)
    {
    }

    public function handle(): void
    {
        Mail::to($this->marketer->email)->send(new MarketerWelcomeMail($this->marketer));
    }
}
