<?php

declare(strict_types=1);


return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$services = $containerConfigurator->services();

	// class element order: constants, properties, from public to private
	$services->set(PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer::class)
		->call('configure', [[
			'order' => [
				'use_trait',
				'constant',
				'constant_public',
				'constant_protected',
				'constant_private',
				'property_public',
				'property_protected',
				'property_private',
			],
		]]);

	// All Class and Trait elements should have visibility required
	$services->set(Nette\CodingStandard\Fixer\ClassNotation\ClassAndTraitVisibilityRequiredFixer::class)
		->call('configure', [[
			'elements' => ['property', 'method'],
		]]);

	// Traits

	// Prohibits multiple traits separated by commas in one use statement.
	$services->set(SlevomatCodingStandard\Sniffs\Classes\TraitUseDeclarationSniff::class);

	// Constants

	// constant names are CAPITALIZED (manuall fixing only :()
	$services->set(PHP_CodeSniffer\Standards\Generic\Sniffs\NamingConventions\UpperCaseConstantNameSniff::class);

	// Reports useless @var annotation (or whole documentation comment) for constants because the type of constant is always clear
	$services->set(SlevomatCodingStandard\Sniffs\TypeHints\UselessConstantTypeHintSniff::class);

	// Class Properties

	// Disallows multi property definition.
	$services->set(SlevomatCodingStandard\Sniffs\Classes\DisallowMultiPropertyDefinitionSniff::class);

	// Properties MUST not be explicitly initialized with `null`.
	$services->set(PhpCsFixer\Fixer\ClassNotation\NoNullPropertyInitializationFixer::class);

	// Methods

	// They must be declared in camelCase.
	$services->set(PHP_CodeSniffer\Standards\PSR1\Sniffs\Methods\CamelCapsMethodNameSniff::class);

	// In function arguments there must not be arguments with default values before non-default ones.
	$services->set(PhpCsFixer\Fixer\FunctionNotation\NoUnreachableDefaultArgumentValueFixer::class);

	// Enforces method signature to be splitted to more lines so each parameter is on its own line.
	$services->set(SlevomatCodingStandard\Sniffs\Classes\RequireMultiLineMethodSignatureSniff::class);
};
