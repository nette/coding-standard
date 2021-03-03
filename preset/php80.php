<?php

declare(strict_types=1);

return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$containerConfigurator->import(__DIR__ . '/php74.php');
	$containerConfigurator->import(__DIR__ . '/../vendor/symplify/easy-coding-standard/config/set/php80-migration-risky.php');

	$services = $containerConfigurator->services();
};
