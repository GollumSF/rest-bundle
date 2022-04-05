<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Serialize;

use GollumSF\RestBundle\Metadata\Serialize\MetadataSerialize;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class MetadataSerializeTest extends TestCase {
	
	
	public function provideConstruct() {
		return [
			[ [], [], Response::HTTP_OK ],
			[ [ 'header_key' => 'header_value' ], [], Response::HTTP_OK ],
			[ [], [ 'group1' ], Response::HTTP_OK ],
			[ [], [], Response::HTTP_INTERNAL_SERVER_ERROR ],
		];
	}
	
	/**
	 * @dataProvider provideConstruct
	 */
	public function testConstruct($headers, $groups, $code) {
		$annotation = new MetadataSerialize($code, $groups, $headers);
		$this->assertEquals($annotation->getHeaders(), $headers);
		$this->assertEquals($annotation->getGroups(), $groups);
		$this->assertEquals($annotation->getCode(), $code);
	}
	
}
