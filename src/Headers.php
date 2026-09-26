<?php

declare(strict_types=1);

namespace Memless;

/**
 * Reads the result column names in order. The core refuses a projection whose
 * output headers collide (Q2 "duplicate output column"), so the bridge never
 * sees a duplicate to resolve — it returns the names the core gave.
 */
final class Headers
{
    public static function of(\FFI $ffi, int $result): array
    {
        $count = $ffi->memless_result_column_count($result);
        $names = array_fill(0, $count, '');
        for ($index = 0; $index < $count; $index++) {
            $names[$index] = $ffi->memless_result_column($result, $index);
        }

        return $names;
    }
}
