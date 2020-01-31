<?php
namespace Test\GollumSF\RestBundle\Model;

use GollumSF\RestBundle\Model\ApiList;
use PHPUnit\Framework\TestCase;

class ApiListTest extends TestCase {
	
	public function testGetter() {
		$apiList = new ApiList([
			'AAA',
			'BBB',
		], 2);
		$this->assertEquals($apiList->getData(), [
			'AAA',
			'BBB',
		]);
		$this->assertEquals($apiList->getTotal(), 2);
	}
	
}