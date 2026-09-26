<?php

declare(strict_types=1);

namespace Memless;

/**
 * Reads the result rows as arrays keyed by column name, cell by cell.
 */
final class Records
{
    public static function of(\FFI $ffi, int $result, array $columns): array
    {
        $count = $ffi->memless_result_row_count($result);
        $rows = array_fill(0, $count, []);
        for ($row = 0; $row < $count; $row++) {
            $rows[$row] = self::record($ffi, $result, $columns, $row);
        }

        return $rows;
    }

    private static function record(\FFI $ffi, int $result, array $columns, int $row): array
    {
        $cells = [];
        foreach ($columns as $index => $name) {
            $cells[$name] = Cell::value($ffi, $result, $row, $index);
        }

        return $cells;
    }
}
