# Internals

How `nette/coding-standard` works inside. It is not a checker of its own, and since
version 4 it does not define the standard either: the Nette coding standard is the
`dresscode/nette` preset of [DressCode](https://github.com/dg/dresscode), and this
package is a **supplement** to it — derived presets, a file filter, an extension
that wires them into a project, and the entry point of version 3. All engine
behavior (parsing, rules, fixing, suppression, configuration) lives in DressCode;
read its documentation for the rule and preset API.

## The presets

`nette/clean-code`, `nette/optimize-fn` and `nette/types` (`src/Presets/`) each name
`DressCode\Presets\Nette` as their parent and add a handful of rules; they define
nothing of the standard themselves. A change of the standard is a change of
`dresscode/nette` in DressCode. The rule catalog is in the preset classes — read
them, it is not mirrored here.

A preset name is registered when its class is resolved, so the name `nette/types`
works only where the extension has run; a class name (`Types::class`) works
everywhere, which is what `ecs` and the tests use.

## The extension

`Nette\CodingStandard\Extension` is what a project names in its `dresscode.neon`
(`extensions: [Nette\CodingStandard\Extension]`) next to `presets: [dresscode/nette]`.
It registers the three preset classes, so a configuration and `--preset` can name
them, and it sets the exclusions version 3 made by itself (`expected`, `tmp`,
`fixtures*`) together with the `PhpVersionFilter`. It enables no preset: which
presets apply is the decision of the project. All of it is a layer *below* the
project, so the configuration file overrides whatever it likes.

## PhpVersionFilter

`PhpVersionFilter::create()` returns a `Config::skipWhen()` predicate: a file
whose content matches `@phpVersion X.Y` is skipped when the running PHP is older
than X.Y. The match is anywhere in the content, not just in a header — a file
that merely quotes the annotation (such as the filter's own test) is skipped too.
A predicate cannot be written in NEON, which is why the filter comes through the
extension.

## ecs

`ecs` is the entry point of version 3 and the only binary of the package; it is
deprecated, a project is expected to run `dresscode` with a `dresscode.neon`. It
accepts the version 3 command line, translates it and hands it to
`DressCode\Console\Application` together with a default configuration: the
extension, `dresscode/nette`, and the paths `src` and `tests` version 3 checked by
itself. DressCode uses the default only when it finds no configuration file upwards
from the working directory, so a project configuration always wins, and `--preset`
adds to it instead of replacing it.

The command may be omitted (`check` is the default, `--fix` forces `fix`), missing
paths are left to the configuration, a version preset (`--preset php`,
`--preset php81`…) maps to the standard itself — the PHP version now comes from the
project — and `--preset clean-code|optimize-fn|types` maps to the class of the
preset. `--config-file` is refused with a pointer to `dresscode.neon`,
`--no-progress` is dropped.

`ecs migrate` reads `ncs.php` and `ncs.xml` of the current directory and writes
`dresscode.neon`; it refuses to overwrite one.

## ConfigMigration

`ConfigMigration::migrate()` turns the version 3 configuration of a directory into
the content of `dresscode.neon`: the fixer overrides of `ncs.php` become `rules`
(with their options copied as they are, which a note points out), the
`exclude-pattern` elements of `ncs.xml` become `excludePaths` and
`excludeRulePaths`, and the directories version 3 checked by itself become `paths`.
Every name goes through two tables: the fixers and the sniff version 3 had of its
own (`Nette/*`, `NetteCodingStandard.*`), which only this class knows, and then the
aliases of DressCode for the fixers of php-cs-fixer and the sniffs of phpcs. Names
no rule owns are listed in `$unknown` and left out. The output is assembled by
`Neon::encode()` section by section, a blank line between them.

## Tests

`tests/preset.*.phpt` assert the resolved rule list of each preset against
`DressCode\Presets\Nette` plus its additions. `tests/ecs.bin.phpt` and
`tests/ConfigMigration.phpt` spawn `ecs` in a system temp directory to escape the
repository's own `dresscode.neon`.

## Dogfood

`dresscode.neon` in the repository root checks the package's own sources with the
extension and `dresscode/nette` (`vendor/bin/dresscode check`); CI runs it too. It
exercises the extension in the way projects are expected to use it.
