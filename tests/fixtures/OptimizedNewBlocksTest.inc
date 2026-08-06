<?php // {"optimizedFunctionsOnly": true, "includedConstants": ["PHP_*"]}

namespace App;

class MyClass
{
    public function run(array $items)
    {
        if (is_array($items) && count($items) > 0) {
            echo implode(PHP_EOL, $items) . PHP_INT_MAX;
        }
    }
}
