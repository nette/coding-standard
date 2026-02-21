<?php declare(strict_types=1);


function test(?array $a, int $b)
{
	return 1;
}


function &test2(): int
{
	return 1;
}


$a = (int) $b;
$x = 2;

$a = [1, 2, 3];
$a = [
	1,
	2,
	3,
];


[true, false, null];

trim('a', $b);

trim(
	1,
	2,
);

trim(
	'hfklasdehfgisdgfkljhsnettefsedhgfsdghflskdhfsdlhfgldkshsdfhgsdlkfh',
	415_645_646_548_746_845_646_545_646_546,
);

test(fn($a): x => $a + 2);


func(
	$a
		? $b
		: $c,
);


func(
	$a && ($a
		? $b
		: $c) && $c,
	$d,
);


if ($a && ($a
	? $b
	: $c) && $c
) {
	echo 1;
}



if (
	$this->lastAttrValue === ''
	&& $this->context
	&& Helpers::startsWith($this->context, self::CONTEXT_HTML_ATTRIBUTE)
) {
	x();
}

if (
	$tokens->isNext()
	&& (
		!$tokens->isNext($tokens::T_CHAR)
		|| $tokens->isNext('hfklasdehfgisdgfkljhsnettefsedhgfsdghflskdhf', '\\')
	)
) {
	x();
}

if (
	$tokens->isNext()
	&& ($tokens->isNext($tokens::T_CHAR)
		|| $tokens->isNext('hfklasdehfgisdgfkljhsnettefsedhgfsdghflskdhf', '\\'))
) {
	x();
}


$s .= ($item['hfklasdehfgisdgfkljhsnettefsedhgfsdghflskdhfsdlhfgldkshsdfhgsdlkfh']
		? (
			$a . $b
		)
		: ($line
			? trim($line)
			: $item
		)
);

$s .= fnc(
	$item['hfklasdehfgisdgfkljhsnettefsedhgfsdghflskdhfsdlhfgldkshsdfhgsdlkfh']
		? (
			$a . $b
		)
		: ($line
			? trim($line)
			: $item
		),
);
