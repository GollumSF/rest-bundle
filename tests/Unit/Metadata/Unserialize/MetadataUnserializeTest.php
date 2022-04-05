<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Unserialize;

use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserialize;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class MetadataUnserializeTest extends TestCase {
	
	public function provideConstruct() {
		return [
			[ '', [], true ],
			[ '', [], false ],
			[ 'name', [], false ],
			[ '', [ 'group1' ], Response::HTTP_OK ],
		];
	}
	
	/**
	 * @dataProvider provideConstruct
	 */
	public function testConstruct($name, $groups, $isSave) {
		$annotation = new MetadataUnserialize($name, $groups, $isSave);
		$this->assertEquals($annotation->getName(), $name);
		$this->assertEquals($annotation->getGroups(), $groups);
		$this->assertEquals($annotation->isSave(), $isSave);
	}
	
}
