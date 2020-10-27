<?php

declare(strict_types=1);

return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$containerConfigurator->import(__DIR__ . '/php56.php');

	$services = $containerConfigurator->services();

	// declare(strict_types=1);
	$services->set(PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer::class);

	// Enforces consistent formatting of return typehints: function foo(): ?int
	$services->set(SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSpacingSniff::class);

	// Use `null` coalescing operator `??` where possible
	$services->set(PhpCsFixer\Fixer\Operator\TernaryToNullCoalescingFixer::class);

	// Requires use of null coalesce operator when possible
	$services->set(SlevomatCodingStandard\Sniffs\ControlStructures\RequireNullCoalesceOperatorSniff::class);

	$services->set(PhpCsFixer\Fixer\FunctionNotation\CombineNestedDirnameFixer::class);

	$services->set(PhpCsFixer\Fixer\Alias\PowToExponentiationFixer::class);
};
