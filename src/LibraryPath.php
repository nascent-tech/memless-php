<?php

declare(strict_types=1);

namespace Memless;

/**
 * Resolves the header and cdylib paths: from the environment, then from the
 * files this package bundles, then from the workspace.
 */
final class LibraryPath
{
    public static function header(): string
    {
        return self::search()->header(self::env('MEMLESS_HEADER'));
    }

    public static function library(): string
    {
        return self::search()->library(self::env('MEMLESS_LIB'), Bundle::current());
    }

    private static function search(): LibrarySearch
    {
        return new LibrarySearch(dirname(__DIR__), dirname(__DIR__, 3));
    }

    private static function env(string $name): ?string
    {
        $value = getenv($name);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
