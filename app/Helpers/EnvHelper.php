<?php

namespace App\Helpers;

class EnvHelper
{
    public static function baseUrl(): string
    {
        return rtrim(env('APP_URL', config('app.url')), '/');
    }

    public static function assetUrl(string $path = ''): string
    {
        return self::baseUrl() . '/' . ltrim($path, '/');
    }

    public static function isLocal(): bool
    {
        return app()->environment('local');
    }

    public static function isProduction(): bool
    {
        return app()->environment('production');
    }
}
