<?php
namespace Test\GollumSF\RestBundle\Unit\Sort;

use GollumSF\RestBundle\Sort\ApiSortContext;
use PHPUnit\Framework\TestCase;

class ApiSortContextTest extends TestCase {

	public function testConstruct() {
		$context = new ApiSortContext('ENTITY', 'CONTROLLER', 'ACTION');
		$this->assertEquals('ENTITY', $context->getEntityClass());
		$this->assertEquals('CONTROLLER', $context->getController());
		$this->assertEquals('ACTION', $context->getAction());
	}

	public function testConstructDefaults() {
		$context = new ApiSortContext('ENTITY');
		$this->assertEquals('ENTITY', $context->getEntityClass());
		$this->assertNull($context->getController());
		$this->assertNull($context->getAction());
	}
}
