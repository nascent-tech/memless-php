<?php

declare(strict_types=1);

namespace Memless;

/**
 * Copies the owned message out of `out_message` and frees it, so the C pointer
 * never outlives the call.
 */
final class Message
{
    public static function take(\FFI $ffi, \FFI\CData $out): string
    {
        if (\FFI::isNull($out)) {
            return '';
        }

        $text = \FFI::string($out);
        $ffi->memless_free_string($out);

        return $text;
    }
}
