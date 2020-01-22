<?php
namespace Test\GollumSF\RestBundle\Serializer\Normalizer;

use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use GollumSF\RestBundle\Serializer\Normalizer\RecursiveObjectNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class RecursiveObjectNormalizerTest extends TestCase {
	
	use ReflectionPropertyTrait;

	public function testConstructor() {

		$classMetadataFactory = $this
			->getMockBuilder(ClassMetadataFactoryInterface::class)
			->getMockForAbstractClass()
		;
		$nameConverter = $this
			->getMockBuilder(NameConverterInterface::class)
			->getMockForAbstractClass()
		;
		$propertyAccessor = $this
			->getMockBuilder(PropertyAccessorInterface::class)
			->getMockForAbstractClass()
		;

		$recursiveObjectNormalizer = new RecursiveObjectNormalizer(
			$classMetadataFactory,
			$nameConverter,
			$propertyAccessor
		);
		
		$this->assertEquals($this->reflectionGetValue($recursiveObjectNormalizer, 'classMetadataFactory', AbstractNormalizer::class), $classMetadataFactory);
		$this->assertEquals($this->reflectionGetValue($recursiveObjectNormalizer, 'nameConverter'       , AbstractNormalizer::class), $nameConverter);
		$this->assertEquals($this->reflectionGetValue($recursiveObjectNormalizer, 'propertyAccessor'    , ObjectNormalizer::class), $propertyAccessor);
		$this->assertInstanceOf(ReflectionExtractor::class, $this->reflectionGetValue($recursiveObjectNormalizer, 'propertyTypeExtractor', AbstractObjectNormalizer::class));
	}

}