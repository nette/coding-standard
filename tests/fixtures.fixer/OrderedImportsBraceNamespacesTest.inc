<?php // {"Nette/ordered_imports": true}

namespace First {
	use const PHP_EOL;
	use function strlen;
	use Nette\Utils\Strings;

	echo Strings::upper('x') . strlen('y') . PHP_EOL;
}

namespace Second {
	use const PHP_INT_MAX;
	use Nette\Utils\Arrays;

	echo Arrays::first([1]) . PHP_INT_MAX;
}
