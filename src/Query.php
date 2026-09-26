<?php

declare(strict_types=1);

namespace Memless;

/**
 * Runs one SQL query against an instance handle: calls memless_query, then reads
 * the rows on Ok or throws the refusal message on Refused, a fault otherwise.
 */
final class Query
{
    private const STATUS_OK = 0;

    private const STATUS_REFUSED = 1;

    private const STATUS_INVALID_ARGUMENT = 2;

    public static function run(int $handle, string $sql): array
    {
        if (strpos($sql, "\0") !== false) {
            throw new MemlessFault(self::STATUS_INVALID_ARGUMENT, 'sql contains a NUL byte');
        }

        $ffi = Library::ffi();
        [$status, $result, $message] = self::call($ffi, $handle, $sql);

        return self::outcome($ffi, $status, $result, $message);
    }

    private static function call(\FFI $ffi, int $handle, string $sql): array
    {
        $outResult = $ffi->new('uint64_t');
        $outMessage = $ffi->new('char*');
        $status = $ffi->memless_query($handle, $sql, \FFI::addr($outResult), \FFI::addr($outMessage));

        return [$status, (int) $outResult->cdata, Message::take($ffi, $outMessage)];
    }

    private static function outcome(\FFI $ffi, int $status, int $result, string $message): array
    {
        if ($status === self::STATUS_OK) {
            return Result::read($ffi, $result);
        }

        if ($status === self::STATUS_REFUSED) {
            throw new MemlessRefusal($message);
        }

        throw new MemlessFault($status, $message);
    }
}
