<?php

declare(strict_types=1);

namespace Memless;

/**
 * Where a checked-out workspace keeps the header and the built cdylib. Same
 * order as the Go and Node bridges: release before debug, .dylib before .so.
 */
final class Workspace
{
    private const LIBRARIES = [
        'target/release/libmemless_capi.dylib',
        'target/debug/libmemless_capi.dylib',
        'target/release/libmemless_capi.so',
        'target/debug/libmemless_capi.so',
    ];

    public static function libraries(string $root): array
    {
        return array_map(static fn (string $name): string => $root . '/' . $name, self::LIBRARIES);
    }

    public static function header(string $root): string
    {
        return $root . '/crates/memless-capi/include/memless.h';
    }
}
