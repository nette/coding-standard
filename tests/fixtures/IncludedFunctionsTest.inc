<?php // {"includedFunctions": ["str*", "count"]}

namespace App;

class MyClass
{
    public function run()
    {
        $a = strlen('hello');
        $b = strpos('hello', 'e');
        $c = count([]);
        $d = trim(' x ');
    }
}
