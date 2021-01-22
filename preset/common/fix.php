<?php

declare(strict_types=1);


return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$services = $containerConfigurator->services();

	// Removes debug statements
	$services->set(Drew\DebugStatementsFixers\Dump::class);
};
