<?php

namespace Test\GollumSF\RestBundle\Unit\Configuration;

use GollumSF\RestBundle\Configuration\ApiConfiguration;
use PHPUnit\Framework\TestCase;

class ApiConfigurationTest extends TestCase {

	public function testConstructor() {

		$apiConfiguration = new ApiConfiguration(
			42,
			21,
			true
		);

		$this->assertEquals($apiConfiguration->getMaxLimitItem()    , 42);
		$this->assertEquals($apiConfiguration->getDefaultLimitItem(), 21);
		$this->assertEquals($apiConfiguration->isAlwaysSerializedException(), true);

		$apiConfiguration = new ApiConfiguration(
			4242,
			2121,
			false
		);

		$this->assertEquals($apiConfiguration->getMaxLimitItem()    , 4242);
		$this->assertEquals($apiConfiguration->getDefaultLimitItem(), 2121);
		$this->assertEquals($apiConfiguration->isAlwaysSerializedException(), false);
	}
}