<?php

declare(strict_types=1);

namespace Memless;

/**
 * Reads one result cell by its kind, copying a text value before the result is
 * released. An absent cell is null; the C string never outlives the call.
 */
final class Cell
{
    private const TEXT = 1;

    private const INTEGER = 2;

    private const DECIMAL = 3;

    private const ABSENT = 0;

    private const BOOLEAN = 4;

    public static function value(\FFI $ffi, int $result, int $row, int $column): int|float|bool|string|null
    {
        $out = self::holders($ffi);
        $kind = self::read($ffi, $result, $row, $column, $out);

        return self::of($kind, $out);
    }

    private static function holders(\FFI $ffi): array
    {
        return [
            'integer' => $ffi->new('int64_t'),
            'decimal' => $ffi->new('double'),
            'boolean' => $ffi->new('int32_t'),
            'text' => $ffi->new('char*'),
        ];
    }

    private static function read(\FFI $ffi, int $result, int $row, int $column, array $out): int
    {
        return $ffi->memless_result_cell(
            $result,
            $row,
            $column,
            \FFI::addr($out['integer']),
            \FFI::addr($out['decimal']),
            \FFI::addr($out['boolean']),
            \FFI::addr($out['text']),
        );
    }

    private static function of(int $kind, array $out): int|float|bool|string|null
    {
        return match ($kind) {
            self::TEXT => \FFI::string($out['text']),
            self::INTEGER => (int) $out['integer']->cdata,
            self::DECIMAL => (float) $out['decimal']->cdata,
            self::BOOLEAN => (bool) $out['boolean']->cdata,
            self::ABSENT => null,
            default => throw new \LogicException("unknown cell kind {$kind}"),
        };
    }
}
