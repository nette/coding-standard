<?php

declare(strict_types=1);

return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$containerConfigurator->import(__DIR__ . '/php71.php');

	$services = $containerConfigurator->services();

	// enforces trailing commas in multi-line calls
	$services->set(SlevomatCodingStandard\Sniffs\Functions\TrailingCommaInCallSniff::class);

	$services->set(PhpCsFixer\Fixer\Whitespace\HeredocIndentationFixer::class);
};
