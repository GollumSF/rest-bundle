# GollumSFRestBundle

[![Build Status](https://travis-ci.com/GollumSF/rest-bundle.svg?branch=master)](https://travis-ci.com/GollumSF/rest-bundle)
[![Coverage](https://coveralls.io/repos/github/GollumSF/rest-bundle/badge.svg?branch=master)](https://coveralls.io/github/GollumSF/rest-bundle)
[![License](https://poser.pugx.org/gollumsf/rest-bundle/license)](https://packagist.org/packages/gollumsf/rest-bundle)
[![Latest Stable Version](https://poser.pugx.org/gollumsf/rest-bundle/v/stable)](https://packagist.org/packages/gollumsf/rest-bundle)
[![Latest Unstable Version](https://poser.pugx.org/gollumsf/rest-bundle/v/unstable)](https://packagist.org/packages/gollumsf/rest-bundle)
[![Discord](https://img.shields.io/discord/671741944149573687?color=purple&label=discord)](https://discord.gg/xMBc5SQ)


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
    max_limit_item:     100  # optional, default : 100
    default_limit_item: 25   # optional, default : 25
```
