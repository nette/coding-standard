<?php

declare(strict_types=1);


return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$services = $containerConfigurator->services();

	$services->set(PhpCsFixer\Fixer\LanguageConstruct\DirConstantFixer::class);
	$services->set(PhpCsFixer\Fixer\Operator\LogicalOperatorsFixer::class);
	$services->set(PhpCsFixer\Fixer\Alias\NoAliasFunctionsFixer::class);
	$services->set(PhpCsFixer\Fixer\Alias\SetTypeToCastFixer::class);
	$services->set(PhpCsFixer\Fixer\LanguageConstruct\CombineConsecutiveIssetsFixer::class);
	$services->set(PhpCsFixer\Fixer\LanguageConstruct\CombineConsecutiveUnsetsFixer::class);
};
