---
name: RestBundle migration status
description: Current state of SF 6.4-8.0 migration as of 2026-03-24. Resume from here.
type: project
---

## Migration RestBundle SF 6.4 → 8.0

### Résultats actuels
- **SF 6.4 / PHP 8.2 / PHPUnit 11** : 228 tests, 1402 assertions → **TOUS OK**
- **SF 7.x / PHP 8.2 / PHPUnit 11** : 228 tests, 1402 assertions → **TOUS OK**
- **SF 7.x / PHP 8.4 / PHPUnit 11** : 228 tests, 1402 assertions → **TOUS OK**
- **SF 8.0 / PHP 8.4** : unit tests OK, integration tests bloqués par bug Doctrine ORM 3.6 + var-exporter v8 (LazyGhostTrait supprimé)

### Ce qui a été fait
- composer.json : PHP >=8.2, SF ^6.4|^7.0|^8.0, PHPUnit ^11, entity-relation-setter ^3.0
- `Request::get()` supprimé en SF 8 → remplacé par `$request->query->get()` dans ApiSearch.php, StaticArrayApiList.php
- Implicit nullable fixé partout (PHP 8.4 deprecated)
- Proxy `__load()/__isInitialized()` return types ajoutés (Doctrine Persistence v3+)
- `ClassMetadata` : vrai objet au lieu de mock (property typé en Doctrine ORM 3)
- `QueryBuilder::getQuery()` retourne `Query` en Doctrine ORM 3 → mock de Query au lieu d'AbstractQuery
- `createQueryBuilder()` signature typée dans le test
- `symfony/var-exporter` ajouté en require-dev
- GitHub Actions mis à jour : actions/checkout@v4, xdebug coverage, PHPUnit 11
- Anciens workflows supprimés (PHP 7.x, SF 4.4-6.3)
- `.gitignore` : ajouté composer.lock

### Ce qui reste
1. **SF 8.0 integration tests** : bloqué par Doctrine ORM 3.6 incompatible avec var-exporter v8 (LazyGhostTrait). Attendre Doctrine ORM 3.7+/4.0
2. **git rm --cached composer.lock** : à faire avant le commit
3. **40 risky tests** : "did not remove its own exception handlers" - warnings mineurs GollumSFRestBundleTest
4. **130 PHPUnit deprecations** : à investiguer si besoin
5. **Tests ValueResolver** : écrire des tests unitaires pour PostRestValueResolver enrichi

### EntityRelationSetter
- **Livré en v3.0.0** : PHP >=8.2, Doctrine ORM ^2.10|^3.0, PHPUnit ^11
- 10 tests, 115 assertions, 100% coverage
- GitHub Actions : Doctrine 2.10, 2.11, 3.0, latest + PHP 8.2, 8.4

### ControllerActionExtractorBundle
- **Livré en v2.0.0** : SF ^6.4|^7.0|^8.0, PHP >=8.2

### Commande Docker pour tester
```sh
# SF 6.4
docker run --rm -v "$(pwd):/src:ro" -w /tmp/test registry.gitlab.com/damienduboeuf/docker/php-fpm-symfony:8.2-fpm sh -c 'cp -a /src/. . && rm -rf vendor composer.lock && composer require "symfony/symfony:6.4.*" --no-update -q && composer update --prefer-dist --no-interaction -q && php -d memory_limit=-1 vendor/bin/phpunit tests/ | tail -3'

# SF 7.x PHP 8.2
docker run --rm -v "$(pwd):/src:ro" -w /tmp/test registry.gitlab.com/damienduboeuf/docker/php-fpm-symfony:8.2-fpm sh -c 'cp -a /src/. . && rm -rf vendor composer.lock && composer update --prefer-dist --no-interaction -q && php -d memory_limit=-1 vendor/bin/phpunit tests/ | tail -3'

# SF 7.x PHP 8.4
docker run --rm -v "$(pwd):/src:ro" -w /tmp/test registry.gitlab.com/damienduboeuf/docker/php-fpm-symfony:8.4-fpm sh -c 'cp -a /src/. . && rm -rf vendor composer.lock && composer require "symfony/var-exporter:7.2.*" --no-update -q && composer update --prefer-dist --no-interaction -q && php -d memory_limit=-1 vendor/bin/phpunit tests/ | tail -3'
```

**Why:** Track migration progress across sessions
**How to apply:** Resume from this state. SF 8.0 integration tests pending Doctrine fix.
