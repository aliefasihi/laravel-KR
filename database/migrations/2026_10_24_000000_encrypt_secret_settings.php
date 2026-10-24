<?php

/**
 * Khaneh Romantic — encrypt secrets already saved in plain text in `settings`
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * API keys, bot tokens and passwords saved before encryption existed. Safe to run again:
 * values that are already encrypted are left alone.
 */

use App\Core\Settings\Settings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('settings')->pluck('value', 'key') as $key => $json) {
            $value = json_decode((string) $json, true);
            $sealed = Settings::seal($key, $value);
            if ($sealed !== $value) {
                DB::table('settings')->where('key', $key)->update(['value' => json_encode($sealed, JSON_UNESCAPED_UNICODE)]);
            }
        }

        Cache::forget(Settings::CACHE_KEY);
    }

    public function down(): void
    {
        // left encrypted: the app reads both forms
    }
};
