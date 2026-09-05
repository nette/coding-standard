<?php declare(strict_types=1);

/**
 * The nette/optimize-fn preset is dresscode/nette plus the imports of the optimized global functions.
 */

use DressCode\Config;
use DressCode\Config\PresetResolver;
use DressCode\Config\RuleRegistry;
use DressCode\PresetContext;
use DressCode\Presets\Nette;
use DressCode\RuleInfo;
use Nette\CodingStandard\Presets\OptimizeFn;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$registry = new RuleRegistry;
$context = new PresetContext('8.4');
$names = fn(string $preset) => array_map(
	fn($rule) => RuleInfo::of($rule)->name,
	(new PresetResolver($registry))->resolve(Config::create()->preset($preset), $context),
);

Assert::same([...$names(Nette::class), 'dresscode/global-imports'], $names(OptimizeFn::class));
Assert::same($names(OptimizeFn::class), $names('nette/optimize-fn'));
