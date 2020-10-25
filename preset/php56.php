<?php

declare(strict_types=1);

return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$parameters = $containerConfigurator->parameters();

	$parameters->set('indentation', 'tab');

	$parameters->set('file_extensions', ['php', 'phpt']);

	$parameters->set('exclude_files', [
		'fixtures/*',
		'fixtures*/*',
		'temp/*',
		'tmp/*',
	]);

	$services = $containerConfigurator->services();


	// General rules - https://nette.org/en/coding-standard#toc-general-rules

	// use tabs over spaces
	$services->set(PHP_CodeSniffer\Standards\Generic\Sniffs\WhiteSpace\DisallowSpaceIndentSniff::class);

	// PHP code must use only UTF-8 without BOM
	$services->set(PhpCsFixer\Fixer\Basic\EncodingFixer::class);

	// <?php opening tag
	$services->set(PhpCsFixer\Fixer\PhpTag\FullOpeningTagFixer::class);

	// Ensure there is no code on the same line as the PHP open tag.
	$services->set(PhpCsFixer\Fixer\PhpTag\LinebreakAfterOpeningTagFixer::class);

	// The closing ? > tag must be omitted from files containing only PHP.
	$services->set(PhpCsFixer\Fixer\PhpTag\NoClosingTagFixer::class);

	// There must not be trailing whitespace at the end of lines.
	$services->set(PhpCsFixer\Fixer\Whitespace\NoTrailingWhitespaceFixer::class);

	// ...and at the end of blank lines.
	$services->set(PhpCsFixer\Fixer\Whitespace\NoWhitespaceInBlankLineFixer::class);

	// All files must end with a single blank line.
	$services->set(PhpCsFixer\Fixer\Whitespace\SingleBlankLineAtEofFixer::class);

	// File name should match class name if possible.
	$services->set(PhpCsFixer\Fixer\Basic\Psr4Fixer::class);

	// Enforces using shorthand scalar typehint variants in phpDocs: `int` instead of `integer` and `bool` instead of `boolean`
	$services->set(SlevomatCodingStandard\Sniffs\TypeHints\LongTypeHintsSniff::class);


	// File Header - https://nette.org/en/coding-standard#toc-file-header

	// empty line before namespace
	$services->set(PhpCsFixer\Fixer\NamespaceNotation\SingleBlankLineBeforeNamespaceFixer::class);

	// 1 Use statement per line
	$services->set(PhpCsFixer\Fixer\Import\SingleImportPerStatementFixer::class);

	// Use statements are alphabetically ordered
	$services->set(PhpCsFixer\Fixer\Import\OrderedImportsFixer::class);

	// disallow group use declarations use FooLibrary\Bar\Baz\{ ClassA, ClassB, ClassC, ClassD as Fizbo }
	$services->set(SlevomatCodingStandard\Sniffs\Namespaces\DisallowGroupUseSniff::class);

	// Disallows leading backslash in use statement: use \Foo\Bar;
	$services->set(SlevomatCodingStandard\Sniffs\Namespaces\UseDoesNotStartWithBackslashSniff::class);

	// Looks for unused imports from other namespaces.
	$services->set(SlevomatCodingStandard\Sniffs\Namespaces\UnusedUsesSniff::class)
		->property('searchAnnotations', 'yes')
		->property('ignoredAnnotationNames', ['@testCase'])
		->property('ignoredAnnotations', ['@internal']);


	// Language Construct (should be placed before some other fixers)

	// Functions should be used with `$strict` param set to `true`
	$services->set(PhpCsFixer\Fixer\Strict\StrictParamFixer::class);

	// replaces is_null(parameter) expression with `null === parameter`.
	$services->set(PhpCsFixer\Fixer\LanguageConstruct\IsNullFixer::class)
		->call('configure', [['use_yoda_style' => false]]);

	// Calling `unset` on multiple items should be done in one call.
	$services->set(PhpCsFixer\Fixer\LanguageConstruct\CombineConsecutiveUnsetsFixer::class);

	// Replace all `<>` with `!=`.
	$services->set(PhpCsFixer\Fixer\Operator\StandardizeNotEqualsFixer::class);

	// Include/Require and file path should be divided with a single space. File path should not be placed under brackets.
	$services->set(PhpCsFixer\Fixer\ControlStructure\IncludeFixer::class);

	// Include/Require and file path should be divided with a single space. File path should not be placed under brackets.
	$services->set(SlevomatCodingStandard\Sniffs\ControlStructures\RequireShortTernaryOperatorSniff::class);


	// Arrays - https://nette.org/en/coding-standard#toc-arrays

	// use short array fixes
	$services->set(PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer::class)
		->call('configure', [['syntax' => 'short']]);

	// use trailing command in last array element
	$services->set(PhpCsFixer\Fixer\ArrayNotation\TrailingCommaInMultilineArrayFixer::class);

	// PHP single-line arrays should not have trailing comma.
	$services->set(PhpCsFixer\Fixer\ArrayNotation\NoTrailingCommaInSinglelineArrayFixer::class);

	// In array declaration, there MUST NOT be a whitespace before each comma.
	$services->set(PhpCsFixer\Fixer\ArrayNotation\NoWhitespaceBeforeCommaInArrayFixer::class);

	// Arrays should be formatted like function/method arguments, without leading or trailing single line space.
	$services->set(PhpCsFixer\Fixer\ArrayNotation\TrimArraySpacesFixer::class);

	// In array declaration, there MUST be a whitespace after each comma.
	$services->set(PhpCsFixer\Fixer\ArrayNotation\WhitespaceAfterCommaInArrayFixer::class);


	// Strings

	// Convert `heredoc` to `nowdoc` where possible.
	$services->set(PhpCsFixer\Fixer\StringNotation\HeredocToNowdocFixer::class);

	// Convert double quotes to single quotes for simple strings.
	$services->set(PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer::class);


	// Keywords and True/False/Null - https://nette.org/en/coding-standard#toc-keywords-and-true-false-null

	// PHP keywords must be in lower case
	$services->set(PhpCsFixer\Fixer\Casing\LowercaseKeywordsFixer::class);

	// The PHP constants `true`, `false`, and `null` MUST be in lower case
	$services->set(PhpCsFixer\Fixer\Casing\LowercaseConstantsFixer::class);


	// Method and Functions Calls - https://nette.org/en/coding-standard#toc-method-and-function-calls

	// Function defined by PHP should be called using the correct casing
	$services->set(PhpCsFixer\Fixer\Casing\NativeFunctionCasingFixer::class);

	// In the argument list, there must be one space after each comma, and there must no be a space before each comma
	$services->set(PhpCsFixer\Fixer\FunctionNotation\MethodArgumentSpaceFixer::class);

	// This sniff checks that there are two blank lines between functions declarations and single between signatures.
	$services->set(Nette\CodingStandard\Sniffs\WhiteSpace\FunctionSpacingSniff::class);


	// Classes - https://nette.org/en/coding-standard#toc-classes

	// Inside a classy element "self" should be preferred to the class name itself.
	$services->set(PhpCsFixer\Fixer\ClassNotation\SelfAccessorFixer::class);

	// class element order: constants, properties, from public to private
	$services->set(PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer::class)
		->call('configure', [[
			'order' => [
				'use_trait',
				'constant',
				'constant_public',
				'constant_protected',
				'constant_private',
				'property_public',
				'property_protected',
				'property_private',
			],
		]]);


	// Constants - https://nette.org/en/coding-standard#toc-constants

	// constant names are CAPITALIZED (manuall fixing only :()
	$services->set(PHP_CodeSniffer\Standards\Generic\Sniffs\NamingConventions\UpperCaseConstantNameSniff::class);


	// Class Properties - https://nette.org/en/coding-standard#toc-class-properties

	// There MUST NOT be more than one property declared per statement.
	$services->set(PhpCsFixer\Fixer\ClassNotation\SingleClassElementPerStatementFixer::class)
		->call('configure', [[
			'elements' => ['property'],
		]]);


	// Methods - https://nette.org/en/coding-standard#toc-methods

	// They must be declared in camelCase.
	$services->set(PHP_CodeSniffer\Standards\PSR1\Sniffs\Methods\CamelCapsMethodNameSniff::class);

	// Checks that there's a single space between a typehint and a parameter name and no whitespace between a nullability symbol and a typehint
	$services->set(SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSpacingSniff::class);

	// Spaces should be properly placed in a function declaration.
	$services->set(PhpCsFixer\Fixer\FunctionNotation\FunctionDeclarationFixer::class);

	// In function arguments there must not be arguments with default values before non-default ones.
	$services->set(PhpCsFixer\Fixer\FunctionNotation\NoUnreachableDefaultArgumentValueFixer::class);


	// Constans, Class Properties, Methods

	// All Class and Trait elements should have visibility required
	$services->set(Nette\CodingStandard\Fixer\ClassNotation\ClassAndTraitVisibilityRequiredFixer::class)
		->call('configure', [[
			'elements' => ['property', 'method'],
		]]);

	// Last property and 1st method should be separated by 2 spaces
	$services->set(Nette\CodingStandard\Fixer\ClassNotation\LastPropertyAndFirstMethodSeparationFixer::class)
		->call('configure', [['space_count' => 2]]);


	// Control Statements - https://nette.org/en/coding-standard#toc-control-statements

	// The keyword `elseif` should be used instead of `else if` so that all control keywords look like single words.
	$services->set(PhpCsFixer\Fixer\ControlStructure\ElseifFixer::class);

	// Remove useless semicolon statements.
	$services->set(PhpCsFixer\Fixer\Semicolon\NoEmptyStatementFixer::class);

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
		->call('configure', [['allow_single_line_closure' => true]]);

	// changes if (1 === $cond) to if ($cond === 1)
	$services->set(SlevomatCodingStandard\Sniffs\ControlStructures\DisallowYodaComparisonSniff::class);

	// finds unreachable catch blocks:
	$services->set(SlevomatCodingStandard\Sniffs\Exceptions\DeadCatchSniff::class);


	// Casting

	// A single space or none should be between cast and variable (int) $val
	$services->set(PhpCsFixer\Fixer\CastNotation\CastSpacesFixer::class);

	// Cast should be written in lower case.
	$services->set(PhpCsFixer\Fixer\CastNotation\LowercaseCastFixer::class);

	// Replaces `intval`, `floatval`, `doubleval`, `strval` and `boolval` function calls with according type casting operator
	$services->set(PhpCsFixer\Fixer\CastNotation\ModernizeTypesCastingFixer::class);

	// Short cast `bool` using double exclamation mark should not be used
	$services->set(PhpCsFixer\Fixer\CastNotation\NoShortBoolCastFixer::class);

	// Cast `(boolean)` and `(integer)` should be written as `(bool)` and `(int)`, `(double)` and `(real)` as `(float)`
	$services->set(PhpCsFixer\Fixer\CastNotation\ShortScalarCastFixer::class);


	// Language Whitespace

	// Binary operators should be surrounded by at least one space.
	$services->set(PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer::class);

	// Unary operators should be placed adjacent to their operands.
	$services->set(PhpCsFixer\Fixer\Operator\UnaryOperatorSpacesFixer::class);

	// No space after the opening parenthesis and before the closing parenthesis
	$services->set(PhpCsFixer\Fixer\Whitespace\NoSpacesInsideParenthesisFixer::class);

	// There MUST NOT be spaces around offset braces $a[0]
	$services->set(PhpCsFixer\Fixer\Whitespace\NoSpacesAroundOffsetFixer::class);

	// There should not be space before or after object `T_OBJECT_OPERATOR` `->`.
	$services->set(PhpCsFixer\Fixer\Operator\ObjectOperatorWithoutWhitespaceFixer::class);

	// Standardize spaces around ternary operator.
	$services->set(PhpCsFixer\Fixer\Operator\TernaryOperatorSpacesFixer::class);

	// Concatenation $a . $b should be spaced according configuration
	$services->set(PhpCsFixer\Fixer\Operator\ConcatSpaceFixer::class)
		->call('configure', [['spacing' => 'one']]);

	// Removes extra spaces between colon and case value.
	$services->set(PhpCsFixer\Fixer\ControlStructure\SwitchCaseSpaceFixer::class);


	// Comments

	// Docblocks should have the same indentation as the documented subject.
	$services->set(PhpCsFixer\Fixer\Phpdoc\PhpdocIndentFixer::class);

	// There should not be any empty comments.
	$services->set(PhpCsFixer\Fixer\Comment\NoEmptyCommentFixer::class);

	// There should not be empty PHPDoc blocks.
	$services->set(PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer::class);

	// Phpdocs should start and end with content, excluding the very first and last line of the docblocks.
	$services->set(PhpCsFixer\Fixer\Phpdoc\PhpdocTrimFixer::class);

	// Single-line comments comments with only one line of actual content should use the `//` syntax.
	$services->set(PhpCsFixer\Fixer\Comment\SingleLineCommentStyleFixer::class)
		->call('configure', [['comment_types' => ['hash']]]);

	// Require comments with single-line content to be written as one-liners
	$services->set(SlevomatCodingStandard\Sniffs\Commenting\RequireOneLinePropertyDocCommentSniff::class);



	// Properties MUST not be explicitly initialized with `null`.
	$services->set(PhpCsFixer\Fixer\ClassNotation\NoNullPropertyInitializationFixer::class);

	$services->set(PhpCsFixer\Fixer\ControlStructure\NoBreakCommentFixer::class)
		->call('configure', [['comment_text' => 'break omitted']]);
};
