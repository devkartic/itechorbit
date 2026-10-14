<?php

namespace App\CacheRepositories;

class CacheHandler
{
    public static string $cache_key = 'CACHE-UNIQUE-ID';

    public static function cache_key($key): string
    {
        $key = strtoupper($key);

        return self::$cache_key."-$key";
    }
}
