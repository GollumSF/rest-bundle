<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Validate\Handler;

use GollumSF\RestBundle\Annotation\Validate;
use GollumSF\RestBundle\Metadata\Validate\Handler\AttributeHandler;
use PHPUnit\Framework\TestCase;

class AnnoDummyNull {
	public function action() {}
}

#[Validate([ 'group1' ])]
class AnnoDummyClass {
	public function action() {}
}

class AnnoDummyMethod {
	#[Validate([ 'group2' ])]
	public function action() {}
}

#[Validate([ 'group1' ])]
class AnnoDummyFull {
	#[Validate([ 'group2' ])]
	public function action() {}
}

/**
 * @requires PHP 8.0.0
 */
class AttributeHandlerTest extends TestCase {
	
	public function provideGetMetadata() {
		return [
			[ AnnoDummyNull::class, null ],
			[ AnnoDummyClass::class, [ 'group1' ] ],
			[ AnnoDummyMethod::class, [ 'group2' ] ],
			[ AnnoDummyFull::class, [ 'group2' ] ],
		];
	}
	
	/**
	 * @dataProvider provideGetMetadata
	 */
	public function testGetMetadata($class, $group) {
		
		$handler = new AttributeHandler();
		
		$metadata = $handler->getMetadata($class, 'action');
		
		if ($group === null) {
			$this->assertNull($metadata);
		} else {
			$this->assertEquals($metadata->getGroups(), $group);
		}
	}
}
