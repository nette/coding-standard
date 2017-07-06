# Nette Coding Standard

[![Downloads this Month](https://img.shields.io/packagist/dm/nette/coding-standard.svg)](https://packagist.org/packages/nette/coding-standard)
[![Build Status](https://travis-ci.org/nette/coding-standard.svg?branch=master)](https://travis-ci.org/nette/coding-standard)
[![Latest Stable Version](https://img.shields.io/packagist/v/nette/coding-standard.svg)](https://github.com/nette/coding-standard/releases)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](/LICENSE)


This is set of [sniff](https://github.com/squizlabs/PHP_CodeSniffer) and [fixers](https://github.com/FriendsOfPHP/PHP-CS-Fixer) combined under [EasyCodingStandard](https://github.com/Symplify/EasyCodingStandard) that **checks and fixes** your PHP code against [Coding Standard in Nette Documentation](https://nette.org/en/coding-standard). 



## A. Travis Setup

```yaml
# .travis.yml
install:
    - composer create-project nette/coding-standard temp/nette-coding-standard

script:
    - temp/nette-coding-standard/vendor/symplify/easy-coding-standard/bin/ecs check src tests --config temp/nette-coding-standard/easy-coding-standard.neon
```


## B. Local Setup

```bash
composer require --dev nette/coding-standard
vendor/bin/ecs check src tests --config vendor/bin/nette/coding-standard/easy-coding-standard.neon 
```


## Composer Script for Lazy Programmer

To avoid long scripts and typos, you can add this to your `composer.json`:

```json
{
    "scripts": {
        "cs": "vendor/bin/ecs check src tests --config vendor/nette/coding-standard/easy-coding-standard.neon",
        "fs": "vendor/bin/ecs check src tests --config vendor/nette/coding-standard/easy-coding-standard.neon --fix"
    }
}
```

Check coding standard:

```bash
composer cs
```

And fix it: 

```bash
composer fs
```
