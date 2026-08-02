<?php // {"Nette/ordered_imports": true}

namespace Demo;

use const PHP_EOL;
use function strlen; // this sits behind the block's last semicolon

class Demo
{
	public function go(): string
	{
		return strlen('y') . PHP_EOL;
	}
}
