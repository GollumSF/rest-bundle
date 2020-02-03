<?php
namespace Test\GollumSF\RestBundle\Serializer\Normalizer;

use Doctrine\Persistence\ObjectRepository;
use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use GollumSF\RestBundle\Serializer\Normalizer\DoctrineIdDenormalizer;
use PHPUnit\Framework\TestCase;

class DoctrineIdDenormalizerTestDenormalize extends DoctrineIdDenormalizer {
	public $repository;
	protected function getEntityRepositoryForClass($entityOrClass): ?ObjectRepository {
		return $this->repository;
	}
}
class DoctrineIdDenormalizerTestSupportsDenormalization extends DoctrineIdDenormalizer {
	public $isEntity;
	protected function isEntity($entityOrClass): bool {
		return $this->isEntity;
	}
}

class DoctrineIdDenormalizerTest extends TestCase {
	
	use ReflectionPropertyTrait;
	
	public function testDenormalize() {

		$repository = $this->getMockBuilder(ObjectRepository::class)
			->getMockForAbstractClass()
		;
		$entity = new \stdClass();

		$repository
			->method('find')
			->with('ID')
			->willReturn($entity)
		;
		
		$doctrineIdDenormalizer = new DoctrineIdDenormalizerTestDenormalize();
		$doctrineIdDenormalizer->repository = $repository;
		$this->assertEquals(
			$doctrineIdDenormalizer->denormalize('ID', 'STUB_CLASS', 'format'),
			$entity
		);
	}
	
	public function provideSupportsDenormalization() {
		return [
			[ 'STRING', true, \stdClass::class, true],
			[ 1, true, \stdClass::class, true],
			[ 'STRING', true, 'STUB_CLASS',false],
			[ 1, true, 'STUB_CLASS', false],
			[ [], true, \stdClass::class, false],
			[ new \stdClass(), true, \stdClass::class, false],
			[ null, true, \stdClass::class, false],
			[ 'STRING', false, \stdClass::class, false],
			[ 1, false, \stdClass::class, false],
		];
	}

	/**
	 * @dataProvider provideSupportsDenormalization
	 */
	public function testSupportsDenormalization($data, $isEntity, $type, $result) {
		$doctrineIdDenormalizer = new DoctrineIdDenormalizerTestSupportsDenormalization();
		$doctrineIdDenormalizer->isEntity = $isEntity;

		$this->assertEquals(
			$doctrineIdDenormalizer->supportsDenormalization($data, $type),
			$result
		);

		$cache = $this->reflectionGetValue($doctrineIdDenormalizer, 'cache', DoctrineIdDenormalizer::class);
		$this->assertTrue(array_key_exists($type, $cache));
		$this->assertEquals($cache[$type], $result);

	}

	public function testSupportsDenormalizationNoDoctrine() {
		$doctrineIdDenormalizer = new DoctrineIdDenormalizer();
		$this->assertFalse(
			$doctrineIdDenormalizer->supportsDenormalization('STRING', \stdClass::class)
		);
	}
}