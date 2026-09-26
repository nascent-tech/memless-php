<?php

declare(strict_types=1);

namespace Memless;

/**
 * Performs one memless_load call and returns the status, the handle and the
 * copied message, freeing the C string before returning.
 */
final class Call
{
    public static function load(\FFI $ffi, string $path): array
    {
        $outHandle = $ffi->new('uint64_t');
        $outMessage = $ffi->new('char*');
        $status = $ffi->memless_load($path, \FFI::addr($outHandle), \FFI::addr($outMessage));

        return [$status, (int) $outHandle->cdata, Message::take($ffi, $outMessage)];
    }
}
