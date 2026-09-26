<?php

declare(strict_types=1);

namespace Memless;

/**
 * Re-reads the file an instance was loaded on and replaces its in-memory
 * state: calls memless_reload, then returns on Ok, throws a MemlessRefusal on
 * Refused (an open transaction or a refused file, state left intact), and a
 * MemlessFault otherwise.
 */
final class Reload
{
    public static function run(int $handle): void
    {
        $ffi = Library::ffi();
        [$status, $message] = self::call($ffi, $handle);

        EnsureOk::check($status, $message);
    }

    private static function call(\FFI $ffi, int $handle): array
    {
        $outMessage = $ffi->new('char*');
        $status = $ffi->memless_reload($handle, \FFI::addr($outMessage));

        return [$status, Message::take($ffi, $outMessage)];
    }
}
