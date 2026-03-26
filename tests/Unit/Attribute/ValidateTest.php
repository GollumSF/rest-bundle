<?php

namespace Test\GollumSF\RestBundle\Unit\Attribute;

use GollumSF\RestBundle\Attribute\Validate;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ValidateTest extends TestCase
{
	public static function provideConstruct() {
		return [
			[ [],  [ 'Default' ] ],
			[ [ 'group1' ], [ 'group1' ] ],
			[ 'group1', [ 'group1' ] ],
		];
	}

	#[DataProvider('provideConstruct')]
	public function testConstruct($groups, $groupsResult) {
		$annotation = new Validate($groups);
		$this->assertEquals($annotation->getGroups(), $groupsResult);
	}

}
