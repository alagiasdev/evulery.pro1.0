<?php

namespace App\Core;

/**
 * Minimal file-based cache for small, cheap-to-serialize values
 * (counters, aggregates, per-tenant stats). Safe for multi-process
 * writes via LOCK_EX on put.
 *
 * Usage:
 *   Cache::remember('key', 600, fn() => expensiveCompute());
 *   Cache::forget('key');
 */
class Cache
{
    private static function path(string $key): string
    {
        $safe = preg_replace('/[^a-z0-9._-]/i', '_', $key);
        return BASE_PATH . '/storage/cache/' . $safe . '.cache';
    }

    public static function get(string $key): mixed
    {
        $file = self::path($key);
        if (!is_file($file)) {
            return null;
        }
        $raw = @file_get_contents($file);
        if ($raw === false) {
            return null;
        }
        $payload = @unserialize($raw);
        if (!is_array($payload) || !isset($payload['expires_at'], $payload['value'])) {
            return null;
        }
        if ($payload['expires_at'] > 0 && $payload['expires_at'] < time()) {
            @unlink($file);
            return null;
        }
        return $payload['value'];
    }

    public static function put(string $key, mixed $value, int $ttlSeconds): void
    {
        $file = self::path($key);
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $payload = [
            'expires_at' => $ttlSeconds > 0 ? time() + $ttlSeconds : 0,
            'value'      => $value,
        ];
        @file_put_contents($file, serialize($payload), LOCK_EX);
    }

    public static function remember(string $key, int $ttlSeconds, callable $resolver): mixed
    {
        $hit = self::get($key);
        if ($hit !== null) {
            return $hit;
        }
        $value = $resolver();
        self::put($key, $value, $ttlSeconds);
        return $value;
    }

    public static function forget(string $key): void
    {
        $file = self::path($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }
}
