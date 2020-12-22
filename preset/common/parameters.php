<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\ValueObject\Option;


return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$parameters = $containerConfigurator->parameters();

	$parameters->set(Option::INDENTATION, Option::INDENTATION_TAB);

	$parameters->set(Option::FILE_EXTENSIONS, ['php', 'phpt']);

	$paths = array_filter([
		'src',
		'tests',
	], 'is_dir');

	$parameters->set(Option::PATHS, $paths ?: ['.']);

	$parameters->set(Option::SKIP, [
		'fixtures/*',
		'fixtures*/*',
		'temp/*',
		'tmp/*',
		'vendor/*',
	]);
};
