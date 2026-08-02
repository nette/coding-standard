<?php // {"optimizedFunctionsOnly": true, "includedConstants": ["PHP_*"]}

namespace App;

use const PHP_EOL;

class MyClass
{
    public function run()
    {
        $len = \strlen('foo');
        echo $len . PHP_EOL;
    }
}
