<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BotAccessSettings
{
    private const CACHE_KEY = 'bot:access_settings';

    public function get(): array
    {
        return Cache::remember(self::CACHE_KEY, 30, function () {
            $row = DB::table('bot_access_settings')->where('id', 1)->first();

            return [
                'mode' => $row->mode ?? 'public',
                'restricted_until' => $row->restricted_until ?? null,
                'timezone' => $row->timezone ?? config('app.timezone'),
            ];
        });
    }

    public function isRestricted(?array $settings = null): bool
    {
        $settings ??= $this->get();

        return $settings['mode'] === 'staff_only'
            && $settings['restricted_until'] !== null
            && CarbonImmutable::parse($settings['restricted_until'], 'UTC')->isFuture();
    }

    public function save(string $mode, ?CarbonImmutable $until, string $timezone): void
    {
        DB::table('bot_access_settings')->updateOrInsert(['id' => 1], [
            'mode' => $mode,
            'restricted_until' => $until?->utc()->format('Y-m-d H:i:s'),
            'timezone' => $timezone,
            'updated_at' => now(),
        ]);

        Cache::forget(self::CACHE_KEY);
    }
}
