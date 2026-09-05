<?php declare(strict_types=1);

namespace Nette\CodingStandard\Presets;

use DressCode\Preset;
use DressCode\PresetContext;
use DressCode\PresetInfo;
use DressCode\Presets\Nette;
use DressCode\Rules;


/**
 * The Nette coding standard plus native types everywhere: parameters, return values and properties get
 * them from their annotations, and a nullable type is written `?T`.
 */
#[PresetInfo('nette/types', 'The Nette coding standard with native types added from annotations')]
final class Types implements Preset
{
	public function getRules(PresetContext $context): array
	{
		return [
			Rules\Types\TypeHintRequiredRule::class => ['traversableTypeHints' => ['Traversable']],
			Rules\Types\UnionTypeFormatRule::class => true,
		];
	}


	public function getParents(): array
	{
		return [Nette::class];
	}
}
