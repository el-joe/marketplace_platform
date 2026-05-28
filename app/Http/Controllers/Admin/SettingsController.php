<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('settings.view'), 403);

        $settings = Setting::orderBy('category')->orderBy('key')->get()->groupBy('category');

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('settings.edit'), 403);

        $request->validate(['settings' => 'required|array']);

        return $this->persistSettings($request->input('settings'), $admin->id);
    }

    public function updateGroup(Request $request, string $category): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('settings.edit'), 403);

        $request->validate(['settings' => 'required|array']);

        $validKeys = Setting::where('category', $category)->pluck('key')->all();

        if (empty($validKeys)) {
            return response()->json(['message' => 'Unknown settings category.'], 422);
        }

        $filtered = array_filter(
            $request->input('settings', []),
            fn($key) => in_array($key, $validKeys, true),
            ARRAY_FILTER_USE_KEY
        );

        return $this->persistSettings($filtered, $admin->id, $category);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function persistSettings(array $incoming, string $adminId, ?string $category = null): JsonResponse
    {
        $keys = array_keys($incoming);
        $existing = Setting::whereIn('key', $keys)->get()->keyBy('key');

        $errors = [];

        foreach ($incoming as $key => $rawValue) {
            $setting = $existing->get($key);

            if (!$setting) {
                $errors[] = "Unknown key: {$key}";
                continue;
            }

            // Coerce to the original PHP type so JSON storage stays consistent
            $originalDecoded = json_decode($setting->value);
            $typed = match (true) {
                is_bool($originalDecoded) => filter_var($rawValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $rawValue,
                is_int($originalDecoded) => (int) $rawValue,
                is_float($originalDecoded) => (float) $rawValue,
                is_array($originalDecoded) || is_object($originalDecoded) => is_string($rawValue) ? json_decode($rawValue, true) ?? $rawValue : $rawValue,
                default => (string) ($rawValue ?? ''),
            };

            // Encrypted: store Laravel-encrypted string inside JSON
            $encoded = $setting->is_encrypted
                ? json_encode(encrypt($typed))
                : json_encode($typed);

            DB::table('settings')->where('key', $key)->update([
                'value' => $encoded,
                'updated_by_admin_id' => $adminId,
                'updated_at' => now(),
            ]);
        }

        Cache::forget('admin_settings');

        if (!empty($errors)) {
            return response()->json(['message' => 'Saved with warnings.', 'errors' => $errors], 207);
        }

        $label = $category ? ucfirst($category) . ' settings' : 'Settings';
        return response()->json(['message' => "{$label} saved successfully."]);
    }
}
