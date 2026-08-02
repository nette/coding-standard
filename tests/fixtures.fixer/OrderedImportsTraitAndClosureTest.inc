<?php // {"Nette/ordered_imports": true}

namespace Demo;

use const PHP_EOL;
use function strlen;
use Other\SomeTrait;

class Demo
{
	use SomeTrait;

	public function go(): callable
	{
		$x = 1;
		return function () use ($x) {
			return $x . strlen('a') . PHP_EOL;
		};
	}
}
