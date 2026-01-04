# Nette Coding Standard code checker & fixer

[![Downloads this Month](https://img.shields.io/packagist/dm/nette/coding-standard.svg)](https://packagist.org/packages/nette/coding-standard)
[![Latest Stable Version](https://img.shields.io/packagist/v/nette/coding-standard.svg)](https://github.com/nette/coding-standard/releases)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](/LICENSE)


This is set of [sniffs](https://github.com/squizlabs/PHP_CodeSniffer) and [fixers](https://github.com/FriendsOfPHP/PHP-CS-Fixer) that **checks and fixes** code of Nette Framework against [Coding Standard in Documentation](https://doc.nette.org/en/contributing/coding-standard).


## Installation and Usage

Install the tool globally:

```
composer global require nette/coding-standard
```

Check coding standard in folders `src` and `tests`:

```bash
ecs check src tests
```

And fix it:

```bash
ecs fix src tests
```

The PHP version preset is automatically detected from your project's `composer.json`. If auto-detection is not possible, you can specify it manually:

```bash
ecs check src tests --preset php81
```


### GitHub Actions

```yaml
# .github/workflows/coding-style.yml
steps:
    - uses: actions/checkout@v4
    - uses: shivammathur/setup-php@v2
      with:
          php-version: 8.1

    - run: composer create-project nette/coding-standard temp/coding-standard
    - run: php temp/coding-standard/ecs check src tests

```
