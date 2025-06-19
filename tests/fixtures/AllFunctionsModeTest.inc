<?php // {"optimizedFunctionsOnly": false}

namespace App;

use const E_ALL;
use function strlen;
use function count;
use const PHP_OS;

class MyClass
{
    public function run()
    {
        \strlen('test');
        $c = \count([]);
        $v = \PHP_VERSION;
        $os = PHP_OS;
    }
}
