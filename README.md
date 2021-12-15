# Nette Coding Standard code checker & fixer

[![Downloads this Month](https://img.shields.io/packagist/dm/nette/coding-standard.svg)](https://packagist.org/packages/nette/coding-standard)
[![Latest Stable Version](https://img.shields.io/packagist/v/nette/coding-standard.svg)](https://github.com/nette/coding-standard/releases)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](/LICENSE)


This is set of [sniffs](https://github.com/squizlabs/PHP_CodeSniffer) and [fixers](https://github.com/FriendsOfPHP/PHP-CS-Fixer) that **checks and fixes** code of Nette Framework against [Coding Standard in Documentation](https://nette.org/en/coding-standard).


## Installation and Usage

Install the tool in a global directory. Its name will be for example `/nette-cs`:

```
composer create-project nette/coding-standard /nette-cs
```

Check coding standard for PHP 7.1 in folders `src` and `tests`:

```bash
/nette-cs/ecs check src tests --preset php71
```

And fix it:

```bash
/nette-cs/ecs fix src tests --preset php71
```

If no PHP version is specified, it will try to find out from the `composer.json` file.


### GitHub Actions

```yaml
# .github/workflows/coding-style.yml
steps:
    - uses: actions/checkout@v2
    - uses: shivammathur/setup-php@v2
      with:
          php-version: 8.0

    - run: composer create-project nette/coding-standard temp/coding-standard
    - run: php temp/coding-standard/ecs check src tests --preset php71

```
