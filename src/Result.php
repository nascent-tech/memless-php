<?php

declare(strict_types=1);

namespace Memless;

/**
 * Reads a query result into an array of rows (column name => value) via Headers
 * and Records, then always releases it, even if a read raises.
 */
final class Result
{
    public static function read(\FFI $ffi, int $result): array
    {
        try {
            return Records::of($ffi, $result, Headers::of($ffi, $result));
        } finally {
            $ffi->memless_result_release($result);
        }
    }
}
