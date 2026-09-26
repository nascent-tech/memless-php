<?php

declare(strict_types=1);

namespace Memless;

/**
 * Picks an existing file, or fails with the message the caller gives.
 */
final class Existing
{
    public static function file(string $path, string $missing): string
    {
        if (!is_file($path)) {
            throw new \LogicException($missing);
        }

        return $path;
    }

    public static function first(array $candidates, string $missing): string
    {
        $found = array_values(array_filter($candidates, 'is_file'));
        if ($found === []) {
            throw new \LogicException($missing);
        }

        return $found[0];
    }
}
