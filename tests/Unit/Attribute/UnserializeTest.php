<?php

namespace Test\GollumSF\RestBundle\Unit\Attribute;

use GollumSF\RestBundle\Attribute\Unserialize;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class UnserializeTest extends TestCase
{
	public static function provideConstruct() {
		return [
			[ '', [], true, [] ],
			[ 'anno_name', [], Response::HTTP_OK, [] ],
			[ '', 'group1', true, ['group1'] ],
			[ '', [ 'group1' ], true, [ 'group1' ] ],
			[ '', [], false, []],
		];
	}

	/**
	 * @dataProvider provideConstruct
	 */
	public function testConstruct($name, $groups, $save, $groupsResult) {
		$annotation = new Unserialize($name, $groups, $save);
		$this->assertEquals($annotation->getName(), $name);
		$this->assertEquals($annotation->getGroups(), $groupsResult);
		$this->assertEquals($annotation->isSave(), $save);
	}

}
