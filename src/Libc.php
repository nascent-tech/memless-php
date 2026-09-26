<?php

declare(strict_types=1);

namespace Memless;

/**
 * The libc of this Linux system: 'glibc', 'musl', or null when neither
 * /usr/bin/ldd nor the dynamic loader under /lib can tell it. The bundled
 * Linux libraries need glibc, so musl, or an unknown libc, must not load them.
 */
final class Libc
{
    private const MUSL_LOADERS = ['/lib/ld-musl-x86_64.so.1', '/lib/ld-musl-aarch64.so.1'];

    private const GLIBC_LOADERS = ['/lib64/ld-linux-x86-64.so.2', '/lib/ld-linux-aarch64.so.1'];

    public static function current(): ?string
    {
        $ldd = is_readable('/usr/bin/ldd') ? file_get_contents('/usr/bin/ldd') : false;

        return self::detect(is_string($ldd) ? $ldd : null, file_exists(...));
    }

    /**
     * @param callable(string): bool $exists
     */
    public static function detect(?string $ldd, callable $exists): ?string
    {
        return self::fromLdd($ldd) ?? self::fromLoaders($exists);
    }

    public static function fromLdd(?string $ldd): ?string
    {
        if ($ldd === null) {
            return null;
        }
        if (str_contains($ldd, 'musl')) {
            return 'musl';
        }

        return str_contains($ldd, 'GNU C Library') ? 'glibc' : null;
    }

    /**
     * The musl loader wins, since gcompat installs a glibc one on Alpine.
     *
     * @param callable(string): bool $exists
     */
    public static function fromLoaders(callable $exists): ?string
    {
        if (array_filter(self::MUSL_LOADERS, $exists) !== []) {
            return 'musl';
        }

        return array_filter(self::GLIBC_LOADERS, $exists) !== [] ? 'glibc' : null;
    }
}
