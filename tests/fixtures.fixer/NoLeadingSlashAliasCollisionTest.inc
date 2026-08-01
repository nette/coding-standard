<?php // {"Nette/no_leading_slash_in_global_namespace": true}

use Bar\Baz as Alias;
use Foo\Foo;
use Some\{Group\First, Second};

$a = new \Foo\Bar\Baz;
$b = new \Other\Thing;
$c = new \Exception;
$d = new \Alias\Deep;
$e = new \second\Deep;
$f = \Foo\Bar::CONSTANT;
$g = \Other\Bar::make();
$h = new \Alias;

function f(?\Foo\A $a, \Other\B $b): \First\C
{
	try {
	} catch (\Foo\X | \Other\Y $e) {
	}
}
