<?php // {"Nette/no_leading_slash_in_global_namespace": true}

namespace App {
	$a = new \Foo\Bar;
}

namespace {
	use Foo\Foo;

	$b = new \Foo\Bar;
	$c = new \Other\Thing;
}
