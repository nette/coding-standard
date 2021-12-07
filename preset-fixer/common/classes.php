<?php

declare(strict_types=1);

return [
	// class element order: constants, properties, from public to private
	'ordered_class_elements' => [
		'order' => [
			'use_trait',
			'constant',
			'constant_public',
			'constant_protected',
			'constant_private',
			'property_public',
			'property_protected',
			'property_private',
		],
	],

	// All Class and Trait elements should have visibility required
	'Nette/class_and_trait_visibility_required' => [
		'elements' => ['property', 'method'],
	],

	// Properties MUST not be explicitly initialized with `null`.
	'no_null_property_initialization' => true,

	// Methods

	// In function arguments there must not be arguments with default values before non-default ones.
	'no_unreachable_default_argument_value' => true,
];
