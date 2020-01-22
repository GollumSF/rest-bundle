<?php

namespace Test\GollumSF\RestBundle\Annotation;

use GollumSF\RestBundle\Annotation\Serialize;
use GollumSF\RestBundle\Annotation\Unserialize;
use GollumSF\RestBundle\Annotation\Validate;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ValidateTest extends TestCase
{
	
	public function provideConstruct() {
		return [
			[ [],  [ 'Default' ] ],
			[ [
				'value' => 'group1'
			],  'group1' ],
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
		$this->assertEquals($annotation->groups, $groups);
	}
	
}