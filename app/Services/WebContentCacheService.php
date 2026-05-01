<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * File based cache service for heavy "web content" aggregation endpoints.
 *
 * Used by:
 *  - GET /api/webContents/combineContent/{lang}
 *  - GET /api/webContents/home/pagecontent/{id}/{lang}
 *
 * The cache must be flushed whenever a related module (elements, services,
 * service categories, teams, departments, news, events, reviews, social
 * media links, whatsapp profile, web contents, etc.) is created, updated
 * or deleted, or by hitting POST /api/webContents/cache/flush.
 */
class WebContentCacheService
{
    /**
     * Master switch for this cache layer.
     *
     * Set to false to completely bypass the file cache (every request will
     * be served fresh and nothing will be written). Set to true to enable
     * caching. No .env variable is used by design.
     */
    public const ENABLED = true;

    /**
     * Cache store driver to use.
     */
    public const STORE = 'file';

    /**
     * Index key that tracks every cache key we have written, so that
     * we can flush only what belongs to this service (the file driver
     * does not support tagging).
     */
    public const KEYS_INDEX = 'webcontent_cache_keys_index';

    /**
     * Default TTL in seconds (24 hours).
     */
    public const TTL = 2592000;

    /**
     * Build the cache key for the combineContent endpoint.
     */
    public static function combineKey(string $lang): string
    {
        return 'webcontent.combineContent.' . strtolower($lang);
    }

    /**
     * Build the cache key for the fetchHomePageContent endpoint.
     */
    public static function homePageKey($id, string $lang): string
    {
        return 'webcontent.homePageContent.' . $id . '.' . strtolower($lang);
    }

    /**
     * Remember a value in the file cache and track its key for flushing.
     *
     * @param  string   $key
     * @param  Closure  $callback
     * @param  int|null $ttl
     * @return mixed
     */
    public static function remember(string $key, Closure $callback, ?int $ttl = null)
    {
        // Cache disabled: always run the callback fresh and don't write.
        if (!self::ENABLED) {
            return $callback();
        }

        $store = Cache::store(self::STORE);

        if ($store->has($key)) {
            return $store->get($key);
        }

        $value = $callback();

        $store->put($key, $value, $ttl ?? self::TTL);
        self::registerKey($key);

        return $value;
    }

    /**
     * Track a written key in the index so flush() can purge it later.
     */
    protected static function registerKey(string $key): void
    {
        $store = Cache::store(self::STORE);
        $keys = $store->get(self::KEYS_INDEX, []);

        if (!is_array($keys)) {
            $keys = [];
        }

        if (!in_array($key, $keys, true)) {
            $keys[] = $key;
            $store->forever(self::KEYS_INDEX, $keys);
        }
    }

    /**
     * Flush every cached entry that belongs to this service.
     *
     * @return array Keys that were cleared.
     */
    public static function flush(): array
    {
        $store = Cache::store(self::STORE);
        $keys = $store->get(self::KEYS_INDEX, []);
        $cleared = [];

        if (is_array($keys)) {
            foreach ($keys as $key) {
                try {
                    $store->forget($key);
                    $cleared[] = $key;
                } catch (\Throwable $e) {
                    Log::warning('WebContentCacheService: failed to forget key ' . $key . ' - ' . $e->getMessage());
                }
            }
        }

        $store->forget(self::KEYS_INDEX);

        return $cleared;
    }
}
