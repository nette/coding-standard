<?php

declare(strict_types=1);


return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$services = $containerConfigurator->services();

	// use tabs over spaces
	$services->set(PHP_CodeSniffer\Standards\Generic\Sniffs\WhiteSpace\DisallowSpaceIndentSniff::class);

	// PHP code must use only UTF-8 without BOM
	$services->set(PhpCsFixer\Fixer\Basic\EncodingFixer::class);

	// <?php opening tag
	$services->set(PhpCsFixer\Fixer\PhpTag\FullOpeningTagFixer::class);

	// Ensure there is no code on the same line as the PHP open tag.
	$services->set(PhpCsFixer\Fixer\PhpTag\LinebreakAfterOpeningTagFixer::class);

	// The closing ? > tag must be omitted from files containing only PHP.
	$services->set(PhpCsFixer\Fixer\PhpTag\NoClosingTagFixer::class);

	// There must not be trailing whitespace at the end of lines.
	$services->set(PhpCsFixer\Fixer\Whitespace\NoTrailingWhitespaceFixer::class);

	// ...and at the end of blank lines.
	$services->set(PhpCsFixer\Fixer\Whitespace\NoWhitespaceInBlankLineFixer::class);

	// All files must end with a single blank line.
	$services->set(PhpCsFixer\Fixer\Whitespace\SingleBlankLineAtEofFixer::class);

	// File name should match class name if possible.
	//$services->set(PhpCsFixer\Fixer\Basic\Psr4Fixer::class);
};
