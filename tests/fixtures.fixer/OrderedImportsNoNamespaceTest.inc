<?php // {"Nette/ordered_imports": true}

declare(strict_types=1);

use const PHP_EOL;
use function strlen;
use Nette\Utils\Strings;

echo Strings::upper('x') . strlen('y') . PHP_EOL;
