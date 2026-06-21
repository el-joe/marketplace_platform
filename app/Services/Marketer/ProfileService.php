<?php

namespace App\Services\Marketer;

use App\Models\Marketer;
use Illuminate\Http\UploadedFile;

class ProfileService
{
    public function update(Marketer $marketer, array $data): Marketer
    {
        // Map social_links array to individual columns
        if (isset($data['social_links'])) {
            $links = $data['social_links'];
            $data['social_instagram'] = $links['instagram'] ?? $marketer->social_instagram;
            $data['social_tiktok']    = $links['tiktok']    ?? $marketer->social_tiktok;
            $data['social_youtube']   = $links['youtube']   ?? $marketer->social_youtube;
            $data['social_twitter']   = $links['twitter']   ?? $marketer->social_twitter;
            $data['social_facebook']  = $links['facebook']  ?? $marketer->social_facebook;
            unset($data['social_links']);
        }

        $marketer->update($data);

        return $marketer->fresh(['country', 'commissionTiers']);
    }

    public function updatePhoto(Marketer $marketer, UploadedFile $file, string $type): Marketer
    {
        if ($type === 'banner') {
            $path = $file->store('marketer-banners', 'public');
            $marketer->update(['profile_banner_path' => $path]);
        } else {
            $path = $file->store('marketer-photos', 'public');
            $marketer->update(['profile_photo_path' => $path]);
        }

        return $marketer->fresh();
    }

    public function updateBankAccount(Marketer $marketer, array $data): void
    {
        // Schema stores bank info as plain columns (no encryption cast in migration).
        // IBAN is never returned unmasked — masking happens in MarketerProfileResource.
        $marketer->update([
            'bank_account_name' => $data['account_holder_name'],
            'bank_name'         => $data['bank_name'],
            'bank_iban'         => $data['iban'],
        ]);
    }
}
