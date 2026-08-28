<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Unserialize;

use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserialize;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\Attributes\DataProvider;

class MetadataUnserializeTest extends TestCase {
	
	public static function provideConstruct() {
		return [
			[ '', [], true, null ],
			[ '', [], false, null ],
			[ 'name', [], false, null ],
			[ '', [ 'group1' ], Response::HTTP_OK, null ],
			[ 'name', [], true, \stdClass::class ],
		];
	}
	
	#[DataProvider('provideConstruct')]
	public function testConstruct($name, $groups, $isSave, $type) {
		$annotation = new MetadataUnserialize($name, $groups, $isSave, $type);
		$this->assertEquals($annotation->getName(), $name);
		$this->assertEquals($annotation->getGroups(), $groups);
		$this->assertEquals($annotation->isSave(), $isSave);
		$this->assertEquals($annotation->getType(), $type);
	}
	
	public function testTypeDefaultsToNull() {
		$annotation = new MetadataUnserialize('name', [], true);
		$this->assertNull($annotation->getType());
	}
	
}
