<?php // {"optimizedFunctionsOnly": false, "excludedFunctions": ["dd", "dump"]}

namespace App;

class MyClass
{
    public function run()
    {
        // This should be imported.
        $c = \count([]);

        // These are ignored via config, should be left as is.
        \dd('debug');
        dump('debug');
    }
}
