<?php
namespace Test\GollumSF\RestBundle\Traits;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\Persistence\Proxy;
use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use GollumSF\RestBundle\Traits\ManagerRegistryToManager;
use PHPUnit\Framework\TestCase;

class ClassManagerRegistryToManager {

	use ManagerRegistryToManager;

	private $managerRegistry;

	public function __construct(ManagerRegistry $managerRegistry = null) {
		$this->managerRegistry = $managerRegistry;
	}
}
class ClassManagerRegistryToManagerTestGetEntityManagerForClass extends ClassManagerRegistryToManager {
	protected function getEntityClass($entityOrClass): string {
		return $entityOrClass;
	}
}
class ClassManagerRegistryToManagerTestIsEntity extends ClassManagerRegistryToManager {
	public $em;
	protected function getEntityManagerForClass($entityOrClass): ?ObjectManager {
		return $this->em;
	}
	protected function getEntityClass($entityOrClass): string {
		return $entityOrClass;
	}
}
class ClassManagerRegistryToManagerTestGetEntityRepositoryForClass extends ClassManagerRegistryToManager {
	public $em;
	protected function getEntityManagerForClass($entityOrClass): ?ObjectManager {
		return $this->em;
	}
	protected function getEntityClass($entityOrClass): string {
		return $entityOrClass;
	}
}


class ClassManagerRegistryToManagerNoProperty {
	use ManagerRegistryToManager;
}

class DummyProxy extends \stdClass implements Proxy {
	public function __load() {}
	public function __isInitialized() {}
}

class ManagerRegistryToManagerTest extends TestCase
{

	use ReflectionPropertyTrait;

	public function providerGetEntityClass() {
		return [
			[ new \stdClass(), \stdClass::class],
			[ new DummyProxy(), \stdClass::class],
			[ \stdClass::class, \stdClass::class],
			[ DummyProxy::class, \stdClass::class],
		];
	}

	/**
	 * @dataProvider providerGetEntityClass
	 */
	public function testGetEntityClass($obj, $className) {
		$managerRegistryToManager = new ClassManagerRegistryToManager();
		$this->assertEquals(
			$this->reflectionCallMethod($managerRegistryToManager, 'getEntityClass', [$obj]), $className
		);
	}

	public function testGetEntityManagerForClass() {
		$managerRegistry = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
		$em = $this->getMockForAbstractClass(ObjectManager::class);
		$managerRegistryToManager = new ClassManagerRegistryToManagerTestGetEntityManagerForClass($managerRegistry);

		$managerRegistry
			->expects($this->once())
			->method('getManagerForClass')
			->with(\stdClass::class)
			->willReturn($em)
		;

		$this->assertEquals(
			$this->reflectionCallMethod($managerRegistryToManager, 'getEntityManagerForClass', [\stdClass::class]), $em
		);
	}
	
	public function testIsEntity() {
		$managerRegistry = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
		$metadataFactory = $this->getMockForAbstractClass(ClassMetadataFactory::class);
		$em = $this->getMockForAbstractClass(ObjectManager::class);
		$managerRegistryToManager = new ClassManagerRegistryToManagerTestIsEntity($managerRegistry);
		$managerRegistryToManager->em = $em;
		
		$em
			->expects($this->once())
			->method('getMetadataFactory')
			->willReturn($metadataFactory)
		;
		$metadataFactory
			->expects($this->once())
			->method('isTransient')
			->with(\stdClass::class)
			->willReturn(false)
		;
		$this->assertTrue($this->reflectionCallMethod($managerRegistryToManager, 'isEntity', [\stdClass::class]));
	}

	public function testGetEntityRepositoryForClass() {
		$managerRegistry = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
		$em = $this->getMockForAbstractClass(ObjectManager::class);
		$repository = $this->getMockForAbstractClass(ObjectRepository::class);
		$managerRegistryToManager = new ClassManagerRegistryToManagerTestGetEntityRepositoryForClass($managerRegistry);
		$managerRegistryToManager->em = $em;

		$em
			->expects($this->once())
			->method('getRepository')
			->with(\stdClass::class)
			->willReturn($repository)
		;
		
		$this->assertEquals(
			$this->reflectionCallMethod($managerRegistryToManager, 'getEntityRepositoryForClass', [\stdClass::class]), $repository
		);
	}

	public function testGetEntityRegistryNull() {
		$managerRegistryToManager = new ClassManagerRegistryToManager();
		$this->assertNull($this->reflectionCallMethod($managerRegistryToManager, 'getEntityManagerForClass', [\stdClass::class]));
		$this->assertNull($this->reflectionCallMethod($managerRegistryToManager, 'getEntityRepositoryForClass', [\stdClass::class]));
		$this->assertFalse($this->reflectionCallMethod($managerRegistryToManager, 'isEntity', [\stdClass::class]));
	}

	public function testNoRegistry() {
		$managerRegistryToManager = new ClassManagerRegistryToManagerNoProperty();
		$this->assertNull(
			$this->reflectionCallMethod($managerRegistryToManager, 'getEntityManagerForClass', [\stdClass::class])
		);
	}
}