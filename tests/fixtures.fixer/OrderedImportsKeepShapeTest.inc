<?php // {"Nette/ordered_imports": true}

namespace Demo;

use const PHP_EOL;
use Demo\Second, Demo\First;
use function strlen;
use Demo\Third;

class Demo
{
	public function go(): string
	{
		return First::x() . Second::y() . Third::z() . strlen('x') . PHP_EOL;
	}
}
