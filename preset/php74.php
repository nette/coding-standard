<?php

declare(strict_types=1);

return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$containerConfigurator->import(__DIR__ . '/php73.php');

	$services = $containerConfigurator->services();

	// Requires use of null coalesce equal operator when possible
	$services->set(SlevomatCodingStandard\Sniffs\ControlStructures\RequireNullCoalesceEqualOperatorSniff::class);

	// Requires arrow functions
	$services->set(SlevomatCodingStandard\Sniffs\Functions\RequireArrowFunctionSniff::class);

	// Requires use of numeric literal separators.
	$services->set(SlevomatCodingStandard\Sniffs\Numbers\RequireNumericLiteralSeparatorSniff::class)
		->property('minDigitsBeforeDecimalPoint', 7)
		->property('minDigitsAfterDecimalPoint', 20);
};
