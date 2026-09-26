<?php

declare(strict_types=1);

namespace Memless;

/**
 * Opens the memless cdylib once through PHP FFI and checks the ABI version.
 */
final class Library
{
    private const ABI_VERSION = 5;

    private static ?\FFI $ffi = null;

    public static function ffi(): \FFI
    {
        if (self::$ffi === null) {
            self::$ffi = self::open();
        }

        return self::$ffi;
    }

    public static function checkVersion(int $version): void
    {
        if ($version !== self::ABI_VERSION) {
            throw new \LogicException('memless ABI mismatch: expected ' . self::ABI_VERSION . ", got {$version}");
        }
    }

    private static function open(): \FFI
    {
        $header = file_get_contents(LibraryPath::header());
        $ffi = \FFI::cdef($header, LibraryPath::library());
        self::checkVersion($ffi->memless_abi_version());

        return $ffi;
    }
}
