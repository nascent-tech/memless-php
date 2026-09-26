<?php

declare(strict_types=1);

namespace Memless;

/**
 * A boundary or internal fault (InvalidArgument or Internal), not a file
 * refusal. instanceof tells it apart from a MemlessRefusal; the status (see
 * memless.h) is carried both in the message and as a readonly property, on
 * the model of the Go and Node bridges' own fault type.
 */
final class MemlessFault extends \RuntimeException
{
    public function __construct(public readonly int $status, string $message)
    {
        parent::__construct("memless fault ({$status}): {$message}");
    }
}
