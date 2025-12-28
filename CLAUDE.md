# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Nette Coding Standard** is a unified code checker and fixer tool that enforces the [Nette Framework Coding Standard](https://doc.nette.org/en/contributing/coding-standard). It orchestrates two industry-standard tools:
- **PHP CS Fixer** - for automated code fixing
- **PHP CodeSniffer** - for static analysis

The tool supports PHP versions 8.0 through 8.5 with incremental, version-specific presets.

## Essential Commands

### Development Workflow

```bash
# Check code against coding standard (dry-run, no changes)
./ecs check src tests

# Fix code violations automatically
./ecs fix src tests

# Check with specific PHP version preset
./ecs check src tests --preset php81

# Check without paths (defaults to src/ and tests/ if they exist)
./ecs check

# Get help
./ecs --help
```

### Installation

The tool is installed globally and used from the install directory:

```bash
# Install to /nette-cs directory
composer create-project nette/coding-standard /nette-cs

# Use from anywhere
/nette-cs/ecs check src tests --preset php81
```

### Testing

```bash
# Run sniff tests (OptimizeGlobalCallsSniff)
composer tester
# or
vendor/bin/tester tests -s
```

### Testing Custom Rules

Use the `examples/` directory to validate custom fixers and sniffs:

```bash
# Test against valid examples
./ecs check examples/ValidClass.php
./ecs check examples/ValidConstructs.php

# Test against invalid examples (should report violations)
./ecs check examples/InvalidConstructs.php
```

## Architecture Overview

### Entry Points and Execution Flow

```
ecs (bash wrapper)
  └─> run.php (argument parsing, project detection)
      └─> Checker class (orchestration)
          ├─> setPaths() - creates temporary file list (filelist.tmp)
          ├─> runFixer() - executes PHP CS Fixer
          └─> runSniffer() - executes PHP CodeSniffer
```

**Key files:**
- `ecs` - Bash script entry point, delegates to `run.php`
- `run.php` (182 lines) - Main orchestrator:
  - Parses CLI arguments (`check`, `fix`, `--preset`, paths)
  - Finds project root by locating `composer.json`
  - Auto-detects PHP version from `composer.json` if no preset specified
  - Handles signal interruption (SIGINT)
  - Returns appropriate exit codes for CI/CD
- `src/Checker.php` (182 lines) - Core logic:
  - Manages temporary file list creation with PHP version filtering (`@phpVersion` annotations)
  - Executes PHP CS Fixer with proper php.ini handling
  - Executes PHP CodeSniffer (phpcs/phpcbf) with parallel processing (--parallel=10)
  - Coordinates both tools with consistent configuration

### Custom Implementations Deep Dive

#### Custom PHP CS Fixers (src/Fixer/)

**1. BracesPositionFixer.php (449 lines)**
- Most complex custom fixer
- Enforces Nette-specific brace positioning rules
- Key feature: `next_line_unless_newline_at_signature_end` mode
  - If method/function signature ends with newline (e.g., after return type), brace stays on same line
  - Otherwise, brace goes to next line
- Handles: classes, functions, anonymous functions, control structures, anonymous classes
- Configuration options:
  ```php
  'Nette/braces_position' => [
      'functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
      'classes_opening_brace' => 'next_line_unless_newline_at_signature_end',
      'anonymous_functions_opening_brace' => 'same_line',
      'control_structures_opening_brace' => 'same_line',
      'allow_single_line_anonymous_functions' => true,
      'allow_single_line_empty_anonymous_classes' => true,
  ]
  ```
- Smart comment handling: moves braces around comments when needed
- Priority: -2 (runs before StatementIndentationFixer)

**2. StatementIndentationFixer.php (842 lines)**
- Most complex fixer in the entire codebase
- Handles all indentation logic including nested structures
- Configuration: `stick_comment_to_next_continuous_control_statement`
  - When true: comment before `elseif` is treated as part of `elseif` block
  - When false: comment is treated as part of previous `if` block
- Manages scopes with stack-based approach:
  - Block scopes (curly braces, parentheses, arrays)
  - Block signature scopes (function/class headers)
  - Statement scopes (individual statements)
- Special handling for:
  - Alternative syntax (if/endif, foreach/endforeach)
  - Case blocks in switch statements
  - Control structures without braces
  - Multi-line comments
  - Property declarations
- Priority: -3 (runs after BracesPositionFixer, before HeredocIndentationFixer)

**3. MethodArgumentSpaceFixer.php (510 lines)**
- Modified version of PHP CS Fixer's standard MethodArgumentSpaceFixer
- **CRITICAL LIMITATION**: Only processes T_STRING (function calls), not T_FUNCTION declarations
  - Line 165: `if ($meaningfulTokenBeforeParenthesis->isGivenKind(T_STRING))`
  - This means it ONLY fixes function/method calls, not declarations
- Configuration: `on_multiline: ensure_fully_multiline`
  - Forces each argument on separate line when multiline
- Handles:
  - Spacing after commas
  - Multiline argument lists
  - Heredoc/nowdoc in arguments
  - Attribute placement on arguments
- Priority: 30 (runs before ArrayIndentationFixer and StatementIndentationFixer)

**4. ClassAndTraitVisibilityRequiredFixer.php (79 lines)**
- Clever wrapper around PHP CS Fixer's VisibilityRequiredFixer
- Uses PHP Reflection to call private `applyFix()` method
- Reason: VisibilityRequiredFixer is final and can't be extended normally
- Adds 'Nette/' prefix to name for namespacing

#### Custom PHP CodeSniffer Sniffs

**1. FunctionSpacingSniff.php (387 lines)**
- Enforces blank lines between methods/functions
- Dynamic spacing rules:
  - **2 blank lines** between regular methods in classes
  - **1 blank line** between methods in interfaces
  - **1 blank line** after trait `use` statements (line 281-283)
  - **0 blank lines** before first method (`spacingBeforeFirst`)
  - **0 blank lines** after last method (`spacingAfterLast`)
- Respects ruleset property inheritance:
  - If only `spacing` is set in ruleset, uses it for all spacing properties
- Auto-fixable: adds or removes blank lines as needed
- Handles:
  - Function attributes (`#[Attribute]`)
  - PHPDoc comments
  - Inline comments after closing braces
  - Interface methods (abstract methods without body)

**2. OptimizeGlobalCallsSniff.php (src/NetteCodingStandard/Sniffs/Namespaces/)**
- Optimizes global function and constant calls by generating grouped `use function`/`use const` statements
- PHP resolves `strlen()` in a namespace by first checking `My\App\strlen` before falling back to global; explicit import enables compiler optimizations (special opcodes for functions like `strlen`, `count`, `in_array`, `sprintf`)
- Two modes:
  - `optimizedFunctionsOnly: true` (default) - only imports compiler-optimized functions from `zend_compile.c`
  - `optimizedFunctionsOnly: false` - imports ALL global functions and constants
- Configurable `ignoredFunctions` and `ignoredConstants` (supports wildcard patterns like `E_*`, `CURL*`)
- Auto-fixable: adds grouped `use` statements, removes backslash prefixes (`\strlen()` → `strlen()`), cleans up unused imports
- Handles both `T_NAME_FULLY_QUALIFIED` (PHPCS 4.x / PHP 8.0+) and old-style `T_NS_SEPARATOR` + `T_STRING` tokens
- Skips files without namespace, method calls, function declarations
- Available as separate preset `optimize-fn.xml` (not included in base Nette.xml)

### Test Infrastructure

**tests/SniffTestRunner.phpt:**
- Test runner for sniff testing using Nette Tester
- Creates isolated PHPCS rulesets per test with sniff properties from JSON comments
- Fixture files in `tests/fixtures/`:
  - `.inc` files contain input code with optional JSON config: `<?php // {"optimizedFunctionsOnly": false}`
  - `.inc.expected` files contain expected output after phpcbf fix
  - If no `.expected` file exists, the input should remain unchanged
- Properties from JSON comment are injected into the ruleset XML `<properties>` element
- Uses `<config name="installed_paths">` to register custom sniffs
- phpcbf exit codes: 0 = nothing to fix, 1 = fixed successfully (both are success), 2+ = error

### Preset System Architecture

#### PHP CS Fixer Presets (preset-fixer/)

**Preset Loading Chain:**
```
php85.php → php84.php → php83.php → php82.php → php81.php → php80.php → base.php
                                                                           ↓
                                                                    common/Nette.php
                                                                    common/replaces.php
```

**base.php:**
- Registers 4 custom fixers (Nette namespace)
- Registers PhpCsFixerCustomFixers (external library)
- Loads `filelist.tmp` (created by Checker)
- Sets tab indentation and PHP_EOL line endings
- Enables risky rules
- Supports custom `ncs.php` in project root for rule overrides
- Returns empty ruleset (rules added by specific presets)

**php80.php:**
- Loads `base.php`
- Merges all `common/*.php` rules
- Disables `void_return` fixer (PHP 8.0 doesn't require void declarations)
- Merge order: specific rules → common rules → custom rules

**php81-85.php:**
- Each loads previous version
- Adds version-specific migration ruleset:
  - php81: `@PHP8x1Migration`
  - php82: `@PHP8x2Migration`
  - php83: `@PHP8x3Migration`
  - php84: `@PHP8x4Migration`
  - php85: `@PHP8x5Migration`
- Uses `+` operator for array merge (keeps earlier keys)

**common/Nette.php (220 lines):**
Core Nette rules based on @PSR12:
- Overrides PSR-12 defaults:
  - `new_with_parentheses: false` - allows `new stdClass` without ()
  - `single_line_after_imports: false` - Nette uses 2 blank lines
  - `blank_line_between_import_groups: false`
  - `linebreak_after_opening_tag: false` + `blank_line_after_opening_tag: false` - allows `<?php declare(strict_types=1);` on one line
  - Arrow function spacing: `closure_fn_spacing: 'none'` → `fn($a) => $b`
- Custom Nette fixers:
  - `braces_position: false` + `Nette/braces_position: true`
  - `statement_indentation: false` + `Nette/statement_indentation`
  - `method_argument_space: false` + `Nette/method_argument_space`
  - `modifier_keywords: false` + `Nette/class_and_trait_visibility_required`
- Whitespace rules: concat_space, cast_spaces, no_spaces_around_offset
- Control structures: no_alternative_syntax, standardize_not_equals
- Arrays: array_syntax: 'short', trailing_comma_in_multiline
- Strings: single_quote, heredoc_to_nowdoc
- Classes: ordered_class_elements, no_null_property_initialization
- PHPDoc: phpdoc_trim, no_empty_phpdoc
- Ternary: ternary_to_elvis_operator, nullable_type_declaration_for_default_null_value

**common/replaces.php (28 lines):**
Best practices and replacements:
- `dir_constant: true` - use `__DIR__` instead of `dirname(__FILE__)`
- `no_alias_functions: true` - use `implode()` not `join()`
- `strict_param: true` - enforce strict mode in functions like `in_array()`
- `is_null: true` - replace with `null === $var`
- PhpCsFixerCustomFixers rules:
  - Comment out debug functions: print_r, var_dump, var_export, dump
  - No leading slash in global namespace

**clean-code.php:**
Additional strict rules:
- `strict_comparison: true` - enforce === instead of ==
- `no_useless_else: true`
- `final_internal_class: true`
- `no_unset_on_property: true` - use `= null` instead

**types.php:**
Currently empty, reserved for type-related rules

#### PHP CodeSniffer Rulesets (preset-sniffer/)

**Preset Loading Chain:**
```
php85.xml → php84.xml → php83.xml → php82.xml → php81.xml → php80.xml → Nette.xml
                                                                           ↓
                                                                    src/NetteCodingStandard/ruleset.xml
```

**Nette.xml (329 lines):**
Massive ruleset with 50+ rules from multiple sources:

**Namespace Rules (Slevomat):**
- DisallowGroupUse - no `use Foo\{ClassA, ClassB}`
- UseDoesNotStartWithBackslash - no `use \Foo`
- UnusedUses - detects unused imports (with annotation search)
  - Ignores Nette-specific annotations: @persistent, @crossOrigin, @inject
- UselessAlias, UseFromSameNamespace

**Whitespace Rules:**
- FunctionSpacing (custom): 2 blank lines between methods, 0 before first/after last
- PropertySpacing, ConstantSpacing, TraitUseSpacing
- ParameterTypeHintSpacing, ReturnTypeHintSpacing
- ArrowFunctionDeclaration: `spacesCountAfterKeyword: 0` → `fn($a)`

**Control Structures:**
- RequireShortTernaryOperator - enforce `?:` when possible
- RequireCombinedAssignmentOperator - `$a += 1` not `$a = $a + 1`
- LanguageConstructWithParentheses - enforce `echo()` style
- NewWithoutParentheses - enforce `new Foo` without ()
- RequireMultiLineTernaryOperator - split long ternaries (90 char limit)
- RequireMultiLineCondition - split boolean conditions to multiple lines
- DisallowYodaComparison - `$a === 1` not `1 === $a`

**Classes:**
- ModernClassNameReference - use `Foo::class` when possible
- TraitUseDeclaration - one trait per use statement
- UselessConstantTypeHint - don't document constant types
- DisallowMultiPropertyDefinition - one property per line
- RequireMultiLineMethodSignature - split long signatures

**Comments:**
- RequireOneLinePropertyDocComment - `/** @var Type */` on one line
- UselessFunctionDocComment - remove if duplicates signature
- ForbiddenAnnotations - prohibit @author, @todo, @version, etc.
- ForbiddenComments - prohibit useless comments like "Constructor."

**Dead Code:**
- UselessParameterDefaultValue - detect unused defaults
- DeadCatch - detect unreachable catch blocks

**Squiz Rules:**
- ArrayBracketSpacing, SelfMemberReference
- DocCommentAlignment, FunctionComment validation
- CastSpacing, ObjectOperatorSpacing, OperatorSpacing
- ConcatenationSpacing: `spacing: 1` → `$a . $b`

**Slevomat Advanced:**
- Arrays.TrailingArrayComma
- Attributes.RequireAttributeAfterDocComment
- Classes.ClassConstantVisibility
- Classes.EmptyLinesAroundClassBraces (0 lines after {, 0 before })
- Exceptions.ReferenceThrowableOnly
- Namespaces.AlphabeticallySortedUses
- Namespaces.ReferenceUsedNamesOnly (with `allowFallbackGlobalConstants/Functions: true`)
- ControlStructures.UselessIfConditionWithReturn
- Functions.StaticClosure (partially disabled)
- Arrays.MultiLineArrayEndBracketPlacement
- Arrays.SingleLineArrayWhitespace: `spacesAroundBrackets: 0`
- Operators.NegationOperatorSpacing: `spacesCount: 0` → `!$var`

**php80.xml (38 lines):**
PHP 8.0 specific rules:
- RequireNullCoalesceOperator - use `??` when possible
- RequireTrailingCommaInCall - trailing commas in function calls
- RequireNullCoalesceEqualOperator - use `??=`
- RequireArrowFunction - use arrow functions when appropriate
- RequireNumericLiteralSeparator - use `1_000_000` (min 7 digits before decimal)
- ModernClassNameReference: `enableOnObjects: true` - also on objects
- RequireTrailingCommaInDeclaration - trailing commas in declarations

**php81-85.xml:**
- php81: sets `php_version: 80100`
- php82: sets `php_version: 80200`
- php83: sets `php_version: 80300`
- php84: sets `php_version: 80400`
- php85: sets `php_version: 80500`
- Each inherits all rules from previous version

**optimize-fn.xml:**
Optional preset for optimizing global function/constant calls:
- Disables `UnusedUses` (conflicts with grouped `use function`/`use const` imports)
- Overrides `ReferenceUsedNamesOnly` (removes `allowFallbackGlobalConstants/Functions`)
- Adds `OptimizeGlobalCalls` sniff with:
  - `optimizedFunctionsOnly: true` - only compiler-optimized functions
  - Ignored functions: `dump`, `var_dump`, `print_r`, `error_get_last`, `trigger_error`, `debug_backtrace`
  - Ignored constants: `E_*`, `INFO_*`, `CURL*`, `DEBUG_BACKTRACE_IGNORE_ARGS`

**clean-code.xml:**
- RequireTernaryOperator
- DisallowDirectMagicInvokeCall
- SuperfluousAbstractClassNaming - no `AbstractFoo` prefixes
- SuperfluousErrorNaming - no `FooError` suffixes
- SuperfluousInterfaceNaming - no `IFoo` or `FooInterface` naming
- SuperfluousTraitNaming - no `FooTrait` suffixes

**types.xml:**
Type hint enforcement:
- ParameterTypeHint, PropertyTypeHint, ReturnTypeHint
  - `traversableTypeHints: ['Traversable']`
- UselessFunctionDocComment (for types)
- NullableTypeForNullDefaultValue
- UnionTypeHintFormat:
  - `withSpaces: no` → `int|string` not `int | string`
  - `shortNullable: yes` → `?int` not `int|null`
  - `nullPosition: last` → `string|int|null`

### Project Customization

Projects can override rules in two ways:

**1. Custom PHP CS Fixer Rules (ncs.php):**
Create `ncs.php` in project root:
```php
<?php
return [
    'strict_comparison' => false, // disable strict comparison
    'concat_space' => ['spacing' => 'none'], // override concat spacing
];
```
These rules are merged with preset rules (custom rules take precedence).

**2. Custom PHP CodeSniffer Ruleset (ncs.xml):**
Create `ncs.xml` in project root:
```xml
<?xml version="1.0"?>
<ruleset name="MyProject">
    <rule ref="$presets/php81.xml"/>

    <!-- Optional: include optimize-fn preset for use function/const imports -->
    <rule ref="$presets/optimize-fn.xml"/>

    <!-- Exclude specific rules -->
    <exclude name="SlevomatCodingStandard.TypeHints.ReturnTypeHint"/>

    <!-- Override rule properties -->
    <rule ref="NetteCodingStandard.WhiteSpace.FunctionSpacing">
        <properties>
            <property name="spacing" value="1"/>
        </properties>
    </rule>
</ruleset>
```

**Important:** Checker automatically replaces `$presets/` with actual path to preset-sniffer/ directory (line 105 in Checker.php).

### File Processing and Filtering

**Finder Configuration (Checker::setPaths):**
- Matches: `*.php`, `*.phpt` files
- Excludes directories: vendor/, temp/, tmp/, fixtures.*, expected/
- Respects `@phpVersion` annotations:
  - Files with `@phpVersion 8.1` are skipped if current PHP < 8.1
  - Useful for version-specific test files

**Temporary File Management:**
- Creates `filelist.tmp` with all matched file paths
- Used by both PHP CS Fixer and PHP CodeSniffer (`--file-list`)
- Cleaned up after execution (Checker::cleanup)

### Auto-Detection Logic

**PHP Version Detection (Checker::detectPhpVersion):**
1. Reads project's `composer.json`
2. Extracts version from `require.php` (regex: `(\d+\.\d+)`)
3. Example: `"php": "^8.1"` → detects `8.1`

**Preset Selection (Checker::derivePresetFromVersion):**
1. Scans preset directory for `php*.php` or `php*.xml` files
2. Extracts versions: `php81.php` → `8.1`
3. Sorts versions descending
4. Selects highest version ≤ detected PHP version
5. Example: detected 8.2 with available 80, 81, 82, 83 → selects `php82`

### Execution Details

**PHP CS Fixer Execution:**
```bash
php-cs-fixer fix -v [--dry-run] --config=preset-fixer/php81.php
```
- Inherits php.ini from current PHP runtime
- Verbose output (-v)
- Uses config file for all settings
- Returns exit code 0 on success

**PHP CodeSniffer Execution:**
```bash
phpcs -s -p --colors --extensions=php,phpt \
  --runtime-set php_version 80100 \
  --runtime-set ignore_warnings_on_exit true \
  --no-cache --parallel=10 \
  --standard=preset-sniffer/php81.xml \
  --file-list=filelist.tmp
```
- `-s` - show sniff codes (helps identify which rule failed)
- `-p` - show progress
- `--colors` - colored output
- `--parallel=10` - process files in parallel (10 workers)
- `--no-cache` - disable caching for consistent results
- `--runtime-set php_version` - set PHP version from preset name
- Exit codes:
  - **0** - no errors
  - **1** - errors found (dry-run) or errors fixed (fix mode)
  - **2** - fixable errors found (with --report)
  - **3** - processing errors

### Critical Limitations and Gotchas

1. **MethodArgumentSpaceFixer limitation**: Only processes function/method CALLS (T_STRING), not DECLARATIONS (T_FUNCTION). This means multiline function declarations are NOT enforced by this fixer.

2. **Preset merge order matters**:
   - PHP CS Fixer: Later rules override earlier ones
   - Use `+` for prepend merge: `$rules + $config->getRules()`

3. **Custom ruleset path replacement**: `$presets/` in ncs.xml is replaced at runtime, don't use absolute paths.

4. **File list dependency**: Both tools rely on `filelist.tmp` - if Checker fails before creating it, both tools will fail.

5. **Signal handling**: Ctrl+C is caught and triggers cleanup (removes filelist.tmp).

6. **PHPCS 4.x token types**: PHP 8.0+ tokenizes `\strlen` as `T_NAME_FULLY_QUALIFIED` (single token) instead of `T_NS_SEPARATOR` + `T_STRING`. Custom sniffs must handle both token types for compatibility.

7. **Tool versions**: PHP CS Fixer 3.93.1, PHP CodeSniffer 4.0.1.

## CI/CD Integration

Example GitHub Actions workflow:

```yaml
steps:
    - uses: actions/checkout@v2
    - uses: shivammathur/setup-php@v2
      with:
          php-version: 8.1

    - run: composer create-project nette/coding-standard temp/coding-standard
    - run: php temp/coding-standard/ecs check src tests --preset php81
```

The tool returns:
- Exit code `0` on success (no violations or all fixed)
- Non-zero exit code on violations (for CI failure)
