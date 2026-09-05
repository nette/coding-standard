<?php declare(strict_types=1);

use Nette\CodingStandard\PhpVersionFilter;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$filter = PhpVersionFilter::create();

test('a file without the annotation is kept', function () use ($filter) {
	Assert::false($filter("<?php\necho 1;\n", 'a.php'));
});

test('a version above the running PHP skips the file', function () use ($filter) {
	Assert::true($filter("<?php\n/** @phpVersion 99.9 */\n", 'a.php'));
});

test('a version at most the running PHP keeps the file', function () use ($filter) {
	Assert::false($filter("<?php\n/** @phpVersion 5.6 */\n", 'a.php'));
	Assert::false($filter('/** @phpVersion ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . ' */', 'a.php'));
});

test('the annotation is case-insensitive', function () use ($filter) {
	Assert::true($filter('// @PHPVERSION 99.0', 'a.php'));
});
