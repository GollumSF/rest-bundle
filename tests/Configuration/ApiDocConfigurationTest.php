<?php

namespace Test\GollumSF\RestBundle\Configuration;

use GollumSF\RestBundle\Configuration\ApiConfiguration;
use PHPUnit\Framework\TestCase;

class ApiConfigurationTest extends TestCase {

	public function testConstructor() {

		$apiConfiguration = new ApiConfiguration(
			42,
			21
		);

		$this->assertEquals($apiConfiguration->getMaxLimitItem()    , 42);
		$this->assertEquals($apiConfiguration->getDefaultLimitItem(), 21);
	}
}