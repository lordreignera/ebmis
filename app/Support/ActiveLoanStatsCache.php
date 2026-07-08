<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class ActiveLoanStatsCache
{
    private const VERSION_KEY = 'active-loan-stats-version';

    public static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public static function bust(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }
}
