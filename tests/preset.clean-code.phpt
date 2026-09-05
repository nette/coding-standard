<?php declare(strict_types=1);

/**
 * The nette/clean-code preset is dresscode/nette plus the clean code rules.
 */

use DressCode\Config;
use DressCode\Config\PresetResolver;
use DressCode\Config\RuleRegistry;
use DressCode\PresetContext;
use DressCode\Presets\Nette;
use DressCode\RuleInfo;
use Nette\CodingStandard\Presets\CleanCode;
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
		'dresscode/strict-comparison',
		'dresscode/useless-else',
		'dresscode/final-internal-class',
		'dresscode/no-unset-on-property',
		'dresscode/ternary-for-simple-branch',
		'dresscode/no-direct-invoke-call',
		'dresscode/no-kind-in-class-name',
		'dresscode/early-exit',
		'dresscode/static-closure',
	],
	$names(CleanCode::class),
);
Assert::same($names(CleanCode::class), $names('nette/clean-code'));
