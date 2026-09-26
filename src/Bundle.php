<?php

declare(strict_types=1);

namespace Memless;

/**
 * The library this package bundles for a platform, as a path under lib/, or
 * null when none is bundled, such as on Windows or musl.
 */
final class Bundle
{
    private const LIBRARIES = [
        'Darwin-arm64' => 'darwin-arm64/libmemless_capi.dylib',
        'Darwin-x64' => 'darwin-x64/libmemless_capi.dylib',
        'Linux-x64' => 'linux-x64-gnu/libmemless_capi.so',
        'Linux-arm64' => 'linux-arm64-gnu/libmemless_capi.so',
    ];

    private const ARCHITECTURES = [
        'arm64' => 'arm64',
        'aarch64' => 'arm64',
        'x86_64' => 'x64',
        'amd64' => 'x64',
    ];

    public static function current(): ?string
    {
        return self::library(PHP_OS_FAMILY, php_uname('m'), self::isMusl());
    }

    public static function library(string $family, string $machine, bool $musl): ?string
    {
        $architecture = self::ARCHITECTURES[strtolower($machine)] ?? $machine;

        return $musl ? null : self::LIBRARIES["{$family}-{$architecture}"] ?? null;
    }

    private static function isMusl(): bool
    {
        $ldd = is_readable('/usr/bin/ldd') ? file_get_contents('/usr/bin/ldd') : false;

        return is_string($ldd) && str_contains($ldd, 'musl');
    }
}
