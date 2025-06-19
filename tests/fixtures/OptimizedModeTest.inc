<?php // {"optimizedFunctionsOnly": true}

namespace App;

use function trim;
use function count;

class MyClass
{
    public function run()
    {
        // Optimized function, should be managed.
        $len = \strlen('foo');

        // Not an optimized function, should be ignored by the sniff.
        trim(' bar ');
    }
}
