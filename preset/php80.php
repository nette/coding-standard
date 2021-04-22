<?php

declare(strict_types=1);

return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$containerConfigurator->import(__DIR__ . '/php74.php');

	$services = $containerConfigurator->services();

	// Class names should be referenced via ::class constant when possible
	$services->set(SlevomatCodingStandard\Sniffs\Classes\ModernClassNameReferenceSniff::class)
		->property('enableOnObjects', true);
};
