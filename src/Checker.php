<?php declare(strict_types=1);

/**
 * Class responsible for running code style checks and fixes.
 */
class Checker
{
	private const IgnoredPaths = [
		'/fixtures.*/',
		'expected',
		'temp',
		'tmp',
		'vendor',
	];

	private string $fileListPath;


	public function __construct(
		private string $vendorDir,
		private string $projectDir,
		private bool $dryRun = true,
		private ?string $preset = null,
	) {
		$this->fileListPath = dirname(__DIR__) . '/filelist.tmp';
	}


	public function setPaths(array $paths): void
	{
		$files = [];
		$dirs = [];
		foreach ($paths as $path) {
			if (is_file($path)) {
				$files[] = $path;
			} elseif (is_dir($path)) {
				$dirs[] = $path;
			} else {
				fwrite(STDERR, "Warning: Path not found: $path\n");
			}
		}

		$versionFilter = fn($path) => !preg_match('#@phpVersion\s+([0-9.]+)#i', file_get_contents($path), $m)
			|| version_compare(PHP_VERSION, $m[1], '>=');

		$result = [];
		if ($dirs) {
			$finder = PhpCsFixer\Finder::create()
				->name(['*.php', '*.phpt'])
				->notPath(self::IgnoredPaths)
				->filter(fn(SplFileInfo $file) => $versionFilter((string) $file))
				->in($dirs);

			foreach ($finder as $file) {
				$result[] = (string) $file;
			}
		}

		foreach ($files as $file) {
			if (preg_match('#\.(php|phpt)$#', $file) && $versionFilter($file)) {
				$result[] = realpath($file) ?: $file;
			}
		}

		file_put_contents($this->fileListPath, implode("\n", $result));
	}


	/**
	 * Runs PHP CS Fixer.
	 * Returns true on success, false on failure.
	 */
	public function runFixer(): bool
	{
		$fixerBin = $this->vendorDir . '/friendsofphp/php-cs-fixer/php-cs-fixer';

		$presetPath = dirname(__DIR__) . '/preset-fixer';
		$preset = $this->preset;
		if ($preset === null) {
			$preset = $this->derivePresetFromVersion($presetPath);
			echo "Preset: $preset detected from PHP version\n";
		}
		$presetFile = "$presetPath/$preset.php";
		if (!is_file($presetFile)) {
			fwrite(STDERR, "Error: Preset configuration not found for PHP CS Fixer: {$presetFile}\n");
			return false;
		}

		passthru(
			PHP_BINARY
			. (php_ini_loaded_file() ? ' -c ' . escapeshellarg(php_ini_loaded_file()) : '')
			. ' ' . escapeshellarg($fixerBin)
			. ' fix -v'
			. ($this->dryRun ? ' --dry-run' : '')
			. ' --config=' . escapeshellarg($presetFile),
			$exitCode,
		);
		return $exitCode === 0;
	}


	/**
	 * Runs PHP_CodeSniffer (phpcs or phpcbf).
	 */
	public function runSniffer(): bool
	{
		$snifferBin = $this->vendorDir . '/squizlabs/php_codesniffer/bin/' . ($this->dryRun ? 'phpcs' : 'phpcbf');

		$presetPath = dirname(__DIR__) . '/preset-sniffer';
		$preset = $this->preset;
		if ($preset === null) {
			$preset = $this->derivePresetFromVersion($presetPath);
			echo "Preset: $preset detected from PHP version\n";
		}
		$presetFile = "$presetPath/$preset.xml";
		if (!is_file($presetFile)) {
			fwrite(STDERR, "Error: Preset ruleset not found for PHP_CodeSniffer: {$presetFile}\n");
			return false;
		}

		$phpVersionOption = '';
		$originalNcsContent = null;
		$ncsPath = $this->projectDir . '/ncs.xml';

		try {
			if (preg_match('~php(\d)(\d)~', $preset, $m)) {
				$phpVersionOption = " --runtime-set php_version {$m[1]}0{$m[2]}00";
				if (is_file($ncsPath)) {
					echo "Using custom ruleset: $ncsPath\n";
					$presetFile = $ncsPath;
					$originalNcsContent = file_get_contents($ncsPath);
					file_put_contents($ncsPath, str_replace('ref="$presets/', "ref=\"$presetPath/", $originalNcsContent));
				}
			}

			passthru(
				PHP_BINARY
				. (php_ini_loaded_file() ? ' -c ' . escapeshellarg(php_ini_loaded_file()) : '')
				. ' ' . escapeshellarg($snifferBin)
				. ' -s' // show sniff codes, works only in dry mode :-(
				. ' -p' // progress
				. $phpVersionOption
				. ' --colors'
				. ' --extensions=php,phpt'
				. ' --runtime-set ignore_warnings_on_exit true'
				. ' --no-cache'
				. ' --parallel=10'
				. ' --standard=' . escapeshellarg($presetFile)
				. ' --file-list=' . escapeshellarg($this->fileListPath),
				$exitCode,
			);

		} finally {
			if ($originalNcsContent !== null) {
				file_put_contents($ncsPath, $originalNcsContent);
			}
		}

		// phpcs returns 0 for no errors, 1 for errors found, 2 for fixable errors found (with --report=...), 3 for processing errors
		// phpcbf returns 0 for no errors, 1 for errors fixed, 2 for errors remaining, 3 for processing errors
		return $this->dryRun ? $exitCode === 0 : ($exitCode === 0 || $exitCode === 1);
	}


	/**
	 * Derives a preset name (e.g., 'php81') from a PHP version.
	 */
	private function derivePresetFromVersion(string $path): string
	{
		$phpVersion = $this->detectPhpVersion();
		$versions = array_map(
			fn($file) => preg_match('/php(\d)(\d+)\.\w+$/', $file, $m) ? "$m[1].$m[2]" : null,
			glob("$path/php*"),
		);
		usort($versions, fn($a, $b) => -version_compare($a, $b));
		foreach ($versions as $version) {
			if (version_compare($version, $phpVersion ?? '0', '<=')) {
				break;
			}
		}
		return 'php' . str_replace('.', '', $version);
	}


	/**
	 * Tries to detect the required PHP version from composer.json.
	 */
	private function detectPhpVersion(): ?string
	{
		$composerPath = $this->projectDir . '/composer.json';
		if (is_file($composerPath)) {
			$json = @json_decode(file_get_contents($composerPath));
			if (preg_match('#(\d+\.\d+)#', $json->require->php ?? '', $m)) {
				return $m[1];
			}
		}
		return null;
	}


	/**
	 * Cleans up temporary files.
	 */
	public function cleanup(): void
	{
		@unlink($this->fileListPath);
	}
}
