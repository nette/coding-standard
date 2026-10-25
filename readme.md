# Nette Coding Standard code checker & fixer

[![Downloads this Month](https://img.shields.io/packagist/dm/nette/coding-standard.svg)](https://packagist.org/packages/nette/coding-standard)
[![Latest Stable Version](https://img.shields.io/packagist/v/nette/coding-standard.svg)](https://github.com/nette/coding-standard/releases)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](/LICENSE)


> [!IMPORTANT]
> **This project is abandoned in favor of [DressCode](https://dresscode.run)**, the next-generation tool that checks and fixes the [Nette coding standard](https://doc.nette.org/contributing/coding-standard), which it has built in as the `nette` standard. Version 3 is the last one: it keeps working, but it gets no new rules and no new features. How to switch is described in [Moving from Version 3](https://doc.nette.org/tools/coding-standard#moving-from-version-3).


Why DressCode
-------------

- **One tool instead of two.** Nette Coding Standard drives PHP CS Fixer and PHP_CodeSniffer side by side, each with its own rules and its own configuration (`ncs.php`, `ncs.xml`). DressCode is a single engine with a single `dresscode.neon`.
- **It changes only what it fixes.** DressCode reads every file into a lossless syntax tree, so a fix touches the piece of code that broke the standard and nothing else.
- **It is faster.** One file takes about a third to a half of the time PHP CS Fixer needs, and fixing a project takes 36 to 54 percent of the time of `phpcbf` ([measured](https://github.com/dg/dresscode#performance-php-cs-fixer-php_codesniffer) in October 2026).
- **It upgrades the code too.** Besides the style, it rewrites the code to what newer PHP offers, and with `dresscode/upgrade-nette` to the new API of the Nette libraries.
- **Its findings say why.** Every finding names the setting it breaks, and a fix that may change what the code does waits for your consent.

```shell
composer global require dresscode/dresscode
```

Then put a `dresscode.neon` with `use: [nette]` into the project root and run `dresscode check` or `dresscode fix`. The [manual](https://doc.nette.org/tools/coding-standard) walks you through it.


Version 3
---------

The rest of this page documents version 3 for the projects that still use it.

Nette Coding Standard checks and automatically fixes your PHP code so that it follows the [Nette coding standard](https://doc.nette.org/contributing/coding-standard) – from indentation and braces to spacing and the ordering of imports.

Under the hood it combines two industry-standard tools, [PHP CS Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer) and [PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer), preconfigured with the full Nette rule set, so you don't have to configure anything yourself. It also ships rules for the latest PHP versions and applies them incrementally based on the PHP version your project uses.

It runs as a standalone application, not as a project dependency, which keeps its many dependencies away from your own.


### Installation

Install it globally via Composer:

```shell
composer global require nette/coding-standard
```

Make sure your global Composer `bin` directory is in your [`PATH`](https://getcomposer.org/doc/03-cli.md#global).
The `ecs` command is then available from anywhere, on any operating system.

Alternatively, install it as a standalone project, which is handy for pinning a specific version in CI:

```shell
composer create-project nette/coding-standard
```

It requires PHP 8.0 or higher.


### Usage

The tool provides two commands. The `check` command runs in read-only mode and only reports violations, while `fix` repairs them automatically:

```shell
ecs check
ecs fix
```

Without any arguments it scans the `src` and `tests` directories. You can also pass one or more of your own paths:

```shell
ecs check app bin
```

**Back up your files first**, or run `fix` on a clean working tree so you can review the changes afterwards with `git diff`.

In read-only mode the tool exits with a non-zero code when any violation is found, so it fits nicely into CI pipelines.


### PHP Version

The tool supports PHP 8.0 to 8.5 and applies the rules incrementally according to the version. The version preset is detected automatically from your project's `composer.json`. If auto-detection is not possible, specify it manually:

```shell
ecs check --preset php81
```

The name `php` stands for the auto-detected version preset, so `--preset php` behaves exactly like omitting the option. That is useful for tools that always pass `--preset`:

```shell
ecs fix --preset php
```


### Custom Configuration

You can tweak the rules per-project by placing `ncs.php` (PHP CS Fixer) and/or `ncs.xml` (PHP_CodeSniffer) in your project root. Both are discovered automatically and merged on top of the preset, with your values taking precedence.

**`ncs.php`** returns an associative array of fixer overrides:

```php
<?php
return [
	'strict_comparison' => false,
	'PhpCsFixerCustomFixers/commented_out_function' => false, // don't comment out dump(), var_dump(), ...
];
```

**`ncs.xml`** is a PHP_CodeSniffer ruleset. It does not need to reference the version preset – that is added automatically – and you can use `$presets/` to pull in any bundled preset:

```xml
<?xml version="1.0"?>
<ruleset name="MyProject">
	<!-- enable grouped `use function`/`use const` imports -->
	<rule ref="$presets/optimize-fn.xml"/>

	<!-- disable a rule -->
	<exclude name="SlevomatCodingStandard.TypeHints.ReturnTypeHint"/>

	<!-- override a rule's property -->
	<rule ref="NetteCodingStandard.WhiteSpace.FunctionSpacing">
		<properties>
			<property name="spacing" value="1"/>
		</properties>
	</rule>
</ruleset>
```


### Ad-hoc Configuration via `--config-file`

For one-off runs (e.g. when you want to keep `dump()` calls intact during debugging) you can point to an additional config file without committing it to the project:

```shell
ecs fix src --config-file ./my-overrides.php
```

The tool is selected by extension: `.php` applies to PHP CS Fixer, `.xml` to PHP_CodeSniffer. You can pass `--config-file` twice to configure both tools. Project-level `ncs.php`/`ncs.xml` are still used; values from `--config-file` take precedence.


### GitHub Actions

In a CI pipeline, install the tool as a project and run it from there:

```yaml
# .github/workflows/coding-style.yml
steps:
    - uses: actions/checkout@v4
    - uses: shivammathur/setup-php@v2
      with:
          php-version: 8.1

    - run: composer create-project nette/coding-standard temp/coding-standard
    - run: php temp/coding-standard/ecs check
```
