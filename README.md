# GollumSFRestBundle

[![Build Status](https://travis-ci.org/GollumSF/rest-bundle.svg?branch=master)](https://travis-ci.org/GollumSF/rest-bundle)
[![License](https://poser.pugx.org/gollumsf/rest-bundle/license)](https://packagist.org/packages/gollumsf/rest-bundle)
[![Latest Stable Version](https://poser.pugx.org/gollumsf/rest-bundle/v/stable)](https://packagist.org/packages/gollumsf/rest-bundle)
[![Latest Unstable Version](https://poser.pugx.org/gollumsf/rest-bundle/v/unstable)](https://packagist.org/packages/gollumsf/rest-bundle)


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

```yml
gollum_sf_rest:
```
