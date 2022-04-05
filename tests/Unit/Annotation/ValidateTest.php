<?php

namespace Test\GollumSF\RestBundle\Unit\Annotation;

use GollumSF\RestBundle\Annotation\Validate;
use PHPUnit\Framework\TestCase;

class ValidateTest extends TestCase
{
	public function provideConstructLegacy() {
		return [
			[ [],  [ 'Default' ] ],
			[ [
				'value' => 'group1'
			],  ['group1'] ],
			[ [
				'value' => [ 'group1' ]
			], ['group1']],
			[ [
				'groups' => 'group1'
			],  ['group1'] ],
			[ [
				'groups' => [ 'group1' ]
			], ['group1']],
		];
	}
	
	/**
	 * @dataProvider provideConstructLegacy
	 */
	public function testConstructLegacy($param, $groups) {
		$annotation = new Validate($param);
		$this->assertEquals($annotation->getGroups(), $groups);
	}
	
	public function provideConstruct() {
		return [
			[ [],  [ 'Default' ] ],
			[ [ 'group1' ], [ 'group1' ] ],
			[ 'group1', [ 'group1' ] ],
		];
	}
	
	/**
	 * @dataProvider provideConstruct
	 */
	public function testConstruct($groups, $groupsResult) {
		$annotation = new Validate($groups);
		$this->assertEquals($annotation->getGroups(), $groupsResult);
	}
	
}
