<?php

declare(strict_types=1);

return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$containerConfigurator->import(__DIR__ . '/common/parameters.php');

	$services = $containerConfigurator->services();

	// Requires ternary operator when possible
	$services->set(SlevomatCodingStandard\Sniffs\ControlStructures\RequireTernaryOperatorSniff::class);

	// There should not be useless `else` cases.
	$services->set(PhpCsFixer\Fixer\ControlStructure\NoUselessElseFixer::class);

	// Requires use of early exit
	/*$services->set(SlevomatCodingStandard\Sniffs\ControlStructures\EarlyExitSniff::class)
		->property('ignoreStandaloneIfInScope', true)
		->property('ignoreTrailingIfWithOneInstruction', true);*/

	// Internal classes should be `final`
	$services->set(PhpCsFixer\Fixer\ClassNotation\FinalInternalClassFixer::class);

	// Properties should be set to `null` instead of using `unset`
	$services->set(PhpCsFixer\Fixer\LanguageConstruct\NoUnsetOnPropertyFixer::class);

	$services->set(SlevomatCodingStandard\Sniffs\PHP\DisallowDirectMagicInvokeCallSniff::class);

	// naming
	$services->set(SlevomatCodingStandard\Sniffs\Classes\SuperfluousAbstractClassNamingSniff::class);
	$services->set(SlevomatCodingStandard\Sniffs\Classes\SuperfluousErrorNamingSniff::class);
	$services->set(SlevomatCodingStandard\Sniffs\Classes\SuperfluousInterfaceNamingSniff::class);
	$services->set(SlevomatCodingStandard\Sniffs\Classes\SuperfluousTraitNamingSniff::class);

	// reformat
	$services->set(Nette\CodingStandard\Fixer\Basic\BracesFixer::class)
		->call('configure', [['allow_single_line_closure' => true]]);
	$services->set(PhpCsFixer\Fixer\Whitespace\NoWhitespaceInBlankLineFixer::class);
	$services->set(PhpCsFixer\Fixer\Whitespace\NoTrailingWhitespaceFixer::class);
};
