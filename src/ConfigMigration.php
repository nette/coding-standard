<?php declare(strict_types=1);

namespace Nette\CodingStandard;

use DressCode\Config\RuleRegistry;
use Nette\Neon\Neon;
use function is_array, is_string;


/**
 * Turns the version 3 configuration of a project into dresscode.neon: the fixer overrides of ncs.php become
 * rules, the exclusions of ncs.xml become excluded paths, and every name goes through the aliases of the rules.
 * Names no rule owns are listed and left out.
 */
final class ConfigMigration
{
	/** the fixers and the sniff version 3 had of its own */
	private const OwnNames = [
		'Nette/braces_position' => 'dresscode/braces-position',
		'Nette/class_and_trait_visibility_required' => 'dresscode/visibility-required',
		'Nette/method_argument_space' => 'dresscode/multi-line-call',
		'Nette/no_leading_slash_in_global_namespace' => 'dresscode/no-leading-backslash-in-global-namespace',
		'Nette/optimize_global_calls' => 'dresscode/global-imports',
		'Nette/ordered_imports' => 'dresscode/ordered-imports',
		'Nette/statement_indentation' => 'dresscode/indentation',
		'NetteCodingStandard.WhiteSpace.FunctionSpacing' => 'dresscode/declaration-blank-lines',
	];

	/** @var list<string> */
	public array $unknown = [];

	/** @var list<string> */
	public array $notes = [];


	public function __construct(
		private readonly RuleRegistry $registry = new RuleRegistry,
	) {
	}


	/** The content of dresscode.neon, or null when the directory has neither ncs.php nor ncs.xml. */
	public function migrate(string $dir): ?string
	{
		$phpFile = "$dir/ncs.php";
		$xmlFile = "$dir/ncs.xml";
		if (!is_file($phpFile) && !is_file($xmlFile)) {
			return null;
		}

		$rules = [];
		$skip = [];
		$ruleSkip = [];
		if (is_file($phpFile)) {
			$overrides = require $phpFile;
			foreach (is_array($overrides) ? $overrides : [] as $name => $value) {
				$targets = is_string($name) ? $this->resolveNames($name) : [];
				if ($targets === []) {
					$this->unknown[] = (string) $name;
					continue;
				}

				foreach ($targets as $rule) {
					$rules[$rule] = $value;
					if (is_array($value)) {
						$this->notes[] = "$rule: the options of $name are copied as they are; check their names against `dresscode rules`";
					}
				}
			}
		}

		if (is_file($xmlFile)) {
			$xml = @simplexml_load_string((string) file_get_contents($xmlFile)); // @ - reported as exception
			if ($xml === false) {
				throw new \RuntimeException("Cannot parse $xmlFile.");
			}

			foreach ($xml->{'exclude-pattern'} as $pattern) {
				$skip[] = self::pattern((string) $pattern);
			}

			foreach ($xml->rule as $element) {
				$ref = (string) $element['ref'];
				$targets = $this->resolveNames($ref)
					?: $this->resolveNames((string) preg_replace('~\.[^.]+$~', '', $ref)); // a sniff code
				if ($targets === []) {
					$this->unknown[] = $ref;
					continue;
				}

				$patterns = [];
				foreach ($element->{'exclude-pattern'} as $pattern) {
					$patterns[] = self::pattern((string) $pattern);
				}

				if ($patterns === []) {
					$this->notes[] = "$ref: a rule element without exclude-pattern has no counterpart and was left out";
				}

				foreach ($patterns === [] ? [] : $targets as $rule) {
					$ruleSkip[$rule] = array_merge($ruleSkip[$rule] ?? [], $patterns);
				}
			}
		}

		$paths = array_values(array_filter(['src', 'tests', 'tools'], fn(string $path) => is_dir("$dir/$path")));
		$sections = array_filter([
			'extensions' => [Extension::class],
			'presets' => ['dresscode/nette'],
			'paths' => $paths, // version 3 checked src and tests by itself
			'rules' => $rules,
			'excludePaths' => array_values(array_unique($skip)),
			'excludeRulePaths' => $ruleSkip,
		]);
		$blocks = array_map(fn($key, $value) => rtrim(Neon::encode([$key => $value], blockMode: true)), array_keys($sections), $sections);
		return implode("\n\n", $blocks) . "\n";
	}


	/** @return list<string> */
	private function resolveNames(string $name): array
	{
		return isset(self::OwnNames[$name])
			? [self::OwnNames[$name]]
			: $this->registry->resolveNames($name);
	}


	/** A phpcs exclude pattern as a DressCode one: slashes, relative to the root. */
	private static function pattern(string $pattern): string
	{
		return (string) preg_replace('~^\./~', '', str_replace('\\', '/', trim($pattern)));
	}
}
