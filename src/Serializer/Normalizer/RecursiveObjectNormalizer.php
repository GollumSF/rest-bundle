<?php
namespace GollumSF\RestBundle\Serializer\Normalizer;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

class RecursiveObjectNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface {

	private ObjectNormalizer $inner;

	public function __construct(
		?ClassMetadataFactoryInterface $classMetadataFactory = null,
		?NameConverterInterface $nameConverter = null,
		?PropertyAccessorInterface $propertyAccessor = null
	) {
		$this->inner = new ObjectNormalizer(
			$classMetadataFactory,
			$nameConverter,
			$propertyAccessor,
			new ReflectionExtractor()
		);
	}

	public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null {
		return $this->inner->normalize($object, $format, $context);
	}

	public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool {
		return $this->inner->supportsNormalization($data, $format, $context);
	}

	public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed {
		return $this->inner->denormalize($data, $type, $format, $context);
	}

	public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool {
		return $this->inner->supportsDenormalization($data, $type, $format, $context);
	}

	public function getSupportedTypes(?string $format): array {
		return $this->inner->getSupportedTypes($format);
	}

	public function setSerializer(SerializerInterface $serializer): void {
		$this->inner->setSerializer($serializer);
	}
}
