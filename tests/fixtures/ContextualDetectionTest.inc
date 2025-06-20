<?php // {"optimizedFunctionsOnly": false}

namespace App;

class MyClass
{
    // These are declarations, not calls. Should be ignored.
    public function count() {}
    public function &strlen() {}

    public function run()
    {
        // These are method calls, not global calls. Should be ignored.
        $this->count();
        self::strlen();

        // This is a real global call. Should be imported.
        $type = \gettype([]);
    }
}
