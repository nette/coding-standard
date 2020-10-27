<?php

declare(strict_types=1);


return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$services = $containerConfigurator->services();

	// Docblocks should have the same indentation as the documented subject.
	$services->set(PhpCsFixer\Fixer\Phpdoc\PhpdocIndentFixer::class);

	// There should not be empty PHPDoc blocks.
	$services->set(PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer::class);

	// Phpdocs should start and end with content, excluding the very first and last line of the docblocks.
	$services->set(PhpCsFixer\Fixer\Phpdoc\PhpdocTrimFixer::class);

	// Require comments with single-line content to be written as one-liners
	$services->set(SlevomatCodingStandard\Sniffs\Commenting\RequireOneLinePropertyDocCommentSniff::class);

	// Checks for useless doc comments. If the native method declaration contains everything
	$services->set(SlevomatCodingStandard\Sniffs\Commenting\UselessFunctionDocCommentSniff::class);
	$services->set(SlevomatCodingStandard\Sniffs\Commenting\UselessInheritDocCommentSniff::class);

	$services->set(PhpCsFixer\Fixer\Comment\NoTrailingWhitespaceInCommentFixer::class);

	$services->set(PhpCsFixer\Fixer\Phpdoc\PhpdocTrimConsecutiveBlankLineSeparationFixer::class);

	$services->set(PhpCsFixer\Fixer\Phpdoc\PhpdocTypesFixer::class);
};
