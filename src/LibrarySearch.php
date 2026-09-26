<?php

declare(strict_types=1);

namespace Memless;

/**
 * The search order shared by the three bridges: the environment variable,
 * then lib/ of this package for the current platform, then target/release and
 * target/debug of the workspace.
 */
final class LibrarySearch
{
    private const LIBRARY_NOT_FOUND =
        'memless cdylib not found: none bundled with this package, none under target/; set MEMLESS_LIB';

    private const HEADER_NOT_FOUND =
        'memless header not found: none bundled with this package, none in the workspace; set MEMLESS_HEADER';

    private string $packageRoot;

    private string $workspaceRoot;

    public function __construct(string $packageRoot, string $workspaceRoot)
    {
        $this->packageRoot = $packageRoot;
        $this->workspaceRoot = $workspaceRoot;
    }

    public function library(?string $env, ?string $bundle): string
    {
        if ($env !== null) {
            return Existing::file($env, "memless cdylib not found at {$env}; set MEMLESS_LIB");
        }
        $bundled = $bundle === null ? [] : [$this->packageRoot . '/lib/' . $bundle];
        $candidates = array_merge($bundled, Workspace::libraries($this->workspaceRoot));

        return Existing::first($candidates, self::LIBRARY_NOT_FOUND);
    }

    public function header(?string $env): string
    {
        if ($env !== null) {
            return Existing::file($env, "memless header not found at {$env}; set MEMLESS_HEADER");
        }
        $candidates = [$this->packageRoot . '/lib/memless.h', Workspace::header($this->workspaceRoot)];

        return Existing::first($candidates, self::HEADER_NOT_FOUND);
    }
}
