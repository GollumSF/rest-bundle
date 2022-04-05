<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Serialize\Handler;

use GollumSF\RestBundle\Annotation\Serialize;
use GollumSF\RestBundle\Metadata\Serialize\Handler\AttributeHandler;
use PHPUnit\Framework\TestCase;

class AnnoDummyNull {
	public function action() {}
}

#[Serialize(1, [ 'group1' ], [ 'header1' ])]
class AnnoDummyClass {
	public function action() {}
}

class AnnoDummyMethod {
	#[Serialize(2, [ 'group2' ], [ 'header2' ])]
	public function action() {}
}

#[Serialize(1, [ 'group1' ], [ 'header1' ])]
class AnnoDummyFull {
	#[Serialize(2, [ 'group2' ], [ 'header2' ])]
	public function action() {}
}

/**
 * @requires PHP 8.0.0
 */
class AttributeHandlerTest extends TestCase {
	
	public function provideGetMetadata() {
		return [
			[ AnnoDummyNull::class, null, null, null ],
			[ AnnoDummyClass::class, 1, [ 'group1' ], [ 'header1' ] ],
			[ AnnoDummyMethod::class, 2, [ 'group2' ], [ 'header2' ] ],
			[ AnnoDummyFull::class, 2, [ 'group2' ], [ 'header2' ] ],
		];
	}
	
	/**
	 * @dataProvider provideGetMetadata
	 */
	public function testGetMetadata($class, $code, $group, $header) {
		
		$handler = new AttributeHandler();
		
		$metadata = $handler->getMetadata($class, 'action');
		
		if ($code === null) {
			$this->assertNull($metadata);
		} else {
			$this->assertEquals($metadata->getCode(), $code);
			$this->assertEquals($metadata->getGroups(), $group);
			$this->assertEquals($metadata->getHeaders(), $header);
		}
	}
}
