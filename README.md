# Memless for PHP

Query and change a YAML file with SQL, from PHP. Memless loads the file into
memory, runs your SQL against it and writes every accepted change back to the
file — no schema, no server. It is made for test fixtures and demos.

Everything you need to use it from PHP is on this page. The
[project README](https://github.com/nascent-tech/memless#readme) has more
detail on the file format, transactions and errors.

## Install

```sh
composer require nascent-tech/memless
```

Requires PHP 8.1 or later with the `ffi` extension enabled; nothing to
compile. The package ships the native engine for macOS (Apple silicon, Intel)
and Linux with glibc 2.39 or later (x86_64, aarch64).

Check that FFI is available:

```sh
php -r 'var_dump(extension_loaded("ffi"));'   # bool(true)
```

If it prints `bool(false)`: on Debian and Ubuntu, FFI comes with
`php8.x-common` and is enabled by default; on Fedora and RHEL, install
`php-ffi`; elsewhere, add `extension=ffi` to `php.ini`. From the
command line — PHPUnit, scripts — nothing else is needed. Under a web server
(PHP-FPM, Apache), PHP only allows FFI in preloaded code by default: set
`ffi.enable=true` there.

## Quick start

```yaml
# data.yaml
users:
  - id: 1
    name: Ada
  - id: 2
    name: Grace
```

```php
<?php

require 'vendor/autoload.php';

use Memless\Instance;

$db = Instance::load('data.yaml');

$db->execute("INSERT INTO users (id, name) VALUES (3, 'Linus')"); // data.yaml is rewritten

print_r($db->query('SELECT name FROM users ORDER BY name'));
// three rows: ['name' => 'Ada'], ['name' => 'Grace'] and ['name' => 'Linus']

$db->release();
```

## Writing the YAML file

Each top-level key is a table, each table is a list of rows, and each row is a
map of column names to values. Memless reads everything else from the data:

| Rule | What Memless expects |
| --- | --- |
| Row identity | Every row has an `id`, text or integer, unique within its table. `5` and `"5"` are different ids. |
| Relations | A column named `<name>_id` points at the `id` of the table `<name>s`, when it exists: `user_id` → `users`. The plural is always `<name>` + `s` (`category_id` → `categorys`). Every value present must name an existing row; a missing value is allowed. |
| Values | Text, integer, decimal or boolean. Lists and maps inside a row are refused. |
| Missing values | Any column but `id` may be left out; `null` (or `~`) counts as missing and reads back as `null`. |
| Types never convert | An integer (`2`) and a decimal (`2.0`) are different types. A comparison only matches the same type: `WHERE amount > 1` skips `1.5`, and `WHERE amount = 2.0` does not match `2`. Keep one type per column (`2.0`, not `2`, in a decimal column). |
| Mixed types | Allowed in a column, but sorting or summing rows of different types is refused. |

## Supported SQL

| Statement | Supported form |
| --- | --- |
| `SELECT` | `SELECT <columns> \| * FROM <table>`, with optional `WHERE`, `ORDER BY` and one `JOIN` |
| `WHERE` | `=`, `<>`, `<`, `<=`, `>`, `>=`, `IS NULL`, `IS NOT NULL`, with `AND`, `OR` and parentheses |
| `JOIN` | One `INNER JOIN <table> ON <a>.<name>_id = <b>.id`; every column is then written `table.column` |
| `ORDER BY` | One or more columns, `ASC` (default) or `DESC`; ties keep the file order, missing values come last |
| Aggregates | `SELECT COUNT(*)` or `SELECT SUM(<column>)`, alone in the select list |
| `INSERT` | `INSERT INTO <table> (<columns>) VALUES (<values>)`; the column list is required |
| `UPDATE` / `DELETE` | `UPDATE <table> SET <column> = <value>, … [WHERE …]`, `DELETE FROM <table> [WHERE …]` |

Anything else — `GROUP BY`, `LIMIT`, several joins, `ORDER BY 1`, `LIKE`, `IN`, subqueries,
`CREATE`… — is refused with `<construct> is outside the supported SQL subset`.

## Good to know

- **Writes are saved at once.** Outside a transaction, each accepted write
  rewrites the file before the call returns. The file is replaced atomically
  through a temporary `.<name>.memless-tmp` next to it: add `.*.memless-tmp`
  to your `.gitignore`.
- **In tests, load a copy of your fixture**, or wrap the test in a
  transaction that you roll back, so that every test starts from the same
  data.
- **One instance per file.** Memless does not coordinate writers: do not
  change the same file from two instances or two processes at once.

## API

| Call | Returns | What it does |
| --- | --- | --- |
| `Instance::load($path)` | `Instance` | Reads the YAML file into memory. |
| `$db->query($sql)` | `array` | Runs a `SELECT`. Each row is an array keyed by column name (`users.name` in a query with a `JOIN`). |
| `$db->execute($sql)` | `int` | Runs an `INSERT`, `UPDATE` or `DELETE` and returns the number of rows affected. Outside a transaction, the file is rewritten before it returns. |
| `$db->begin()` / `$db->commit()` / `$db->rollback()` | `void` | Groups writes: nothing is written until `commit()`; `rollback()` discards them. |
| `$db->reload()` | `void` | Rereads the file, after something else changed it. Refused while a transaction is open. |
| `$db->release()` | `void` | Frees the instance. Calling it again does nothing, and the destructor calls it for you. |

## Values

| In the file | In PHP |
| --- | --- |
| text | `string` |
| integer | `int` |
| decimal | `float` |
| boolean | `bool` |
| missing value | `null` |

## Errors

Every method throws one of two exceptions, both `\RuntimeException`. Their
messages are identical in the Node.js and Go versions of Memless.

- **`Memless\MemlessRefusal`** — the file or the SQL breaks a rule: unknown
  table or column, broken relation, unsupported SQL, transaction already open.
  It is an expected outcome, which you can assert on in a test.
- **`Memless\MemlessFault`** — the package was misused (for example, a call
  after `release()`) or an internal error happened. Its `status` property is
  `2` for an invalid argument and `3` for an internal error.

```php
use Memless\MemlessRefusal;

$this->expectException(MemlessRefusal::class);
$this->expectExceptionMessage('no column "nope" in table "users"');
$db->query('SELECT nope FROM users');
```

If the native library or its C header cannot be found, or the library has an
incompatible version, the first call throws a `\LogicException` that explains
what to do.

## Troubleshooting

**`nascent-tech/memless requires ext-ffi`** from Composer, or **`Class "FFI"
not found`** when the code runs — the `ffi` extension is not enabled; see
[Install](#install).

**Your platform is not covered** (Alpine and other musl-based Linux, glibc
older than 2.39). Build the engine and set `MEMLESS_LIB`:

```sh
git clone https://github.com/nascent-tech/memless.git && cd memless
cargo build --release -p memless-capi
export MEMLESS_LIB="$PWD/target/release/libmemless_capi.so"   # .dylib on macOS
```

The package looks for the engine in this order: `MEMLESS_LIB`, then the library
bundled for your platform under `lib/<platform>/`, then, inside a clone of the
repository, `target/release/` and `target/debug/`. On Linux it checks the libc
first (`/usr/bin/ldd`, then the dynamic loader under `/lib`): musl, or a libc
it cannot identify, gets no bundled library. The C header is found the same
way, with `MEMLESS_HEADER`, then `lib/memless.h`. `MEMLESS_LIB` loads native
code into your process; only point it at a library you trust.

## Contributing

This package is developed in
[`bindings/php`](https://github.com/nascent-tech/memless/tree/main/bindings/php)
of [nascent-tech/memless](https://github.com/nascent-tech/memless), with the
engine and the Node.js and Go versions. Open issues and pull requests there —
the repository `nascent-tech/memless-php`, which Packagist reads, is a
read-only copy published with each release. See the
[contributing guide](https://github.com/nascent-tech/memless/blob/main/CONTRIBUTING.md)
and the [security policy](https://github.com/nascent-tech/memless/security/policy).

To work on this package in a clone of the repository:

```sh
cargo build --release -p memless-capi   # in a clone, the package loads target/release/
cd bindings/php && composer install && vendor/bin/phpunit
```
