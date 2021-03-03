<?php

declare(strict_types=1);

return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$containerConfigurator->import(__DIR__ . '/php70.php');

	$services = $containerConfigurator->services();

	// Formatting - rules for consistent code looks

	$services->set(Nette\CodingStandard\Fixer\ClassNotation\ClassAndTraitVisibilityRequiredFixer::class)
		->call('configure', [[
			'elements' => PHP_MAJOR_VERSION < 8 ? ['const', 'property', 'method'] : ['const', 'method'],
		]]);

	// short list() syntax []
	$services->set(PhpCsFixer\Fixer\ListNotation\ListSyntaxFixer::class)
		->call('configure', [['syntax' => 'short']]);
};
