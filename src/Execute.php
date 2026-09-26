<?php

declare(strict_types=1);

namespace Memless;

/**
 * Runs one SQL write or transaction verb against an instance handle: calls
 * memless_execute, then returns the affected row count on Ok (0 for a verb),
 * throws the refusal message on Refused (a validation, disk or transaction-guard
 * failure), a fault otherwise.
 */
final class Execute
{
    private const STATUS_INVALID_ARGUMENT = 2;

    public static function run(int $handle, string $sql): int
    {
        if (strpos($sql, "\0") !== false) {
            throw new MemlessFault(self::STATUS_INVALID_ARGUMENT, 'sql contains a NUL byte');
        }

        $ffi = Library::ffi();
        [$status, $affected, $message] = self::call($ffi, $handle, $sql);

        return self::outcome($status, $affected, $message);
    }

    private static function call(\FFI $ffi, int $handle, string $sql): array
    {
        $outAffected = $ffi->new('uint64_t');
        $outMessage = $ffi->new('char*');
        $status = $ffi->memless_execute($handle, $sql, \FFI::addr($outAffected), \FFI::addr($outMessage));

        return [$status, (int) $outAffected->cdata, Message::take($ffi, $outMessage)];
    }

    private static function outcome(int $status, int $affected, string $message): int
    {
        EnsureOk::check($status, $message);

        return $affected;
    }
}
