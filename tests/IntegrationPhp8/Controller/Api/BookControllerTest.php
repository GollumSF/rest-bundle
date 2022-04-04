<?php

namespace Test\GollumSF\RestBundle\IntegrationPhp8\Controller\Api;

use Test\GollumSF\RestBundle\Integration\Controller\Api\BookControllerTest as BookControllerTestBase;

/**
 * @requires PHP 8.0.0
 */
class BookControllerTest extends BookControllerTestBase {
	protected function getProjectPath(): string {
		return __DIR__ . '/../../../ProjectTestPhp8';
	}
}
