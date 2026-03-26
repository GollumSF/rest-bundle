<?php

namespace Test\GollumSF\RestBundle\Unit\Attribute;

use GollumSF\RestBundle\Attribute\Serialize;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\Attributes\DataProvider;

class SerializeTest extends TestCase
{
	public static function provideConstruct() {
		return [
			[ [], [], Response::HTTP_OK, [] ],
			[ [ 'header_key' => 'header_value' ], [], Response::HTTP_OK, [] ],
			[ [], 'group1', Response::HTTP_OK, [ 'group1' ] ],
			[ [], [ 'group1' ], Response::HTTP_OK, [ 'group1' ] ],
			[ [], [], Response::HTTP_INTERNAL_SERVER_ERROR, [] ],
		];
	}

	#[DataProvider('provideConstruct')]
	public function testConstruct($headers, $groups, $code, $groupsResult) {
		$annotation = new Serialize($code, $groups, $headers);
		$this->assertEquals($annotation->getHeaders(), $headers);
		$this->assertEquals($annotation->getGroups(), $groupsResult);
		$this->assertEquals($annotation->getCode(), $code);
	}

}
