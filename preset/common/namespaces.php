<?php

declare(strict_types=1);


return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$services = $containerConfigurator->services();

	// empty line before namespace
	$services->set(PhpCsFixer\Fixer\NamespaceNotation\SingleBlankLineBeforeNamespaceFixer::class);

	// 1 Use statement per line
	$services->set(PhpCsFixer\Fixer\Import\SingleImportPerStatementFixer::class);

	// Use statements are alphabetically ordered
	$services->set(PhpCsFixer\Fixer\Import\OrderedImportsFixer::class);

	// disallow group use declarations use FooLibrary\Bar\Baz\{ ClassA, ClassB, ClassC, ClassD as Fizbo }
	$services->set(SlevomatCodingStandard\Sniffs\Namespaces\DisallowGroupUseSniff::class);

	// Disallows leading backslash in use statement: use \Foo\Bar;
	$services->set(SlevomatCodingStandard\Sniffs\Namespaces\UseDoesNotStartWithBackslashSniff::class);

	// Looks for unused imports from other namespaces.
	$services->set(SlevomatCodingStandard\Sniffs\Namespaces\UnusedUsesSniff::class)
		->property('searchAnnotations', 'yes')
		->property('ignoredAnnotationNames', ['@testCase'])
		->property('ignoredAnnotations', ['@internal']);

	$services->set(SlevomatCodingStandard\Sniffs\Namespaces\UselessAliasSniff::class);

	// Prohibits uses from the same namespace:
	$services->set(SlevomatCodingStandard\Sniffs\Namespaces\UseFromSameNamespaceSniff::class);
};
