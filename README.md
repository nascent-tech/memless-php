# memless — PHP bridge

A PHP bridge to the memless C ABI through [FFI](https://www.php.net/manual/en/book.ffi.php)
— no extension to compile, only `ext-ffi` enabled. It loads the same
`libmemless_capi` cdylib as the Go and Node bridges and speaks the same
contract (ABI version 5), so the three stay in parity from a single shared
surface. See the [project README](../../README.md) for what memless is, the
guessing rules and the supported SQL subset.

## Requirements

- PHP 8.1 or later, with the `ffi` extension enabled.

## Install

```sh
composer require nascent-tech/memless
```

That is all: the package carries the native library under `lib/<platform>/`
for macOS (`darwin-arm64`, `darwin-x64`) and Linux with glibc
(`linux-x64-gnu`, `linux-arm64-gnu`), their `lib/SHA256SUMS`, and the C
header in `lib/memless.h`, so there is nothing to download or configure.
Elsewhere, see [The cdylib](#the-cdylib).

Packagist needs `composer.json` at the root of a repository, so the package
is published from the mirror repository `nascent-tech/memless-php`, which the
release workflow fills with this directory and the libraries at each version.
Versions before 0.2.0 were not on Packagist: the package was called
`memless/php` and shipped only as `memless-php-<version>.zip` on the GitHub
release, without the library.

## Surface

```php
<?php

require 'vendor/autoload.php';

use Memless\Instance;
use Memless\MemlessFault;
use Memless\MemlessRefusal;

$db = Instance::load('data.yaml'); // throws MemlessRefusal or MemlessFault

$rows = $db->query('SELECT name FROM users');
// -> [['name' => 'Ada'], ['name' => 'Grace']]

$affected = $db->execute("UPDATE users SET name = 'Zoe' WHERE id = '01H7B2'");

$db->begin();                                   // BEGIN / COMMIT / ROLLBACK
$db->execute("DELETE FROM wallets WHERE id = 'w_123'");
$db->commit();

$db->reload();                                  // re-reads data.yaml

$db->release();
```

- `Instance::query()` returns an array of rows, each row an associative array
  keyed by (possibly qualified, e.g. `users.id`) column name; a cell is a PHP
  `string`, `int`, `float`, `bool`, or `null` for an absent value.
- `Instance::execute()` returns the affected row count (`0` for a transaction
  verb — `begin()`, `commit()` and `rollback()` are thin wrappers over it).
- `MemlessRefusal` (extends `\RuntimeException`) carries the domain message
  verbatim (D13); a boundary or internal fault instead throws `MemlessFault`
  (also a `\RuntimeException`), whose message reads
  `memless fault (<status>): <message>` — the same text as in the Go and Node
  bridges — and whose `status` property carries the ABI status (`2` for an
  invalid argument, such as a released instance or a NUL byte in the path or
  SQL, `3` for an internal fault).
- A library or header that cannot be found, or a library that speaks another
  ABI version, throws a `\LogicException` on the first call that needs it.
- `Instance::release()` is idempotent, and is also called automatically from
  the destructor if you never call it yourself.
- `Instance::reload()` re-reads the file from disk into a fresh in-memory
  state, exactly as `Instance::load()` would build it. It throws a
  `MemlessRefusal` while a transaction is open
  (`cannot reload while a transaction is open`) or when the file would be
  refused at load; the old state stays usable either way.

## The cdylib

The bridge loads the native library on the first call that needs it, never
at include time, and looks for it in the same order as the Go and Node
bridges:

1. `MEMLESS_LIB`, a trusted (ideally absolute) path that must name an
   existing file;
2. `lib/<platform>/libmemless_capi.<ext>` of this package, the platform coming
   from `PHP_OS_FAMILY` and `php_uname('m')` (none on Linux with musl, whose
   `/usr/bin/ldd` says so);
3. inside a checked-out workspace, `target/release/`, then `target/debug/`
   (`.dylib` before `.so`).

If `/usr/bin/ldd` is absent (a minimal musl image), the bridge assumes glibc
and the load fails: set `MEMLESS_LIB`. The bundled Linux libraries need glibc
2.39 or later (Ubuntu 24.04 or later); on an older glibc, set `MEMLESS_LIB` to
a library built locally. When nothing is found, the error says to set
`MEMLESS_LIB`. The library must speak ABI version 5. The C header (`memless.h`) is found the same way:
`MEMLESS_HEADER`, then `lib/memless.h` of this package, then
`crates/memless-capi/include/memless.h` of the workspace. On another
platform, or with your own build, set `MEMLESS_LIB`:

```sh
cargo build --release -p memless-capi
export MEMLESS_LIB="$PWD/target/release/libmemless_capi.so"   # .dylib on macOS
```

`MEMLESS_LIB` loads arbitrary native code, like any FFI library path — only
point it at a library you trust.

## Running the tests

```sh
composer install
vendor/bin/phpunit
```
