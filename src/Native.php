<?php

declare(strict_types=1);

namespace Memless;

/**
 * Releases an instance handle through the shared FFI, so Instance stays a thin
 * facade over Loader, Query, Execute and this call.
 */
final class Native
{
    public static function release(int $handle): void
    {
        Library::ffi()->memless_release($handle);
    }
}
