<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Serialize;

use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use GollumSF\RestBundle\Metadata\Serialize\Handler\HandlerInterface;
use GollumSF\RestBundle\Metadata\Serialize\MetadataSerialize;
use GollumSF\RestBundle\Metadata\Serialize\MetadataSerializeManager;
use PHPUnit\Framework\TestCase;
use Test\GollumSF\RestBundle\Helper\WithConsecutiveTrait;

class MetadataSerializerManagerTest extends TestCase {

	use ReflectionPropertyTrait;
	use WithConsecutiveTrait;

	public function testConstructor() {

		$handler1 = $this->createMock(HandlerInterface::class);
		$handler2 = $this->createMock(HandlerInterface::class);

		$manager = new MetadataSerializeManager();
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

		$metadata = $this->getMockBuilder(MetadataSerialize::class)->disableOriginalConstructor()->getMock();
		$handler1 = $this->createMock(HandlerInterface::class);
		$handler2 = $this->createMock(HandlerInterface::class);

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

		$manager = new MetadataSerializeManager();
		$manager->addHandler($handler1);
		$manager->addHandler($handler2);

		$this->assertEquals($manager->getMetadata('CONTROLLER1', 'ACTIONS1'), $metadata);
		$this->assertNull($manager->getMetadata('CONTROLLER2', 'ACTIONS2'));
	}

}
