<?php

declare(strict_types=1);

namespace Memless;

/**
 * Opens an instance on a path: calls memless_load and returns the handle on Ok,
 * throwing the refusal message on Refused, a boundary fault otherwise.
 */
final class Loader
{
    private const STATUS_OK = 0;

    private const STATUS_REFUSED = 1;

    private const STATUS_INVALID_ARGUMENT = 2;

    public static function open(string $path): int
    {
        if (strpos($path, "\0") !== false) {
            throw new MemlessFault(self::STATUS_INVALID_ARGUMENT, 'path contains a NUL byte');
        }

        [$status, $handle, $message] = Call::load(Library::ffi(), $path);

        return self::outcome($status, $handle, $message);
    }

    private static function outcome(int $status, int $handle, string $message): int
    {
        if ($status === self::STATUS_OK) {
            return $handle;
        }

        if ($status === self::STATUS_REFUSED) {
            throw new MemlessRefusal($message);
        }

        throw new MemlessFault($status, $message);
    }
}
