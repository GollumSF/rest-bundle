<?php

namespace Test\GollumSF\RestBundle\Unit\Attribute;

use GollumSF\RestBundle\Attribute\Validate;
use PHPUnit\Framework\TestCase;

class ValidateTest extends TestCase
{
	public static function provideConstruct() {
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
