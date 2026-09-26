<?php

declare(strict_types=1);

namespace Memless;

/**
 * A file the domain refused. Carries the domain message verbatim (D13): the
 * bridge translates the error shape, never the text.
 */
final class MemlessRefusal extends \RuntimeException
{
}
