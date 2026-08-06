<?php // {"optimizedFunctionsOnly": true, "includedConstants": ["PHP_*"]}

namespace App;

use App\Other;

class MyClass
{
    public function run(array $items, Other $other)
    {
        if (is_array($items) && count($items) > 0) {
            echo implode(PHP_EOL, $items) . PHP_INT_MAX;
        }
    }
}
