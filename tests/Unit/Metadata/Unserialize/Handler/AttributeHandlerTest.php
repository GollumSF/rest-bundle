<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Unserialize\Handler;

use GollumSF\RestBundle\Annotation\Unserialize;
use GollumSF\RestBundle\Metadata\Unserialize\Handler\AttributeHandler;
use PHPUnit\Framework\TestCase;

class AnnoDummyNull {
	public function action() {}
}

#[Unserialize('name1', [ 'group1' ], false)]
class AnnoDummyClass {
	public function action() {}
}

class AnnoDummyMethod {
	#[Unserialize('name2', [ 'group2' ], true)]
	public function action() {}
}

#[Unserialize('name1', [ 'group1' ], false)]
class AnnoDummyFull {
	#[Unserialize('name2', [ 'group2' ], true)]
	public function action() {}
}

/**
 * @requires PHP 8.0.0
 */
class AttributeHandlerTest extends TestCase {
	
	public function provideGetMetadata() {
		return [
			[ AnnoDummyNull::class, null, null, null ],
			[ AnnoDummyClass::class, 'name1', [ 'group1' ], false ],
			[ AnnoDummyMethod::class, 'name2', [ 'group2' ], true ],
			[ AnnoDummyFull::class, 'name2', [ 'group2' ], true ],
		];
	}
	
	/**
	 * @dataProvider provideGetMetadata
	 */
	public function testGetMetadata($class, $name, $group, $isSave) {
		
		$handler = new AttributeHandler();
		
		$metadata = $handler->getMetadata($class, 'action');
		
		if ($name === null) {
			$this->assertNull($metadata);
		} else {
			$this->assertEquals($metadata->getName(), $name);
			$this->assertEquals($metadata->getGroups(), $group);
			$this->assertEquals($metadata->isSave(), $isSave);
		}
	}
}
