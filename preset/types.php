<?php

declare(strict_types=1);

return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$containerConfigurator->import(__DIR__ . '/common/parameters.php');

	$services = $containerConfigurator->services();

	// Checks for missing parameter typehints in case they can be declared natively
	$services->set(SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSniff::class);

	// Checks for missing return typehints in case they can be declared natively
	$services->set(SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSniff::class);

	// Checks for missing property typehints in case they can be declared natively
	if (PHP_MAJOR_VERSION < 8) {
		$services->set(SlevomatCodingStandard\Sniffs\TypeHints\PropertyTypeHintSniff::class);
	}

	$services->set(PhpCsFixer\Fixer\FunctionNotation\VoidReturnFixer::class);

	// reformat
	$services->set(SlevomatCodingStandard\Sniffs\Commenting\UselessFunctionDocCommentSniff::class);
};
