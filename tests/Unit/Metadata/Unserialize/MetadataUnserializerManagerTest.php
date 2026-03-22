<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Unserialize;

use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use GollumSF\RestBundle\Metadata\Unserialize\Handler\HandlerInterface;
use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserialize;
use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserializeManager;
use PHPUnit\Framework\TestCase;
use Test\GollumSF\RestBundle\Helper\WithConsecutiveTrait;

class MetadataUnserializerManagerTest extends TestCase {

	use ReflectionPropertyTrait;
	use WithConsecutiveTrait;

	public function testConstructor() {

		$handler1 = $this->getMockForAbstractClass(HandlerInterface::class);
		$handler2 = $this->getMockForAbstractClass(HandlerInterface::class);

		$manager = new MetadataUnserializeManager();
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

		$metadata = $this->getMockBuilder(MetadataUnserialize::class)->disableOriginalConstructor()->getMock();
		$handler1 = $this->getMockForAbstractClass(HandlerInterface::class);
		$handler2 = $this->getMockForAbstractClass(HandlerInterface::class);

		[$callback, $count] = self::withConsecutiveArgs(
			[[ 'CONTROLLER1', 'ACTIONS1' ], [ 'CONTROLLER2', 'ACTIONS2' ]],
			[$metadata, null]
		);
		$handler1
			->expects($this->exactly($count))
			->method('getMetadata')
			->willReturnCallback($callback)
		;

		$handler2
			->expects($this->once())
			->method('getMetadata')
			->with('CONTROLLER2', 'ACTIONS2')
			->willReturn(null)
		;

		$manager = new MetadataUnserializeManager();
		$manager->addHandler($handler1);
		$manager->addHandler($handler2);

		$this->assertEquals($manager->getMetadata('CONTROLLER1', 'ACTIONS1'), $metadata);
		$this->assertNull($manager->getMetadata('CONTROLLER2', 'ACTIONS2'));
	}

}
