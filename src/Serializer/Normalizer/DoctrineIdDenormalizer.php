<?php
namespace GollumSF\RestBundle\Serializer\Normalizer;

use Doctrine\Persistence\ManagerRegistry;
use GollumSF\RestBundle\Traits\ManagerRegistryToManager;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class DoctrineIdDenormalizer implements DenormalizerInterface {
	use DoctrineIdDenormalizerTrait;
	use ManagerRegistryToManager;

	private ?ManagerRegistry $managerRegistry = null;

	public function setManagerRegistry(ManagerRegistry $managerRegistry): self {
		$this->managerRegistry = $managerRegistry;
		return $this;
	}

	public function denormalize(mixed $data, string $class, ?string $format = null, array $context = []): mixed {
		return $this->denormalizeImplement($data, $class, $format, $context);
	}
	public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool {
		return $this->supportsDenormalizationImplement($data, $type, $format);
	}
	public function getSupportedTypes(?string $format): array {
		return ['*' => false];
	}
}
