<?php

declare(strict_types=1);

namespace Memless;

/**
 * Turns a memless status into the bridge's outcome, shared by Execute and
 * Reload: returns on Ok, throws a MemlessRefusal carrying the core's message
 * on Refused, and a MemlessFault carrying the status otherwise.
 */
final class EnsureOk
{
    private const STATUS_OK = 0;

    private const STATUS_REFUSED = 1;

    public static function check(int $status, string $message): void
    {
        if ($status === self::STATUS_OK) {
            return;
        }

        if ($status === self::STATUS_REFUSED) {
            throw new MemlessRefusal($message);
        }

        throw new MemlessFault($status, $message);
    }
}
