<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


/**
 * This runner creates a temporary, isolated ruleset to ensure only the target sniff is tested.
 */
class SniffTestRunner
{
	private string $sniffCode = 'NetteCodingStandard.Namespaces.OptimizeGlobalCalls';
	private string $installedPaths;
	private string $phpcbfPath;
	private string $testsDir;
	private array $tempFiles = [];


	public function __construct()
	{
		$this->installedPaths = realpath(__DIR__ . '/../src/NetteCodingStandard');
		$this->phpcbfPath = realpath(__DIR__ . '/../vendor/bin/phpcbf');
		$this->testsDir = __DIR__ . '/fixtures';

		if (!$this->phpcbfPath || !file_exists($this->phpcbfPath)) {
			throw new Exception("phpcbf executable not found at: {$this->phpcbfPath}");
		}
	}


	public function __destruct()
	{
		foreach ($this->tempFiles as $file) {
			if (file_exists($file)) {
				unlink($file);
			}
		}
	}


	/**
	 * Creates a temporary ruleset XML file with the sniff and its properties.
	 */
	private function createRuleset(array $properties): string
	{
		$xml = '<?xml version="1.0"?>' . PHP_EOL;
		$xml .= '<ruleset name="IsolatedSniffTest">' . PHP_EOL;
		$xml .= '	<config name="installed_paths" value="' . htmlspecialchars($this->installedPaths, ENT_XML1) . '"/>' . PHP_EOL;
		$xml .= '	<rule ref="' . htmlspecialchars($this->sniffCode, ENT_XML1) . '">' . PHP_EOL;

		if ($properties) {
			$xml .= '		<properties>' . PHP_EOL;
			foreach ($properties as $key => $value) {
				if (is_array($value)) {
					$xml .= '			<property name="' . htmlspecialchars($key, ENT_XML1) . '" type="array">' . PHP_EOL;
					foreach ($value as $element) {
						$xml .= '				<element value="' . htmlspecialchars((string) $element, ENT_XML1) . '"/>' . PHP_EOL;
					}
					$xml .= '			</property>' . PHP_EOL;
				} elseif (is_bool($value)) {
					$xml .= '			<property name="' . htmlspecialchars($key, ENT_XML1) . '" value="' . ($value ? 'true' : 'false') . '"/>' . PHP_EOL;
				} else {
					$xml .= '			<property name="' . htmlspecialchars($key, ENT_XML1) . '" value="' . htmlspecialchars((string) $value, ENT_XML1) . '"/>' . PHP_EOL;
				}
			}
			$xml .= '		</properties>' . PHP_EOL;
		}

		$xml .= '	</rule>' . PHP_EOL;
		$xml .= '</ruleset>' . PHP_EOL;

		$path = sys_get_temp_dir() . '/phpcs_ruleset_' . uniqid() . '.xml';
		file_put_contents($path, $xml);
		$this->tempFiles[] = $path;
		return $path;
	}


	/**
	 * Discovers and runs all test cases from the fixtures directory.
	 */
	public function run(): void
	{
		$testFiles = glob($this->testsDir . '/*.inc');

		foreach ($testFiles as $testFile) {
			test(basename($testFile, '.inc'), function () use ($testFile) {
				$this->runTestFromFile($testFile);
			});
		}
	}


	/**
	 * Runs a single test case.
	 */
	private function runTestFromFile(string $inputFile): void
	{
		$inputCode = file_get_contents($inputFile);

		$expectedFile = $inputFile . '.expected';
		$expectedFixedCode = file_exists($expectedFile) ? file_get_contents($expectedFile) : $inputCode;

		$actualFixedContent = $this->getFixedContent($inputCode);

		$expectedFixedCode = str_replace("\r\n", "\n", $expectedFixedCode);
		$actualFixedContent = str_replace("\r\n", "\n", $actualFixedContent);

		Assert::same($expectedFixedCode, $actualFixedContent, 'Mismatch for ' . basename($inputFile));
	}


	/**
	 * Executes phpcbf with the isolated ruleset and returns the fixed content.
	 */
	private function getFixedContent(string $inputCode): string
	{
		$tempInputFile = sys_get_temp_dir() . '/phpcs_test_input_' . uniqid() . '.php';
		file_put_contents($tempInputFile, $inputCode);
		$this->tempFiles[] = $tempInputFile;

		$properties = $this->parsePropertiesFromJsonComment($inputCode);
		$rulesetPath = $this->createRuleset($properties);

		$command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($this->phpcbfPath);
		$command .= ' --standard=' . escapeshellarg($rulesetPath);
		$command .= ' --no-cache';
		$command .= ' ' . escapeshellarg($tempInputFile);

		$descriptorSpec = [
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];

		$process = proc_open($command, $descriptorSpec, $pipes);
		$errors = '';
		$exitCode = -1;

		if (is_resource($process)) {
			fclose($pipes[1]);
			$errors = stream_get_contents($pipes[2]);
			fclose($pipes[2]);
			$exitCode = proc_close($process);
		}

		// phpcbf exit codes: 0 = nothing to fix, 1 = fixed successfully, 2+ = error
		if ($exitCode > 1) {
			return "PHPCBF FAILED WITH EXIT CODE {$exitCode}:\n" . $errors;
		}

		return file_get_contents($tempInputFile);
	}


	/**
	 * Parses a special JSON comment on the first line of the code to set sniff properties.
	 * E.g., <?php // {"optimizedFunctionsOnly": true, "ignoredFunctions": ["dd"]}
	 */
	private function parsePropertiesFromJsonComment(string $code): array
	{
		return preg_match('~^\s*<\?php\s*//\s*({.+})~', $code, $matches)
			? json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR)
			: [];
	}
}


(new SniffTestRunner)->run();
