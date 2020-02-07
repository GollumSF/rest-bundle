<?php

namespace Test\GollumSF\RestBundle\Unit\Annotation;

use GollumSF\RestBundle\Annotation\Validate;
use PHPUnit\Framework\TestCase;

class ValidateTest extends TestCase
{
	
	public function provideConstruct() {
		return [
			[ [],  [ 'Default' ] ],
			[ [
				'value' => 'group1'
			],  ['group1'] ],
			[ [
				'value' => [ 'group1' ]
			], ['group1']],
		];
	}

	/**
	 * @dataProvider provideConstruct
	 */
	public function testConstruct($param, $groups) {
		$annotation = new Validate($param);
		$this->assertEquals($annotation->getGroups(), $groups);
		$this->assertEquals($annotation->getAliasName(), Validate::ALIAS_NAME);
		$this->assertFalse($annotation->allowArray());
	}
	
}