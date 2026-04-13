# Nette Coding Standard code checker & fixer

[![Downloads this Month](https://img.shields.io/packagist/dm/nette/coding-standard.svg)](https://packagist.org/packages/nette/coding-standard)
[![Latest Stable Version](https://img.shields.io/packagist/v/nette/coding-standard.svg)](https://github.com/nette/coding-standard/releases)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](/LICENSE)


This is set of [sniffs](https://github.com/squizlabs/PHP_CodeSniffer) and [fixers](https://github.com/FriendsOfPHP/PHP-CS-Fixer) that **checks and fixes** code of Nette Framework against [Coding Standard in Documentation](https://doc.nette.org/en/contributing/coding-standard).


## Installation and Usage

Install the tool globally:

```
composer global require nette/coding-standard
```

Check coding standard in folders `src` and `tests`:

```bash
ecs check src tests
```

And fix it:

```bash
ecs fix src tests
```

The PHP version preset is automatically detected from your project's `composer.json`. If auto-detection is not possible, you can specify it manually:

```bash
ecs check src tests --preset php81
```


### Custom Configuration

You can tweak rules per-project by placing `ncs.php` (PHP CS Fixer) and/or `ncs.xml` (PHP_CodeSniffer) in your project root. Both are discovered automatically and merged on top of the preset.

**`ncs.php`** returns an associative array of fixer overrides:

```php
<?php
return [
	'strict_comparison' => false,
	'PhpCsFixerCustomFixers/commented_out_function' => false, // don't comment out dump(), var_dump(), ...
];
```

**`ncs.xml`** is a PHP_CodeSniffer ruleset. It does not need to reference the version preset – it is automatically combined with it. Use `$presets/` to reference any bundled preset:

```xml
<?xml version="1.0"?>
<ruleset name="MyProject">
	<!-- Optional: enable use function/const imports -->
	<rule ref="$presets/optimize-fn.xml"/>

	<!-- Disable a rule -->
	<exclude name="SlevomatCodingStandard.TypeHints.ReturnTypeHint"/>
</ruleset>
```


### Ad-hoc Configuration via `--config-file`

For one-off runs (e.g. when you want to keep `dump()` calls intact during debugging) you can point to an additional config file without committing it to the project:

```bash
ecs fix src --config-file ./my-overrides.php
```

The tool is selected by extension: `.php` applies to PHP CS Fixer, `.xml` to PHP_CodeSniffer. You can pass `--config-file` twice to configure both tools. Project-level `ncs.php`/`ncs.xml` are still used; values from `--config-file` take precedence.


### GitHub Actions

```yaml
# .github/workflows/coding-style.yml
steps:
    - uses: actions/checkout@v4
    - uses: shivammathur/setup-php@v2
      with:
          php-version: 8.1

    - run: composer create-project nette/coding-standard temp/coding-standard
    - run: php temp/coding-standard/ecs check src tests

```
