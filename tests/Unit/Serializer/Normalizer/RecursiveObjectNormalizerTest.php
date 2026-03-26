<?php
namespace Test\GollumSF\RestBundle\Unit\Serializer\Normalizer;

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
			->createMock(ClassMetadataFactoryInterface::class)
		;
		$nameConverter = $this
			->createMock(NameConverterInterface::class)
		;
		$propertyAccessor = $this
			->createMock(PropertyAccessorInterface::class)
		;

		$recursiveObjectNormalizer = new RecursiveObjectNormalizer(
			$classMetadataFactory,
			$nameConverter,
			$propertyAccessor
		);

		// Get the inner ObjectNormalizer
		$inner = $this->reflectionGetValue($recursiveObjectNormalizer, 'inner');
		$this->assertInstanceOf(ObjectNormalizer::class, $inner);

		$this->assertEquals($this->reflectionGetValue($inner, 'classMetadataFactory', AbstractNormalizer::class), $classMetadataFactory);
		$this->assertEquals($this->reflectionGetValue($inner, 'nameConverter'       , AbstractNormalizer::class), $nameConverter);
		$this->assertEquals($this->reflectionGetValue($inner, 'propertyAccessor'    , ObjectNormalizer::class), $propertyAccessor);
		$this->assertInstanceOf(ReflectionExtractor::class, $this->reflectionGetValue($inner, 'propertyTypeExtractor', AbstractObjectNormalizer::class));
	}

}
