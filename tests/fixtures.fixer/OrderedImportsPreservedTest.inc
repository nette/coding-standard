<?php // {"Nette/ordered_imports": true}

namespace Demo;

use const PHP_EOL;
// a comment anywhere in the block makes the whole block untouchable, so this wrong
// order stays as it is rather than the comments being dropped or reattached
use function strlen; // why this one
use Nette\Utils\Strings;

class Demo
{
	public function go(): string
	{
		return Strings::upper('x') . strlen('y') . PHP_EOL;
	}
}
