<?php declare(strict_types=1);

namespace Nette\CodingStandard\Presets;

use DressCode\Preset;
use DressCode\PresetContext;
use DressCode\PresetInfo;
use DressCode\Presets\Nette;
use DressCode\Rules;


/**
 * The Nette coding standard plus the clean code rules: strict comparisons, no useless branches, final internal
 * classes, no unset on properties, ternaries, direct calls of invokables, no type kind in a name.
 */
#[PresetInfo('nette/clean-code', 'The Nette coding standard with the clean code rules')]
final class CleanCode implements Preset
{
	public function getRules(PresetContext $context): array
	{
		return [
			Rules\Expressions\StrictComparisonRule::class => true,
			Rules\ControlFlow\UselessElseRule::class => true,
			Rules\Classes\FinalInternalClassRule::class => true,
			Rules\Variables\NoUnsetOnPropertyRule::class => true,
			Rules\ControlFlow\TernaryForSimpleBranchRule::class => true,
			Rules\Functions\NoDirectInvokeCallRule::class => true,
			Rules\Classes\NoKindInClassNameRule::class => true,
			Rules\ControlFlow\EarlyExitRule::class => true,
			Rules\Functions\StaticClosureRule::class => true,
		];
	}


	public function getParents(): array
	{
		return [Nette::class];
	}
}
