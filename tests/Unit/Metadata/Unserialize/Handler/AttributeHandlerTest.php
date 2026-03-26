<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Unserialize\Handler;

use GollumSF\RestBundle\Attribute\Unserialize;
use GollumSF\RestBundle\Metadata\Unserialize\Handler\AttributeHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

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

class AttributeHandlerTest extends TestCase {
	
	public static function provideGetMetadata() {
		return [
			[ AnnoDummyNull::class, null, null, null ],
			[ AnnoDummyClass::class, 'name1', [ 'group1' ], false ],
			[ AnnoDummyMethod::class, 'name2', [ 'group2' ], true ],
			[ AnnoDummyFull::class, 'name2', [ 'group2' ], true ],
		];
	}
	
	#[DataProvider('provideGetMetadata')]
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
