# GollumSFRestBundle

[![Build Status](https://travis-ci.org/GollumSF/rest-bundle.svg?branch=master)](https://travis-ci.org/GollumSF/rest-bundle)
[![Coverage](https://coveralls.io/repos/github/GollumSF/rest-bundle/badge.svg?branch=master)](https://coveralls.io/github/GollumSF/rest-bundle)
[![License](https://poser.pugx.org/gollumsf/rest-bundle/license)](https://packagist.org/packages/gollumsf/rest-bundle)
[![Latest Stable Version](https://poser.pugx.org/gollumsf/rest-bundle/v/stable)](https://packagist.org/packages/gollumsf/rest-bundle)
[![Latest Unstable Version](https://poser.pugx.org/gollumsf/rest-bundle/v/unstable)](https://packagist.org/packages/gollumsf/rest-bundle)
[![Discord](https://img.shields.io/discord/671741944149573687?color=purple&label=discord)](https://discord.gg/xMBc5SQ)

Very simple REST Api implementation

## Installation:

```shell
composer require gollumsf/rest-bundle
```

### config/bundles.php
```php
return [
    // [ ... ]
    GollumSF\RestBundle\GollumSFRestBundle::class => ['all' => true],
];
```

config.yml

```yaml
gollum_sf_rest:
    max_limit_item:              100   # (optional, default : 100) Max limit item API support when call ApiSearch, if 0 no limit.
    default_limit_item:          25    # (optional, default : 25) Default limit item API support if no limit on request when call ApiSearch
    always_serialized_exception: false # (optional, default : false) All symfony exception return json response. If false only route with Serialize annotation
```

## Usages:

 - [Get started](docs/GetStarted.md)
 - [Serialize](docs/GetStarted.md)
 - [Unserialize](docs/GetStarted.md)
 - [Validation](docs/Validation.md)
 - [ApiDoc / Swagger](https://github.com/GollumSF/rest-doc-bundle)