<?php // {"optimizedFunctionsOnly": true}

namespace App;



class MyClass
{
    public function run(array $items)
    {
        if (is_array($items) && count($items) > 0) {
            echo implode(', ', $items);
        }
    }
}
