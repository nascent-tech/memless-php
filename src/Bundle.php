<?php

declare(strict_types=1);

namespace Memless;

/**
 * The library this package bundles for a platform, as a path under lib/, or
 * null when none is bundled, such as on Windows, on musl, or on a Linux whose
 * libc cannot be told.
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
        $libc = PHP_OS_FAMILY === 'Linux' ? Libc::current() : null;

        return self::library(PHP_OS_FAMILY, php_uname('m'), $libc);
    }

    public static function library(string $family, string $machine, ?string $libc): ?string
    {
        if ($family === 'Linux' && $libc !== 'glibc') {
            return null;
        }
        $architecture = self::ARCHITECTURES[strtolower($machine)] ?? $machine;

        return self::LIBRARIES["{$family}-{$architecture}"] ?? null;
    }
}
