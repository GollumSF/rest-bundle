<?php
namespace GollumSF\RestBundle\Serializer\Normalizer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\ObjectRepository;
use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use PHPUnit\Framework\TestCase;

class DoctrineObjectDenormalizerTest extends TestCase {
	
	use ReflectionPropertyTrait;

	public function testDenormalize() {

		$em = $this->getMockBuilder(EntityManagerInterface::class)
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
		
		$recursiveObjectNormalizer
			->method('denormalize')
			->with([ 'ID' => 'VALUE_ID' ], 'STUB_CLASS', 'format', [ 'object_to_populate' => $entity ])
			->willReturn($entity)
		;
			
		$doctrineObjectDenormalizer = new DoctrineObjectDenormalizer($em, $recursiveObjectNormalizer);
		
		$repository
			->method('find')
			->with('ID')
			->willReturn($entity)
		;
		
		$this->assertEquals(
			$doctrineObjectDenormalizer->denormalize([ 'ID' => 'VALUE_ID' ], 'STUB_CLASS', 'format'),
			$entity
		);
	}

	public function testDenormalizeNoIds() {

		$em = $this->getMockBuilder(EntityManagerInterface::class)
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

		$doctrineObjectDenormalizer = new DoctrineObjectDenormalizer($em, $recursiveObjectNormalizer);

		$this->assertEquals(
			$doctrineObjectDenormalizer->denormalize([ 'ID' => 'VALUE_ID' ], 'STUB_CLASS', 'format'),
			$entity
		);
	}
	
	public function testDenormalizeNoEntity() {

		$em = $this->getMockBuilder(EntityManagerInterface::class)
			->getMockForAbstractClass()
		;
		$recursiveObjectNormalizer = $this->getMockBuilder(RecursiveObjectNormalizer::class)
			->disableOriginalConstructor()
			->getMock()
		;
		$entity = new \stdClass();

		$em
			->method('getClassMetadata')
			->with('STUB_CLASS')
			->willThrowException(new MappingException())
		;

		$recursiveObjectNormalizer
			->method('denormalize')
			->with('ID', 'STUB_CLASS', 'format', [])
			->willReturn($entity)
		;

		$doctrineObjectDenormalizer = new DoctrineObjectDenormalizer($em, $recursiveObjectNormalizer);

		$this->assertEquals(
			$doctrineObjectDenormalizer->denormalize('ID', 'STUB_CLASS', 'format'),
			$entity
		);
	}
	
	public function testDenormalizeObjectPopulate() {

		$em = $this->getMockBuilder(EntityManagerInterface::class)
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
		
		
		$doctrineObjectDenormalizer = new DoctrineObjectDenormalizer($em, $recursiveObjectNormalizer);
		
		$this->assertEquals(
			$doctrineObjectDenormalizer->denormalize('ID', 'STUB_CLASS', 'format', $context),
			$entity
		);
	}


	public function testDenormalizeObjectNull() {

		$em = $this->getMockBuilder(EntityManagerInterface::class)
			->getMockForAbstractClass()
		;
		$recursiveObjectNormalizer = $this->getMockBuilder(RecursiveObjectNormalizer::class)
			->disableOriginalConstructor()
			->getMock()
		;
		
		$doctrineObjectDenormalizer = new DoctrineObjectDenormalizer($em, $recursiveObjectNormalizer);

		$this->assertEquals(
			$doctrineObjectDenormalizer->denormalize(null, 'STUB_CLASS', 'format'),
			null
		);
	}
	
	public function provideSupportsDenormalization() {
		return [
			[ 'STRING', \stdClass::class, false],
			[ 1, \stdClass::class, false],
			[ new \stdClass(), \stdClass::class, false],
			[ [], \stdClass::class, true],
			[ null, \stdClass::class, true],
			[ [], 'STUB_CLASS', false],
			[ null, 'STUB_CLASS', false]
		];
	}

	/**
	 * @dataProvider provideSupportsDenormalization
	 */
	public function testSupportsDenormalization($data, $type, $result) {
		$em = $this->getMockBuilder(EntityManagerInterface::class)
			->getMockForAbstractClass()
		;
		$recursiveObjectNormalizer = $this->getMockBuilder(RecursiveObjectNormalizer::class)
			->disableOriginalConstructor()
			->getMock()
		;
		$doctrineObjectDenormalizer = new DoctrineObjectDenormalizer($em, $recursiveObjectNormalizer);
		
		$this->assertEquals(
			$doctrineObjectDenormalizer->supportsDenormalization($data, $type),
			$result
		);

		$cache = $this->reflectionGetValue($doctrineObjectDenormalizer, 'cache');
		$this->assertTrue(array_key_exists($type, $cache));
		$this->assertEquals($cache[$type], $result);

	}
}