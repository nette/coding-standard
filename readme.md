# Nette Coding Standard

[![Downloads this Month](https://img.shields.io/packagist/dm/nette/coding-standard.svg)](https://packagist.org/packages/nette/coding-standard)
[![Latest Stable Version](https://img.shields.io/packagist/v/nette/coding-standard.svg)](https://github.com/nette/coding-standard/releases)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](/LICENSE)


Introduction
------------

The [Nette coding standard](https://doc.nette.org/contributing/coding-standard) is checked and fixed by [DressCode](https://github.com/dg/dresscode), a code style checker and fixer built on a lossless syntax tree, where it is the `dresscode/nette` preset. This package adds what a Nette project needs on top of it:

- the presets `nette/clean-code`, `nette/optimize-fn` and `nette/types`, which extend the standard with the clean code rules, with imports of the global functions the PHP compiler optimizes, and with native types added from annotations,
- `PhpVersionFilter`, which leaves out the files that declare `@phpVersion` above the running PHP, as the tests of the Nette libraries do,
- the `ecs` command of version 3, so that a project on version 3 keeps working while it moves to DressCode.

Documentation can be found on the [website](https://doc.nette.org/tools/coding-standard). If you like it, **[please make a donation now](https://github.com/sponsors/dg)**. Thank you!


Installation
------------

```shell
composer require --dev dresscode/dresscode nette/coding-standard
```

It requires PHP 8.2 or higher.


Usage
-----

Put a `dresscode.neon` file into the project root, name the extension of this package and the presets you want:

```neon
extensions:
	- Nette\CodingStandard\Extension

presets:
	- dresscode/nette
	- nette/types        # optional: nette/clean-code, nette/optimize-fn, nette/types

paths:
	- src
	- tests
```

Then run DressCode; `check` only reports violations and `fix` repairs them:

```shell
vendor/bin/dresscode check
vendor/bin/dresscode fix
```

**Back up your files first**, or run `fix` on a clean working tree so you can review the changes afterwards with `git diff`.

In read-only mode the tool exits with a non-zero code when any violation is found, so it fits nicely into CI pipelines.

The extension brings the presets of this package under their names, the exclusions version 3 made by itself (`expected`, `tmp`, `fixtures*`) and the `@phpVersion` filter; the configuration file overrides whatever it likes. See the [DressCode documentation](https://github.com/dg/dresscode) for the configuration API and the list of rules (`vendor/bin/dresscode rules` prints them, including the version 3 fixer and sniff codes each rule replaces).

Single occurrences are silenced directly in the code:

```php
$a = "double quotes here"; // dresscode:ignore dresscode/single-quoted-strings
```


Version 3
---------

The `ecs` command of version 3 is deprecated and kept for the transition: it accepts the old command line (`ecs check`, `ecs fix`, default paths `src` and `tests`, `--preset clean-code`…) and runs DressCode with the Nette coding standard. Version presets (`--preset php81`) are ignored, because the PHP version now comes from `composer.json`. A project with a `dresscode.neon` is checked by that configuration, whichever command starts the run.

The configuration of version 3 is converted by

```shell
vendor/bin/ecs migrate
```

which reads `ncs.php` and `ncs.xml` in the current directory and writes `dresscode.neon`; the rule names and sniff codes of version 3 become the names of DressCode rules, and what has no counterpart is listed.

| version 3 | version 4 |
|---|---|
| `ncs.php` with `'rule' => false` | `rules: {rule: false}` in `dresscode.neon` (old rule codes work as aliases) |
| `ncs.xml` with `<exclude name="…"/>` | `rules: {…: false}` in `dresscode.neon` |
| `ncs.xml` with `<exclude-pattern>` | `excludePaths:`, or a `// dresscode:ignore` comment |
| `--config-file overrides.php` | `dresscode.neon` in the project root |
| `--preset php81`, `--preset php` | nothing: the version comes from your `composer.json` |


GitHub Actions
--------------

```yaml
# .github/workflows/coding-style.yml
steps:
    - uses: actions/checkout@v4
    - uses: shivammathur/setup-php@v2
      with:
          php-version: 8.3

    - run: composer install --no-progress --prefer-dist
    - run: vendor/bin/dresscode check
```
