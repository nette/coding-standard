<?php

declare(strict_types=1);

namespace NetteCodingStandard\Fixer\ClassNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ClassNotation\VisibilityRequiredFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use ReflectionMethod;
use SplFileInfo;

final class ClassAndTraitVisibilityRequiredFixer extends AbstractFixer implements ConfigurableFixerInterface
{
	/** @var VisibilityRequiredFixer */
	private $visibilityRequiredFixer;


	public function __construct()
	{
		$this->visibilityRequiredFixer = new VisibilityRequiredFixer;
		parent::__construct();
	}


	public function isCandidate(Tokens $tokens): bool
	{
		return $tokens->isAnyTokenKindsFound([T_CLASS, T_TRAIT]);
	}


	public function getDefinition(): FixerDefinitionInterface
	{
		return $this->visibilityRequiredFixer->getDefinition();
	}


	public function getPriority(): int
	{
		return $this->visibilityRequiredFixer->getPriority();
	}


	public function configure(array $configuration): void
	{
		$this->configuration = $configuration;
		$this->visibilityRequiredFixer->configure($configuration);
	}


	public function getConfigurationDefinition(): FixerConfigurationResolverInterface
	{
		return $this->visibilityRequiredFixer->getConfigurationDefinition();
	}


	protected function applyFix(SplFileInfo $file, Tokens $tokens): void
	{
		/**
		 * Hack note: This reflection opening was chosen as more future-proof
		 * than duplicating whole 300-lines class. As "VisibilityRequiredFixer" class is final
		 * and "applyFix()" is final, there is no other way round it.
		 */
		$method = new ReflectionMethod($this->visibilityRequiredFixer, 'applyFix');
		if (version_compare(PHP_VERSION, '8.1.0', '<')) {
			$method->setAccessible(true);
		}
		$method->invoke($this->visibilityRequiredFixer, $file, $tokens);
	}


	public function getName(): string
	{
		return 'Nette/' . parent::getName();
	}
}
