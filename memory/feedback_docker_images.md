---
name: Use user's pre-configured Docker images
description: User has pre-built Docker images with PHP+Symfony at registry.gitlab.com/damienduboeuf/docker/php-fpm-symfony - use them instead of building from scratch
type: feedback
---

Use the user's Docker images for testing: `registry.gitlab.com/damienduboeuf/docker/php-fpm-symfony:{version}-fpm`

Available tags: 8.0-fpm, 8.1-fpm, 8.2-fpm, 8.3-fpm, 8.4-fpm (probably)

**Why:** User has told me multiple times to use these. They have PHP, composer, extensions pre-installed. Stop using `php:8.2-cli` and installing everything from scratch each time.
**How to apply:** For all Docker test runs, use `registry.gitlab.com/damienduboeuf/docker/php-fpm-symfony:X.Y-fpm` instead of `php:X.Y-cli`.
