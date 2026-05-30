<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingsService
{
    /**
     * Save a group of settings for a category.
     * Encrypted fields: if value === '●●●●●●●●', skip (keep current).
     * Returns count of settings updated.
     */
    public function saveGroup(string $category, array $data, Admin $admin): int
    {
        $count = 0;

        DB::transaction(function () use ($category, $data, $admin, &$count) {
            foreach ($data as $key => $value) {
                $setting = Setting::where('key', $key)
                    ->where('category', $category)
                    ->first();

                if (!$setting) {
                    continue; // don't create unknown keys
                }

                // Encrypted field: if placeholder sent, keep existing value
                if ($setting->is_encrypted) {
                    if ($value === '●●●●●●●●' || $value === '') {
                        continue;
                    }
                    // Encrypt the new plaintext value
                    $value = Crypt::encryptString((string) $value);
                } else {
                    // Coerce to the original PHP type stored in DB
                    $original = $setting->value;
                    $value = match (true) {
                        is_bool($original) => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value,
                        is_int($original) => (int) $value,
                        is_float($original) => (float) $value,
                        is_array($original) => is_string($value) ? (json_decode($value, true) ?? $value) : $value,
                        default => (string) ($value ?? ''),
                    };
                }

                // Using the JSON cast: assign decoded PHP value, Eloquent auto-encodes
                $setting->update([
                    'value' => $value,
                    'updated_by_admin_id' => $admin->id,
                    'updated_at' => now(),
                ]);

                Cache::forget('setting:' . $key);
                $count++;
            }

            Cache::forget('settings:' . $category);
        });

        Log::info("Settings [{$category}] saved by admin {$admin->id}: {$count} keys updated.");

        return $count;
    }

    /**
     * Return settings as key => display_value array for form pre-fill.
     * Encrypted fields return '●●●●●●●●'.
     */
    public function getGroupForForm(string $category): array
    {
        $settings = Setting::where('category', $category)->get();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->getDisplayValue();
        }

        return $result;
    }

    /**
     * Basic validation for a settings array.
     * Returns array of errors keyed by setting key.
     */
    public function validateGroup(string $category, array $data): array
    {
        $errors = [];

        foreach ($data as $key => $value) {
            $setting = Setting::where('key', $key)
                ->where('category', $category)
                ->first();

            if (!$setting || $setting->is_encrypted) {
                continue;
            }

            $original = $setting->value;

            if (is_bool($original)) {
                if (!in_array($value, ['0', '1', 0, 1, true, false], true)) {
                    $errors[$key] = 'Must be a boolean (0 or 1).';
                }
            } elseif (is_int($original) || is_float($original)) {
                if (!is_numeric($value)) {
                    $errors[$key] = 'Must be a numeric value.';
                }
            } elseif (str_contains($key, 'email')) {
                if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$key] = 'Must be a valid email address.';
                }
            } elseif (str_contains($key, 'url') || str_contains($key, 'host')) {
                if ($value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $errors[$key] = 'Must be a valid URL.';
                }
            } elseif (str_contains($key, 'color')) {
                if ($value !== '' && !preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
                    $errors[$key] = 'Must be a valid hex color (e.g. #0284c7).';
                }
            }
        }

        return $errors;
    }
}
