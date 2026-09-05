<?php declare(strict_types=1);

namespace Nette\CodingStandard\Presets;

use DressCode\Preset;
use DressCode\PresetContext;
use DressCode\PresetInfo;
use DressCode\Presets\Nette;
use DressCode\Rules;


/**
 * The Nette coding standard plus imports of the global functions the PHP compiler optimizes.
 */
#[PresetInfo('nette/optimize-fn', 'The Nette coding standard with imports of the optimized global functions')]
final class OptimizeFn implements Preset
{
	public function getRules(PresetContext $context): array
	{
		return [
			Rules\Namespaces\GlobalImportsRule::class => ['constants' => ['PHP_*', 'DIRECTORY_SEPARATOR']],
		];
	}


	public function getParents(): array
	{
		return [Nette::class];
	}
}
