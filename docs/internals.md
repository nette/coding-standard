# Internals

How `nette/coding-standard` works inside. It is not a linter of its own: it is a
**thin orchestrator** that drives two third-party engines with a preconfigured
Nette rule set, plus a handful of custom fixers/sniffs. The value that is
expensive to rediscover lives in how those pieces are wired, not in the rule
lists (read the preset files for those).

## The dual-engine model

Every run drives **two independent engines, sequentially**, over the *same* file
list (`run.php` → `Checker::runFixer()` then `Checker::runSniffer()`):

- **Fixer** = PHP CS Fixer, configured by a `.php` preset. Rewrites code.
- **Sniffer** = PHP_CodeSniffer, configured by a `.xml` preset. `check` runs
  `phpcs` (report only); `fix` runs `phpcbf` (also rewrites).

The two engines are **not unified**: a given rule is enforced by exactly one of
them, and the two rule sets are maintained separately. There is no shared source
of truth for "the Nette style" — it is the *union* of what both engines do. When
you change or add a style rule you must decide which engine owns it and edit that
engine's preset tree; adding a version rule usually means editing **both** trees
(e.g. `preset-fixer/php80.php` *and* `preset-sniffer/php80.xml`).

Both engines run in both modes. `check` is dry-run for both; `fix` lets both
rewrite. A run "passes" only if both engines pass.

## Preset resolution: two mirrored trees

Presets come in two parallel trees keyed by an **identical name**:

- `preset-fixer/<name>.php` (PHP CS Fixer `Config`)
- `preset-sniffer/<name>.xml` (PHP_CodeSniffer ruleset)

`Checker` resolves `<name>` once and looks it up in each tree independently.

- **Auto-detection** (`detectPhpVersion` + `derivePresetFromVersion`): read the
  *consumer* project's `composer.json` `require.php`, extract `X.Y`, then pick the
  **highest `phpXX` preset ≤ that version**. `--preset` overrides detection.
- **Trap — one-sided presets are silent no-ops.** If a preset name resolves in
  only one tree, the *other* engine returns success without doing anything
  (`Checker::runFixer`/`runSniffer` `return true` when their file is missing).
  A preset that exists only as `.xml` therefore skips the fixer entirely, and
  vice versa — no error is raised. Keep the trees in lockstep.

## How the file list reaches each engine (asymmetric)

**Both engines run as separate PHP subprocesses** (`Checker` `passthru`s
`PHP_BINARY … php-cs-fixer` / `… phpcs`). So `preset-fixer/base.php` executes in a
*different process* than `Checker`, and no PHP objects can cross that boundary —
everything the orchestrator hands an engine travels as a **file or an env var**.
That is the reason for the two couplings below (file list via file, CLI fixer
override via `NCS_CONFIG_FILE_PHP`), not an arbitrary choice.

`Checker::setPaths()` writes one `filelist.tmp` (newline-separated absolute
paths), then each engine consumes it by a **different mechanism** — this is the
non-obvious coupling:

- **Sniffer**: passed explicitly via `--file-list=filelist.tmp`.
- **Fixer**: `preset-fixer/base.php` itself reads `../filelist.tmp` at
  config-load time and feeds it to the `Finder`. Nothing on the fixer command
  line mentions the files.

Consequence: `setPaths()` **must** run before either engine. If `filelist.tmp` is
absent, the sniffer gets no files and the fixer's config throws while loading.

`setPaths()` also applies a **`@phpVersion` filter**: a file annotated with a PHP
version higher than the running interpreter is dropped from the list for *both*
engines (used for version-specific test fixtures). Excluded dir patterns live in
`Checker::IgnoredPaths` (`fixtures.*`, `expected`, `temp`, `tmp`, `vendor`).

## Fixer preset chain and the shared-scope override

The `.php` presets form a `require` chain, newest wraps oldest:

```
php85.php → … → php81.php → php80.php → base.php → (common/*.php)
```

- **`base.php` configures the engine, not the rules**: registers the four custom
  Nette fixers + `PhpCsFixerCustomFixers`, sets tab indent, `PHP_EOL`, risky
  allowed, the finder — then returns a `Config` whose rules are `[]`. The actual
  rules are supplied by the version preset.
- **`php80.php`** loads `base.php`, merges every `common/*.php` rule set, and
  produces the real rule array. `php81+` each load the previous version and add
  that version's `@PHP8xNMigration` set.
- **Shared-scope override (fragile, load-bearing).** `base.php` discovers project
  overrides — `ncs.php` (walked up from CWD) and the CLI file in
  `$_ENV`/`getenv('NCS_CONFIG_FILE_PHP')` — into a local `$customRules`. Because
  `require` shares variable scope, that `$customRules` is visible in `php80.php`,
  which folds it into the final merge. Wrapping any preset in a function, or
  renaming the variable, silently drops all project/CLI overrides.
- **Merge operator differs by layer, deliberately:**
  - `php80.php`: `array_merge($versionDefaults, $commonRules, $customRules, $enforced)` —
    right wins, so **project/CLI overrides have the highest precedence**, then
    common rules, then version defaults (e.g. `void_return => false`). `$enforced` is
    the one exception, a short list a project must not override; see
    `Nette/ordered_imports` below for why it holds `'ordered_imports' => false`.
  - `php81+`: `$migration + $config->getRules()` — the `+` union keeps
    **left/earlier** keys; safe only because migration keys are new. Do not swap
    `+` and `array_merge` when editing presets; they resolve conflicts opposite
    ways.

### Custom fixers: the "disable stock, enable Nette" pattern

`common/Nette.php` turns off the upstream fixer and turns on the Nette fork for
the same concern:

```
'braces_position' => false,      'Nette/braces_position' => true,
'statement_indentation' => false,'Nette/statement_indentation' => [...],
'method_argument_space' => false,'Nette/method_argument_space' => [...],
'modifier_keywords' => false,    'Nette/class_and_trait_visibility_required' => true,
```

`common/replaces.php` does the same for a *third-party* fixer:

```
PhpCsFixerCustomFixers\Fixer\NoLeadingSlashInGlobalNamespaceFixer::name() => false,
'Nette/no_leading_slash_in_global_namespace' => true,
```

So the six `src/Fixer/*` classes *shadow* their upstream equivalents. Facts
worth carrying:

- **Ordering is by `getPriority()`, and it matters.** `method_argument_space`
  (30) runs early; `braces_position` (-2) before `statement_indentation` (-3) —
  indentation must run *after* braces are placed. Keep those relative priorities.
- **`MethodArgumentSpaceFixer` only touches CALLS, never declarations.** It is a
  fork of the stock fixer gated on `isGivenKind(T_STRING)` (~line 165); multiline
  *declaration* formatting is intentionally out of its scope. If declaration
  spacing looks unenforced, this is why.
- **`NoLeadingSlashInGlobalNamespaceFixer` exists for one guard the original lacks.**
  Upstream decides purely from the *preceding* token, so in a file with no `namespace`
  declaration it turns `new \Foo\Bar` into `new Foo\Bar` even when `use Foo\Foo;` makes
  `Foo` an alias — the name then resolves to `Foo\Foo\Bar` and the code breaks at
  runtime with no fixer, sniffer or PHPStan complaint. The fork collects the file's
  class imports and keeps the slash whenever the first segment of the name matches one
  (case-insensitively). Single-segment names are equally exposed (`use Foo\Exception;`
  shadows `\Exception`), so the check is on the segment, not on the name's arity.
- **`OrderedImportsFixer` exists because the stock one corrupts comma-separated
  imports.** `single_import_per_statement` is off (Nette groups `use function` /
  `use const`), and upstream `setNewOrder()` keeps the original statement prefixes in
  place and pours the sorted names back into them. With mixed types the names then leak
  across the boundary: `use const A, B;` above `use function c, d;` comes back as
  `use function c, d, A;` / `use function B;`, and a class statement can end up holding
  a function. The fork regenerates the whole block instead, so a name can never change
  its import type. It keeps each type's *shape* (the number of statements and how many
  names each held), so it only reorders and never merges or splits what the author
  wrote. **A comment anywhere in the block makes the whole block untouchable**, including
  one behind the last semicolon: rewriting the block would drop it, and reordering around
  it would silently reattach it to an import it never described. `php80.php` therefore
  also *enforces* `'ordered_imports' => false` after merging `$customRules` — a project
  re-enabling the stock fixer would get both, at the same priority `-30` and in undefined
  order, and the stock one would corrupt the result the fork just produced.
- **`ClassAndTraitVisibilityRequiredFixer` wraps the stock fixer via Reflection.**
  The upstream `VisibilityRequiredFixer` and its `applyFix()` are `final`, so it
  can't be subclassed; the fork holds an instance and reflection-invokes
  `applyFix`, delegating definition/priority/config. It only exists to add the
  `Nette/` name prefix and narrow the candidate set to classes/traits.

## Sniffer config layering and the `phpDD` gate

`Checker::runSniffer()` only builds a composite ruleset **when the preset name
matches `~php(\d)(\d)~`**. Inside that gate:

- It sets `--runtime-set php_version 80X00` from the preset digits.
- It builds a **wrapper ruleset** referencing, in order:
  `preset → project ncs.xml → CLI --config-file *.xml`. Later refs override
  earlier ones (phpcs semantics), so CLI beats project beats preset.
- `$presets/` tokens inside a project/CLI ruleset are rewritten to the absolute
  `preset-sniffer/` path in a **temp copy** (`ruleset-*.tmp.xml`); the source file
  is never modified.

**Trap:** a non-`phpDD` preset name skips this whole block — no `php_version`,
**and project `ncs.xml` / `--config-file *.xml` are ignored**. Custom-named
presets thus can't pick up project sniffer overrides.

Temp artefacts (`filelist.tmp`, `ruleset-*.tmp.xml`) are removed by
`Checker::cleanup()`, which the SIGINT/Ctrl+C handler in `run.php` also calls.

### Where custom sniffs are actually registered (the near-empty `ruleset.xml` lies)

- `src/NetteCodingStandard/ruleset.xml` is **vestigial** (only a description); it
  is *not* the chain terminus and registers nothing.
- The sniffer chain tail is `phpXX.xml → Nette.xml`. `Nette.xml` pulls in the
  custom `FunctionSpacingSniff` **by relative file path**
  (`../src/NetteCodingStandard/Sniffs/WhiteSpace/FunctionSpacingSniff.php`);
  phpcs derives its code `NetteCodingStandard.WhiteSpace.FunctionSpacing` from the
  `<Standard>/Sniffs/<Category>/<Name>Sniff.php` path convention.
- **`OptimizeGlobalCallsSniff` is not in the default `Nette.xml`.** It ships only
  in the optional `optimize-fn.xml` preset; the test runner registers it via
  `<config name="installed_paths">` pointing at `src/NetteCodingStandard`.
- **`optimize-fn` has no fixer preset, so running it skips PHP CS Fixer entirely**
  (`Checker::runFixer()` prints "has no fixer rules, skipped"). The sniff therefore
  cannot lean on `Nette/ordered_imports` to place its output: `findInsertionPointInfo()`
  anchors a new block to the last statement it may follow, so `use function` lands above
  an existing `use const` (and above it, if constants are the only imports so far).
  It only places new blocks; reordering statements that already exist is the fixer's job.

### PHPCS 4 / PHP 8 token compatibility

Custom sniffs must handle both tokenizations of a fully-qualified name: PHP 8.0+
emits a single `T_NAME_FULLY_QUALIFIED`, older tokenizers emit
`T_NS_SEPARATOR` + `T_STRING`. `OptimizeGlobalCallsSniff` branches on both.

## Exit-code semantics (per engine, per mode)

The success predicate is not uniform:

- **Fixer**: success ⇔ exit `0`.
- **Sniffer dry (`phpcs`)**: success ⇔ exit `0`.
- **Sniffer fix (`phpcbf`)**: success ⇔ exit `0` **or `1`** — `phpcbf` returns
  `1` precisely when it *did* fix something, which is a success here.
- `-s` (show sniff codes) is only meaningful for `phpcs`; under `phpcbf` there is
  no per-violation report.

## Navigation map

| Concern | Where |
|---|---|
| CLI parsing, project-root & preset detection, signals | `run.php` |
| Orchestration, file list, wrapper ruleset, cleanup | `src/Checker.php` |
| Fixer engine setup + override discovery | `preset-fixer/base.php` |
| Fixer rule sets | `preset-fixer/{php8N.php, common/*.php, clean-code.php, types.php}` |
| Custom fixers (shadow upstream) | `src/Fixer/*` |
| Sniffer rule sets | `preset-sniffer/{php8N.xml, Nette.xml, clean-code.xml, types.xml, optimize-fn.xml}` |
| Custom sniffs | `src/NetteCodingStandard/Sniffs/**` |
| Sniff test harness (isolated per-sniff ruleset) | `tests/SniffTestRunner.phpt`, `tests/fixtures/*.inc[.expected]` |
| Fixer test harness (isolated per-rule config) | `tests/FixerTestRunner.phpt`, `tests/fixtures.fixer/*.inc[.expected]` |

Project-side extension points: `ncs.php` (fixer overrides, highest precedence),
`ncs.xml` (sniffer overrides, needs no preset ref — the wrapper adds it), and
`--config-file *.php|*.xml` layered on top. See README for user-facing usage.

**Discovery is asymmetric between the two.** `ncs.php` is found by walking up from
the *current working directory* looking for that filename (`base.php`). `ncs.xml`
is looked for **only at the detected project root** (`projectDir . '/ncs.xml'`,
the composer.json location). When CWD ≠ project root, the two overrides can
resolve to different directories — or one silently not be found.
