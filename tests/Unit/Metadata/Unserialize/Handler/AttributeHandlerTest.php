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

class AnnoDummyEntity {}
class AnnoDummyInput {}

class AnnoDummyTyped {
	#[Unserialize('entity')]
	public function action(AnnoDummyEntity $entity) {}
}

class AnnoDummyForcedType {
	#[Unserialize('entity', type: AnnoDummyInput::class)]
	public function action(AnnoDummyEntity $entity) {}
}

class AnnoDummyUntyped {
	#[Unserialize('entity')]
	public function action($entity) {}
}

class AnnoDummyBuiltinType {
	#[Unserialize('entity')]
	public function action(string $entity) {}
}

class AnnoDummyMissingParameter {
	#[Unserialize('entity')]
	public function action(AnnoDummyEntity $other) {}
}

#[Unserialize('entity')]
class AnnoDummyClassLevelTyped {
	public function action(AnnoDummyEntity $entity) {}
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

	public static function provideGetMetadataType() {
		return [
			'type from the targeted parameter'   => [ AnnoDummyTyped::class, AnnoDummyEntity::class ],
			'type forced on the attribute'       => [ AnnoDummyForcedType::class, AnnoDummyInput::class ],
			'untyped parameter'                  => [ AnnoDummyUntyped::class, null ],
			'builtin typed parameter'            => [ AnnoDummyBuiltinType::class, null ],
			'no parameter with that name'        => [ AnnoDummyMissingParameter::class, null ],
			'class level attribute'              => [ AnnoDummyClassLevelTyped::class, AnnoDummyEntity::class ],
			'no parameter at all'                => [ AnnoDummyMethod::class, null ],
		];
	}

	#[DataProvider('provideGetMetadataType')]
	public function testGetMetadataType($class, $type) {

		$handler = new AttributeHandler();

		$metadata = $handler->getMetadata($class, 'action');

		$this->assertEquals($type, $metadata->getType());
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
