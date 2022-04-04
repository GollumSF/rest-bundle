<?php

namespace Test\GollumSF\RestBundle\IntegrationPhp8\Controller\Api;

use Test\GollumSF\RestBundle\Integration\Controller\Api\AuthorControllerTest as AuthorControllerTestBase;

/**
 * @requires PHP 8.0.0
 */
class AuthorControllerTest extends AuthorControllerTestBase {
	protected function getProjectPath(): string {
		return __DIR__ . '/../../../ProjectTestPhp8';
	}
}
