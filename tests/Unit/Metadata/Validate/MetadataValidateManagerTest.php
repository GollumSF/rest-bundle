<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Validate;

use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use GollumSF\RestBundle\Metadata\Validate\Handler\HandlerInterface;
use GollumSF\RestBundle\Metadata\Validate\MetadataValidate;
use GollumSF\RestBundle\Metadata\Validate\MetadataValidateManager;
use PHPUnit\Framework\TestCase;

class MetadataValidateManagerTest extends TestCase {
	
	use ReflectionPropertyTrait;
	
	public function testConstructor() {
		
		$handler1 = $this->getMockForAbstractClass(HandlerInterface::class);
		$handler2 = $this->getMockForAbstractClass(HandlerInterface::class);
		
		$manager = new MetadataValidateManager();
		$manager->addHandler($handler1);
		$manager->addHandler($handler2);
		
		$this->assertEquals(
			$this->reflectionGetValue($manager, 'handlers'),
			[
				$handler1,
				$handler2
			]
		);
	}
	
	public function testGetMetadata() {
		
		$metadata = $this->getMockBuilder(MetadataValidate::class)->disableOriginalConstructor()->getMock();
		$handler1 = $this->getMockForAbstractClass(HandlerInterface::class);
		$handler2 = $this->getMockForAbstractClass(HandlerInterface::class);
		
		$handler1
			->expects($this->exactly(2))
			->method('getMetadata')
			->withConsecutive(
				[ 'CONTROLLER1', 'ACTIONS1' ],
				[ 'CONTROLLER2', 'ACTIONS2' ]
			)
			->willReturnOnConsecutiveCalls(
				$metadata,
				null
			)
		;
		
		$handler2
			->expects($this->once())
			->method('getMetadata')
			->with('CONTROLLER2', 'ACTIONS2')
			->willReturn(null)
		;
		
		$manager = new MetadataValidateManager();
		$manager->addHandler($handler1);
		$manager->addHandler($handler2);
		
		$this->assertEquals($manager->getMetadata('CONTROLLER1', 'ACTIONS1'), $metadata);
		$this->assertNull($manager->getMetadata('CONTROLLER2', 'ACTIONS2'));
	}
	
}
