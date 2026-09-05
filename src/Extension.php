<?php declare(strict_types=1);

namespace Nette\CodingStandard;

use DressCode\Config;


/**
 * What a project gets from this package on top of DressCode: the presets known by name, and the exclusions and
 * the file filter version 3 had built in. Usage in dresscode.neon: `extensions: [Nette\CodingStandard\Extension]`
 * next to `presets: [dresscode/nette]`.
 */
final class Extension
{
	public function __invoke(Config $config): void
	{
		$config
			->registerPresets([Presets\CleanCode::class, Presets\OptimizeFn::class, Presets\Types::class])
			->excludePaths(['expected', 'tmp', 'fixtures*'])
			->skipWhen(PhpVersionFilter::create());
	}
}
