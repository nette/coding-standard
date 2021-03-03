<?php

declare(strict_types=1);


return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$services = $containerConfigurator->services();

	// SPACES

	// Single-line whitespace before closing semicolon are prohibited
	$services->set(PhpCsFixer\Fixer\Semicolon\NoSinglelineWhitespaceBeforeSemicolonsFixer::class);

	// Fix whitespace after a semicolon
	$services->set(PhpCsFixer\Fixer\Semicolon\SpaceAfterSemicolonFixer::class);

	// Binary operators should be surrounded by at least one space.
	//$services->set(PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer::class);

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

	// A single space or none should be between cast and variable (int) $val
	$services->set(SlevomatCodingStandard\Sniffs\PHP\TypeCastSniff::class);

	// A single space or none should be between cast and variable.
	$services->set(PhpCsFixer\Fixer\CastNotation\CastSpacesFixer::class);

	// When making a method or function call, there MUST NOT be a space between the method or function name and the opening parenthesis
	$services->set(PhpCsFixer\Fixer\FunctionNotation\NoSpacesAfterFunctionNameFixer::class);

	$services->set(PhpCsFixer\Fixer\LanguageConstruct\DeclareEqualNormalizeFixer::class)
		->call('configure', [[
			'space' => 'none',
		]]);

	// LINES, INDENTATION

	// The namespace declaration line shouldn\'t contain leading whitespace
	$services->set(PhpCsFixer\Fixer\NamespaceNotation\NoLeadingNamespaceWhitespaceFixer::class);

	// Ensures all language constructs contain a single space between themselves and their content
	$services->set(PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\LanguageConstructSpacingSniff::class);

	$services->set(PhpCsFixer\Fixer\Whitespace\IndentationTypeFixer::class);

	// PROPERTY

	// Checks that there is a certain number of blank lines between properties
	if (PHP_MAJOR_VERSION < 8) {
		$services->set(SlevomatCodingStandard\Sniffs\Classes\PropertySpacingSniff::class);
	}

	if (PHP_MAJOR_VERSION < 8) {
		$services->set(SlevomatCodingStandard\Sniffs\TypeHints\PropertyTypeHintSpacingSniff::class);
	}

	// FUNCTION

	// In the argument list, there must be one space after each comma, and there must no be a space before each comma
	$services->set(Nette\PhpCsFixer\Fixer\FunctionNotation\MethodArgumentSpaceFixer::class)
		->call('configure', [[
			'on_multiline' => 'ensure_fully_multiline',
		]]);

	// This sniff checks that there are two blank lines between functions declarations and single between signatures.
	$services->set(Nette\CodingStandard\Sniffs\WhiteSpace\FunctionSpacingSniff::class)
		->property('spacingBeforeFirst', 0)
		->property('spacingAfterLast', 0);

	// Checks that there's a single space between a typehint and a parameter name and no whitespace between a nullability symbol and a typehint
	if (PHP_MAJOR_VERSION < 8) {
		$services->set(SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSpacingSniff::class);
	}
	$services->set(SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSpacingSniff::class);

	// Spaces should be properly placed in a function declaration.
	$services->set(PhpCsFixer\Fixer\FunctionNotation\FunctionDeclarationFixer::class);

	// Arrow functions formatting
	$services->set(SlevomatCodingStandard\Sniffs\Functions\ArrowFunctionDeclarationSniff::class);
		//->property('spacesCountAfterKeyword', 0); // does not work with FunctionDeclarationFixer #41

	// CLASS

	$services->set(PhpCsFixer\Fixer\ClassNotation\ClassDefinitionFixer::class);

	// Enforces configurable number of lines before first use, after last use and between two use statements.
	$services->set(SlevomatCodingStandard\Sniffs\Classes\TraitUseSpacingSniff::class)
		->property('linesCountBeforeFirstUse', 0)
		->property('linesCountAfterLastUseWhenLastInClass', 0);

	// Checks that there is a certain number of blank lines between constants.
	$services->set(SlevomatCodingStandard\Sniffs\Classes\ConstantSpacingSniff::class);
};
