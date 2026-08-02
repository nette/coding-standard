<?php // {"Nette/ordered_imports": true}

namespace Demo;

use const PHP_EOL;
use function strlen;
use Nette\Utils\Validators;
use function count;
use Nette\Utils\Arrays;

class Demo
{
	public function go(array $items): string
	{
		return Arrays::first($items) . Validators::isList($items) . count($items) . strlen('x') . PHP_EOL;
	}
}
