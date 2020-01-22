<?php
namespace Test\GollumSF\RestBundle\Serializer\Normalizer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use GollumSF\RestBundle\Serializer\Normalizer\DoctrineIdDenormalizer;
use PHPUnit\Framework\TestCase;

class DoctrineIdDenormalizerTest extends TestCase {
	
	use ReflectionPropertyTrait;
	
	public function testDenormalize() {

		$em = $this->getMockBuilder(EntityManagerInterface::class)
			->getMockForAbstractClass()
		;
		$repository = $this->getMockBuilder(ObjectRepository::class)
			->getMockForAbstractClass()
		;
		$entity = new \stdClass();

		$em
			->method('getRepository')
			->with('STUB_CLASS')
			->willReturn($repository)
		;

		$repository
			->method('find')
			->with('ID')
			->willReturn($entity)
		;
		
		$doctrineIdDenormalizer = new DoctrineIdDenormalizer($em);
		$this->assertEquals(
			$doctrineIdDenormalizer->denormalize('ID', 'STUB_CLASS', 'format'),
			$entity
		);
	}
	
	public function provideSupportsDenormalization() {
		return [
			[ 'STRING', \stdClass::class, true],
			[ 1, \stdClass::class, true],
			[ 'STRING', 'STUB_CLASS',false],
			[ 1, 'STUB_CLASS', false],
			[ [], \stdClass::class, false],
			[ new \stdClass(), \stdClass::class, false],
			[ null, \stdClass::class, false]
		];
	}

	/**
	 * @dataProvider provideSupportsDenormalization
	 */
	public function testSupportsDenormalization($data, $type, $result) {
		$em = $this->getMockBuilder(EntityManagerInterface::class)
			->getMockForAbstractClass()
		;
		$doctrineIdDenormalizer =  new DoctrineIdDenormalizer($em);
		
		$this->assertEquals(
			$doctrineIdDenormalizer->supportsDenormalization($data, $type),
			$result
		);

		$cache = $this->reflectionGetValue($doctrineIdDenormalizer, 'cache');
		$this->assertTrue(array_key_exists($type, $cache));
		$this->assertEquals($cache[$type], $result);

	}
}