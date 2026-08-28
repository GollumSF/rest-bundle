<?php

namespace Test\GollumSF\RestBundle\Unit\Attribute;

use GollumSF\RestBundle\Attribute\Unserialize;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\Attributes\DataProvider;

class UnserializeTest extends TestCase
{
	public static function provideConstruct() {
		return [
			[ '', [], true, [], null ],
			[ 'anno_name', [], Response::HTTP_OK, [], null ],
			[ '', 'group1', true, ['group1'], null ],
			[ '', [ 'group1' ], true, [ 'group1' ], null ],
			[ '', [], false, [], null ],
			[ 'anno_name', [], true, [], \stdClass::class ],
		];
	}

	#[DataProvider('provideConstruct')]
	public function testConstruct($name, $groups, $save, $groupsResult, $type) {
		$annotation = new Unserialize($name, $groups, $save, $type);
		$this->assertEquals($annotation->getName(), $name);
		$this->assertEquals($annotation->getGroups(), $groupsResult);
		$this->assertEquals($annotation->isSave(), $save);
		$this->assertEquals($annotation->getType(), $type);
	}

	public function testTypeDefaultsToNull() {
		$annotation = new Unserialize('book');
		$this->assertNull($annotation->getType());
	}

}
