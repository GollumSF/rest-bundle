<?php
namespace Test\GollumSF\RestBundle\Serializer\Normalizer;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use GollumSF\RestBundle\Serializer\Normalizer\DoctrineObjectDenormalizer;
use GollumSF\RestBundle\Serializer\Normalizer\RecursiveObjectNormalizer;
use PHPUnit\Framework\TestCase;


class DoctrineObjectDenormalizerTestaDenormalize extends DoctrineObjectDenormalizer {
	public $em;
	public $repository;
	protected function getEntityManagerForClass($entityOrClass): ?ObjectManager {
		return $this->em;
	}
	protected function getEntityRepositoryForClass($entityOrClass): ?ObjectRepository {
		return $this->repository;
	}
}

class DoctrineObjectDenormalizerTestSupportsDenormalization extends DoctrineObjectDenormalizer {
	public $isEntity;
	protected function isEntity($entityOrClass): bool {
		return $this->isEntity;
	}
}

class DoctrineObjectDenormalizerTest extends TestCase {
	
	use ReflectionPropertyTrait;

	public function testDenormalize() {

		$em = $this->getMockBuilder(ObjectManager::class)
			->getMockForAbstractClass()
		;
		$repository = $this->getMockBuilder(ObjectRepository::class)
			->getMockForAbstractClass()
		;
		$recursiveObjectNormalizer = $this->getMockBuilder(RecursiveObjectNormalizer::class)
			->disableOriginalConstructor()
			->getMock()
		;
		$metadata = $this->getMockBuilder(ClassMetadata::class)
			->disableOriginalConstructor()
			->getMock()
		;
		
		$entity = new \stdClass();
		
		$em
			->method('getClassMetadata')
			->with('STUB_CLASS')
			->willReturn($metadata)
		;
		
		$em
			->method('getRepository')
			->with('STUB_CLASS')
			->willReturn($repository)
		;

		$metadata
			->method('getIdentifier')
			->willReturn([ 'ID' ])
		;
		
		$repository
			->method('findOneBy')
			->with(['ID' => 'VALUE_ID'])
			->willReturn($entity)
		;
		$repository
			->method('find')
			->with('ID')
			->willReturn($entity)
		;
		
		$recursiveObjectNormalizer
			->method('denormalize')
			->with([ 'ID' => 'VALUE_ID' ], 'STUB_CLASS', 'format', [ 'object_to_populate' => $entity ])
			->willReturn($entity)
		;
		
		$doctrineObjectDenormalizer = new DoctrineObjectDenormalizerTestaDenormalize($recursiveObjectNormalizer);
		$doctrineObjectDenormalizer->em = $em;
		$doctrineObjectDenormalizer->repository = $repository;
		
		$this->assertEquals(
			$doctrineObjectDenormalizer->denormalize([ 'ID' => 'VALUE_ID' ], 'STUB_CLASS', 'format'),
			$entity
		);
	}

	public function testDenormalizeNoIds() {

		$em = $this->getMockBuilder(ObjectManager::class)
			->getMockForAbstractClass()
		;
		$recursiveObjectNormalizer = $this->getMockBuilder(RecursiveObjectNormalizer::class)
			->disableOriginalConstructor()
			->getMock()
		;
		$metadata = $this->getMockBuilder(ClassMetadata::class)
			->disableOriginalConstructor()
			->getMock()
		;

		$entity = new \stdClass();

		$em
			->method('getClassMetadata')
			->with('STUB_CLASS')
			->willReturn($metadata)
		;

		$metadata
			->method('getIdentifier')
			->willReturn([])
		;

		$recursiveObjectNormalizer
			->method('denormalize')
			->with([ 'ID' => 'VALUE_ID' ], 'STUB_CLASS', 'format', [])
			->willReturn($entity)
		;

		$doctrineObjectDenormalizer = new DoctrineObjectDenormalizerTestaDenormalize($recursiveObjectNormalizer);
		$doctrineObjectDenormalizer->em = $em;

		$this->assertEquals(
			$doctrineObjectDenormalizer->denormalize([ 'ID' => 'VALUE_ID' ], 'STUB_CLASS', 'format'),
			$entity
		);
	}
	
	public function testDenormalizeObjectPopulate() {

		$em = $this->getMockBuilder(ObjectManager::class)
			->getMockForAbstractClass()
		;
		$recursiveObjectNormalizer = $this->getMockBuilder(RecursiveObjectNormalizer::class)
			->disableOriginalConstructor()
			->getMock()
		;
		$entity = new \stdClass();
		$context = [ 'object_to_populate' => $entity ];

		$recursiveObjectNormalizer
			->method('denormalize')
			->with('ID', 'STUB_CLASS', 'format', $context)
			->willReturn($entity)
		;
		
		$doctrineObjectDenormalizer = new DoctrineObjectDenormalizerTestaDenormalize($recursiveObjectNormalizer);
		$doctrineObjectDenormalizer->em = $em;
		
		$this->assertEquals(
			$doctrineObjectDenormalizer->denormalize('ID', 'STUB_CLASS', 'format', $context),
			$entity
		);
	}


	public function testDenormalizeObjectNull() {

		$em = $this->getMockBuilder(ObjectManager::class)
			->getMockForAbstractClass()
		;
		$recursiveObjectNormalizer = $this->getMockBuilder(RecursiveObjectNormalizer::class)
			->disableOriginalConstructor()
			->getMock()
		;
		
		$doctrineObjectDenormalizer = new DoctrineObjectDenormalizerTestaDenormalize($recursiveObjectNormalizer);
		$doctrineObjectDenormalizer->em = $em;

		$this->assertEquals(
			$doctrineObjectDenormalizer->denormalize(null, 'STUB_CLASS', 'format'),
			null
		);
	}
	
	public function provideSupportsDenormalization() {
		return [
			[ 'STRING', true, \stdClass::class, false],
			[ 1, true, \stdClass::class, false],
			[ new \stdClass(), true, \stdClass::class, false],
			[ [], true, \stdClass::class, true],
			[ null, true, \stdClass::class, true],
			[ [], true, 'STUB_CLASS', false],
			[ null, true, 'STUB_CLASS', false],
			[ [], false, \stdClass::class, false],
			[ null, false, \stdClass::class, false]
		];
	}

	/**
	 * @dataProvider provideSupportsDenormalization
	 */
	public function testSupportsDenormalization($data, $isEntity, $type, $result) {
		$recursiveObjectNormalizer = $this->getMockBuilder(RecursiveObjectNormalizer::class)->disableOriginalConstructor()->getMock();
		
		$doctrineObjectDenormalizer = new DoctrineObjectDenormalizerTestSupportsDenormalization($recursiveObjectNormalizer);
		$doctrineObjectDenormalizer->isEntity = $isEntity;
		
		$this->assertEquals(
			$doctrineObjectDenormalizer->supportsDenormalization($data, $type),
			$result
		);

		$cache = $this->reflectionGetValue($doctrineObjectDenormalizer, 'cache', DoctrineObjectDenormalizer::class);
		$this->assertTrue(array_key_exists($type, $cache));
		$this->assertEquals($cache[$type], $result);

	}

	public function testSupportsDenormalizationNoDoctrine() {
		$recursiveObjectNormalizer = $this->getMockBuilder(RecursiveObjectNormalizer::class)->disableOriginalConstructor()->getMock();
		
		$doctrineIdDenormalizer = new DoctrineObjectDenormalizer($recursiveObjectNormalizer);
		$this->assertFalse(
			$doctrineIdDenormalizer->supportsDenormalization('STRING', \stdClass::class)
		);
	}
}