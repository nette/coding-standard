# Check & Fix Your Code with Nette Coding Standard

[![Downloads this Month](https://img.shields.io/packagist/dm/nette/coding-standard.svg)](https://packagist.org/packages/nette/coding-standard)
[![Tests](https://github.com/nette/coding-standard/workflows/Tests/badge.svg?branch=master)](https://github.com/nette/coding-standard/actions)
[![Latest Stable Version](https://img.shields.io/packagist/v/nette/coding-standard.svg)](https://github.com/nette/coding-standard/releases)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](/LICENSE)


This is set of [sniff](https://github.com/squizlabs/PHP_CodeSniffer) and [fixers](https://github.com/FriendsOfPHP/PHP-CS-Fixer) that **checks and fixes** your PHP code against [Coding Standard in Documentation](https://nette.org/en/coding-standard).


## What Rules are Covered?

This package covers **part of [official rules](https://nette.org/en/coding-standard)**, not all.

When you open [`/examples`](/examples) directory, all files you'll see are checked by this coding standard. The code might look invalid compared to Nette code you know, but it's only because this tool doesn't check it (yet).

All **general rules** you can find in [`preset/php56.php`](/preset/php56.php) file.


## Install and Use


### Local Setup

Installation into global folder named `nette-coding-standard`:

```
composer create-project nette/coding-standard nette-coding-standard
```

Check coding standard:

```bash
nette-coding-standard/ecs check src tests --preset php71
```

And fix it:

```bash
nette-coding-standard/ecs check src tests --preset php71 --fix
```

### Travis Setup

```yaml
# .travis.yml
install:
    - composer create-project nette/coding-standard temp/nette-coding-standard

script:
    - temp/nette-coding-standard/ecs check src tests --preset php71
```
