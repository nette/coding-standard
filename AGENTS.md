# To My Agents!

It is my fervent wish that this file guide every AI coding agent working with code in this repository.

## Documentation

Any distilled, agent-facing documentation for this package - how it works
internally and the rationale behind key design decisions - lives in `docs/`.
Consult it before non-trivial changes; it is the source of truth from which the
public manual is distilled.

This is not a linter of its own - it is a thin orchestrator over two third-party
engines, and the expensive knowledge is in **how they are wired** (dual engines,
mirrored preset trees, override layering), not in the rule lists (grep the preset
files for those). Read `docs/internals.md` before non-trivial changes.

## Project Overview

**Nette Coding Standard** is a unified CLI checker/fixer (`ecs`) that enforces the
Nette coding standard by driving **PHP CS Fixer** (automated fixing, `.php` presets)
and **PHP_CodeSniffer** (static analysis, `.xml` presets), with a handful of custom
fixers/sniffs and version-specific presets.

- **PHP Version**: 8.0 - 8.5
- **Package**: `nette/coding-standard`

## Essential Commands

```bash
# Check (dry-run) / fix, with optional paths (default: src tests) and preset
./ecs check src tests
./ecs fix src tests --preset php81
./ecs fix src tests --preset php     # 'php' = the auto-detected version preset

# Extra config layered on top (.php -> Fixer, .xml -> Sniffer; repeat for both)
./ecs fix src --config-file ./overrides.php --config-file ./overrides.xml

# Sniff tests (Nette Tester)
vendor/bin/tester tests -s        # or: composer tester

# Validate custom fixers/sniffs against the fixtures
./ecs check examples/ValidClass.php
./ecs check examples/InvalidConstructs.php   # should report violations
```

Installed globally via `composer create-project nette/coding-standard /nette-cs`,
then run `/nette-cs/ecs ...` from anywhere.

## Conventions

- The tool dogfoods its own standard (run `./ecs fix` on it). Sniff tests are
  `tests/SniffTestRunner.phpt` with `tests/fixtures/*.inc` (+ optional `.inc.expected`;
  a leading `<?php // {"prop": val}` JSON comment injects sniff properties). Fixer
  tests are `tests/FixerTestRunner.phpt` with `tests/fixtures.fixer/*.inc` (same
  convention, but the JSON comment is the enabled-rules array).

## Working in this repo

- **Two independent engines run sequentially over one file list.** A given rule is
  owned by exactly one engine; there is **no unified source of truth** for "the Nette
  style" - it's the union of both. Adding a version rule usually means editing **both**
  preset trees (`preset-fixer/phpNN.php` *and* `preset-sniffer/phpNN.xml`).
- **The two preset trees are mirrored by identical name, and a one-sided preset is a
  SILENT no-op** - if a name resolves in only one tree, the other engine returns
  success without doing anything. Keep the trees in lockstep.
- **Both engines are subprocesses**, so everything crosses via a file or env var. The
  file list reaches them asymmetrically: the sniffer via `--file-list=filelist.tmp`,
  but `preset-fixer/base.php` reads `../filelist.tmp` itself. `setPaths()` must run
  first (it also applies the `@phpVersion` filter).
- **The fixer `require` chain shares scope, and it's load-bearing.** `base.php`
  discovers `ncs.php` / the CLI `NCS_CONFIG_FILE_PHP` override into `$customRules`,
  which `php80.php` folds into the merge - **wrapping a preset in a function or
  renaming that variable silently drops all overrides.** Merge operators differ on
  purpose: `array_merge` (right wins) in `php80.php` vs `+` (left wins) in `php81+`.
- **The six `src/Fixer/*` shadow their upstream fixers** ("disable stock, enable
  `Nette/*`"). Priority ordering matters (`method_argument_space` 30, then
  `braces_position` -2, then `statement_indentation` -3). `MethodArgumentSpaceFixer`
  only touches CALLS, not declarations; `ClassAndTraitVisibilityRequiredFixer`
  reflection-wraps a `final` upstream fixer; `NoLeadingSlashInGlobalNamespaceFixer`
  forks the `PhpCsFixerCustomFixers` one to keep the slash when an import in the same
  file shadows the first name segment; `OrderedImportsFixer` forks the stock one,
  which corrupts comma-separated imports of mixed types.
- **The sniffer wrapper ruleset (preset -> `ncs.xml` -> CLI xml) is built only for a
  `phpDD`-named preset** - a custom-named preset silently ignores project sniffer
  overrides. `src/NetteCodingStandard/ruleset.xml` is vestigial; custom sniffs are
  pulled by file path in `Nette.xml` (`OptimizeGlobalCalls` only in `optimize-fn.xml`).
- **Discovery is asymmetric:** `ncs.php` is walked up from CWD, `ncs.xml` is read only
  at the detected project root - they diverge when CWD != root. **Exit codes:** `phpcbf`
  returning `1` means it *fixed* something (success).
- The actual rule catalogs live in the preset files (`preset-fixer/common/*.php`,
  `preset-sniffer/Nette.xml`, ...) - read those, don't mirror them here. User-facing
  usage (`ncs.php`/`ncs.xml` overrides, CI setup) is in the README / web manual.

## The optimize-fn preset

`preset-sniffer/optimize-fn.xml` (opt-in, sniffer-only - it deliberately has no
fixer-tree mirror) configures `OptimizeGlobalCallsSniff` to generate grouped
`use function` / `use const` imports and strip the corresponding `\` prefixes
from the code body. What gets imported is intentionally narrow:

- **Functions:** with `optimizedFunctionsOnly=true` only the calls the PHP
  compiler replaces with special opcodes - the list is hardcoded in the sniff
  (`$compilerOptimizedFunctions`, taken from `zend_compile.c`): `strlen`, the
  `is_*` family, `boolval`/`intval`/`floatval`/`strval`, `defined`, `chr`, `ord`,
  `call_user_func(_array)`, `in_array`, `count`/`sizeof`, `get_class`,
  `get_called_class`, `gettype`, `func_num_args`, `func_get_args`, `array_slice`,
  `array_key_exists`, `sprintf`. Importing these is what unlocks the opcodes;
  other global functions gain nothing, so they are not imported.
- **Constants:** only names matching `includedConstants` in the preset - `PHP_*`
  and `DIRECTORY_SEPARATOR`. `TRUE`/`FALSE`/`NULL` are always ignored (hardcoded).
- Existing `use function`/`use const` imports outside these lists are **kept** as
  long as the symbol is still used in the file; imports are merged into one
  alphabetically sorted grouped statement per kind.
