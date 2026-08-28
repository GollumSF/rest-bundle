<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Sort;

use GollumSF\RestBundle\Metadata\Sort\Handler\HandlerInterface;
use GollumSF\RestBundle\Metadata\Sort\MetadataSort;
use GollumSF\RestBundle\Metadata\Sort\MetadataSortable;
use GollumSF\RestBundle\Metadata\Sort\MetadataSortManager;
use PHPUnit\Framework\TestCase;

class MetadataSortManagerTest extends TestCase {

	public function testGetMetadataWithoutHandlerIsEmpty() {
		$manager = new MetadataSortManager();
		$metadata = $manager->getMetadata('ENTITY');
		$this->assertInstanceOf(MetadataSort::class, $metadata);
		$this->assertTrue($metadata->isEmpty());
	}

	public function testTheFirstHandlerAnsweringWins() {

		$metadata = new MetadataSort([ new MetadataSortable('title', 'title') ]);

		$declining = $this->createMock(HandlerInterface::class);
		$declining
			->expects($this->once())
			->method('getMetadata')
			->with('ENTITY', 'CONTROLLER', 'ACTION')
			->willReturn(null)
		;

		$accepting = $this->createMock(HandlerInterface::class);
		$accepting
			->expects($this->once())
			->method('getMetadata')
			->with('ENTITY', 'CONTROLLER', 'ACTION')
			->willReturn($metadata)
		;

		$never = $this->createMock(HandlerInterface::class);
		$never->expects($this->never())->method('getMetadata');

		$manager = new MetadataSortManager();
		$manager->addHandler($declining);
		$manager->addHandler($accepting);
		$manager->addHandler($never);

		$this->assertSame($metadata, $manager->getMetadata('ENTITY', 'CONTROLLER', 'ACTION'));
	}

	public function testTheResultIsCachedPerEntityAndAction() {

		$metadata = new MetadataSort([ new MetadataSortable('title', 'title') ]);

		$handler = $this->createMock(HandlerInterface::class);
		$handler
			->expects($this->exactly(2))
			->method('getMetadata')
			->willReturn($metadata)
		;

		$manager = new MetadataSortManager();
		$manager->addHandler($handler);

		$this->assertSame($metadata, $manager->getMetadata('ENTITY', 'CONTROLLER', 'ACTION'));
		$this->assertSame($metadata, $manager->getMetadata('ENTITY', 'CONTROLLER', 'ACTION'));
		$this->assertSame($metadata, $manager->getMetadata('ENTITY', 'CONTROLLER', 'OTHER'));
	}

	public function testAnEmptyResultIsCachedToo() {

		$handler = $this->createMock(HandlerInterface::class);
		$handler
			->expects($this->once())
			->method('getMetadata')
			->willReturn(null)
		;

		$manager = new MetadataSortManager();
		$manager->addHandler($handler);

		$this->assertTrue($manager->getMetadata('ENTITY')->isEmpty());
		$this->assertTrue($manager->getMetadata('ENTITY')->isEmpty());
	}
}
