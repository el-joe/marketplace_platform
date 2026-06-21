<?php

namespace App\Policies;

use App\Models\Marketer;
use App\Models\MarketerSampleRequest;

class SampleRequestPolicy
{
    public function view(Marketer $marketer, MarketerSampleRequest $request): bool
    {
        return $request->marketer_id === $marketer->id;
    }
}
