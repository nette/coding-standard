<?php

declare(strict_types=1);


return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$services = $containerConfigurator->services();

	// There should not be any empty comments.
	$services->set(PhpCsFixer\Fixer\Comment\NoEmptyCommentFixer::class);

	// Single-line comments comments with only one line of actual content should use the `//` syntax.
	$services->set(PhpCsFixer\Fixer\Comment\SingleLineCommentStyleFixer::class)
		->call('configure', [[
			'comment_types' => ['hash'],
		]]);
};
