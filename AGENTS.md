# To My Agents!

It is my fervent wish that this file guide every AI coding agent working with code in this repository.

## Documentation

`docs/internals.md` explains how the package works: the extension, the presets, the
filter, the `ecs` entry point and the migration. Read it before non-trivial changes.

## Project overview

**Nette Coding Standard** supplements [DressCode](https://github.com/dg/dresscode)
(`dresscode/dresscode`), a checker/fixer built on a lossless syntax tree, whose
`dresscode/nette` preset is the standard itself. This package contributes:

- the presets `nette/clean-code`, `nette/optimize-fn` and `nette/types`, each
  building on `DressCode\Presets\Nette`,
- `PhpVersionFilter`, a `skipWhen` predicate honouring `@phpVersion` annotations,
- `Extension`, which a project names in its `dresscode.neon` to get the presets by
  name, the exclusions of version 3 and the filter,
- `ecs`, the deprecated entry point of version 3 accepting its command line, and
  `ecs migrate`, which turns `ncs.php` and `ncs.xml` into `dresscode.neon`.

Rules, engine, configuration API and the test harness all come from DressCode;
use only its public API. The standard itself is defined in DressCode: a rule that
is missing or misbehaves, and a change of the standard, are fixed there, not here.
This package holds no rule implementations (a `Rules/` directory would appear only
for a purely Nette-specific rule).

- **PHP**: 8.2–8.5
- **Package**: `nette/coding-standard`, version 4

## Essential commands

```bash
composer tester                # Nette Tester over tests/
composer phpstan               # PHPStan level 8, no baseline
vendor/bin/dresscode check     # dogfood: own sources with dresscode.neon
vendor/bin/dresscode fix       # ditto, writing fixes
php ecs migrate                # in a project: ncs.php and ncs.xml of version 3 become dresscode.neon
```

`dresscode/dresscode` is not on Packagist yet: `composer install` needs a composer path
repository pointing at a local DressCode checkout (`options: {"symlink": false}`).
Never commit that `repositories` entry, and remember a path repository is a copy —
after changing DressCode run `composer reinstall dresscode/dresscode`, or you test a
stale copy. CI workflows run manually (`workflow_dispatch`) until the package is
published.

## Conventions

- The package follows its own standard: tabs, `declare(strict_types=1)`, single
  quotes, types everywhere, two blank lines between methods.
  `vendor/bin/dresscode fix` applies it.
- PHPStan level 8 without a baseline; `ignoreErrors` only with a reason.
- Commits: small, message lowercase, past tense, `subject: description` when it
  clarifies the area. Linear history. Committed files, commit messages and code
  comments never refer to documents outside the repository nor to transient
  states of the work; describe the current state.

## Traps

- **The name of a preset of this package** (`nette/types`) is known only after the
  extension registered it, so `ecs` and the tests refer to the presets by class
  (`Types::class`); `dresscode/nette` is built into DressCode and its name works
  everywhere.
- **The names of version 3** are two kinds: the fixers of php-cs-fixer and the sniffs
  of phpcs, which DressCode translates itself, and the fixers and the sniff version
  3 had of its own (`Nette/*`, `NetteCodingStandard.*`), which only
  `ConfigMigration` knows. DressCode does not know this package.
- `PhpVersionFilter` matches `@phpVersion` anywhere in the file content, so a
  file that merely quotes the annotation is skipped from checking too.
- `tests/ecs.bin.phpt` and `tests/ConfigMigration.phpt` spawn `ecs` in a system
  temp directory to escape the repository's own `dresscode.neon` discovery.
