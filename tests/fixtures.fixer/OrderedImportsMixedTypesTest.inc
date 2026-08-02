<?php // {"Nette/ordered_imports": true}

namespace Demo;

use function count, strlen;
use const DIRECTORY_SEPARATOR, PHP_EOL;
use Nette\Utils\Strings;

class Demo
{
	public function go(array $items): string
	{
		return Strings::upper((string) (count($items) + strlen('x'))) . PHP_EOL . DIRECTORY_SEPARATOR;
	}
}
