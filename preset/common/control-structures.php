<?php

declare(strict_types=1);


return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$services = $containerConfigurator->services();

	// Control Statements and Language Construct

	$services->set(PhpCsFixer\Fixer\ControlStructure\NoAlternativeSyntaxFixer::class);

	// Calling `unset` on multiple items should be done in one call.
	$services->set(PhpCsFixer\Fixer\LanguageConstruct\CombineConsecutiveUnsetsFixer::class);

	// Replace all `<>` with `!=`.
	$services->set(PhpCsFixer\Fixer\Operator\StandardizeNotEqualsFixer::class);

	// Include/Require and file path should be divided with a single space. File path should not be placed under brackets.
	$services->set(PhpCsFixer\Fixer\ControlStructure\IncludeFixer::class);

	$services->set(SlevomatCodingStandard\Sniffs\ControlStructures\RequireShortTernaryOperatorSniff::class);

	$services->set(SlevomatCodingStandard\Sniffs\Operators\RequireCombinedAssignmentOperatorSniff::class);

	// checks and fixes language construct used with parentheses.
	$services->set(SlevomatCodingStandard\Sniffs\ControlStructures\LanguageConstructWithParenthesesSniff::class);

	// Reports new with useless parentheses.
	$services->set(SlevomatCodingStandard\Sniffs\ControlStructures\NewWithoutParenthesesSniff::class);

	// Ternary operator has to be reformatted to more lines when the line length exceeds the given limit.
	$services->set(Nette\SlevomatCodingStandard\Sniffs\ControlStructures\RequireMultiLineTernaryOperatorSniff::class)
		->property('lineLengthLimit', 90)
		->property('expressionsMinLength', 20);

	// Enforces conditions of if, elseif, while and do-while with one or more boolean operators to be splitted to more lines so each condition part is on its own line
	$services->set(SlevomatCodingStandard\Sniffs\ControlStructures\RequireMultiLineConditionSniff::class);

	// Inside a classy element "self" should be preferred to the class name itself.
	$services->set(PhpCsFixer\Fixer\ClassNotation\SelfAccessorFixer::class);

	// Class names should be referenced via ::class constant when possible
	$services->set(SlevomatCodingStandard\Sniffs\Classes\ModernClassNameReferenceSniff::class);

	// Function defined by PHP should be called using the correct casing
	$services->set(PhpCsFixer\Fixer\Casing\NativeFunctionCasingFixer::class);

	// PHP keywords must be in lower case
	$services->set(PhpCsFixer\Fixer\Casing\LowercaseKeywordsFixer::class);

	// The PHP constants `true`, `false`, and `null` MUST be in lower case
	$services->set(PhpCsFixer\Fixer\Casing\LowercaseConstantsFixer::class);

	// Enforces using shorthand scalar typehint variants in phpDocs: `int` instead of `integer` and `bool` instead of `boolean`
	$services->set(SlevomatCodingStandard\Sniffs\TypeHints\LongTypeHintsSniff::class);

	$services->set(SlevomatCodingStandard\Sniffs\TypeHints\NullTypeHintOnLastPositionSniff::class);

	// Cast should be written in lower case.
	$services->set(PhpCsFixer\Fixer\CastNotation\LowercaseCastFixer::class);

	// Replaces `intval`, `floatval`, `doubleval`, `strval` and `boolval` function calls with according type casting operator
	$services->set(PhpCsFixer\Fixer\CastNotation\ModernizeTypesCastingFixer::class);

	// Short cast `bool` using double exclamation mark should not be used
	$services->set(PhpCsFixer\Fixer\CastNotation\NoShortBoolCastFixer::class);

	// Cast `(boolean)` and `(integer)` should be written as `(bool)` and `(int)`, `(double)` and `(real)` as `(float)`
	$services->set(PhpCsFixer\Fixer\CastNotation\ShortScalarCastFixer::class);

	// The keyword `elseif` should be used instead of `else if` so that all control keywords look like single words.
	$services->set(PhpCsFixer\Fixer\ControlStructure\ElseifFixer::class);

	// Remove useless semicolon statements
	$services->set(PhpCsFixer\Fixer\Semicolon\NoEmptyStatementFixer::class);

	$services->set(PhpCsFixer\Fixer\ControlStructure\NoUnneededCurlyBracesFixer::class);

	// Remove trailing commas in list() calls.
	$services->set(PhpCsFixer\Fixer\ControlStructure\NoTrailingCommaInListCallFixer::class);

	// Removes unneeded parentheses around control statements.
	$services->set(PhpCsFixer\Fixer\ControlStructure\NoUnneededControlParenthesesFixer::class);

	// A case should be followed by a colon and not a semicolon.
	$services->set(PhpCsFixer\Fixer\ControlStructure\SwitchCaseSemicolonToColonFixer::class);

	// The structure body must be indented once.
	// The closing brace must be on the next line after the body.
	// There should not be more than one statement per line.
	$services->set(Nette\CodingStandard\Fixer\Basic\BracesFixer::class)
		->call('configure', [[
			'allow_single_line_closure' => true,
		]]);

	// changes if (1 === $cond) to if ($cond === 1)
	$services->set(SlevomatCodingStandard\Sniffs\ControlStructures\DisallowYodaComparisonSniff::class);

	$services->set(PhpCsFixer\Fixer\ControlStructure\NoBreakCommentFixer::class)
		->call('configure', [[
			'comment_text' => 'break omitted',
		]]);

	// Increment and decrement operators should be used if possible.
	$services->set(PhpCsFixer\Fixer\Operator\StandardizeIncrementFixer::class);

	// Magic constants should be referred to using the correct casing.
	$services->set(PhpCsFixer\Fixer\Casing\MagicConstantCasingFixer::class);

	// DEAD CODE

	// Looks for useless parameter default value.
	$services->set(SlevomatCodingStandard\Sniffs\Functions\UselessParameterDefaultValueSniff::class);

	// This sniff finds unreachable catch blocks
	$services->set(SlevomatCodingStandard\Sniffs\Exceptions\DeadCatchSniff::class);
};
