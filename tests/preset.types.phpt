<?php declare(strict_types=1);

/**
 * The nette/types preset is dresscode/nette plus the native type rules.
 */

use DressCode\Config;
use DressCode\Config\PresetResolver;
use DressCode\Config\RuleRegistry;
use DressCode\PresetContext;
use DressCode\Presets\Nette;
use DressCode\RuleInfo;
use Nette\CodingStandard\Presets\Types;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$registry = new RuleRegistry;
$context = new PresetContext('8.4');
$names = fn(string $preset) => array_map(
	fn($rule) => RuleInfo::of($rule)->name,
	(new PresetResolver($registry))->resolve(Config::create()->preset($preset), $context),
);

Assert::same(
	[
		...$names(Nette::class),
		'dresscode/type-hint-required',
		'dresscode/union-type-format',
	],
	$names(Types::class),
);
Assert::same($names(Types::class), $names('nette/types'));
