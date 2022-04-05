<?php
namespace GollumSF\RestBundle\Serializer\Normalizer;

use Doctrine\Persistence\ManagerRegistry;
use GollumSF\RestBundle\Traits\ManagerRegistryToManager;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

// @codeCoverageIgnoreStart
if (version_compare(Kernel::VERSION, '5.0.0', '<')) {
	class DoctrineIdDenormalizer implements DenormalizerInterface {
		
		use DoctrineIdDenormalizerTrait;
		use ManagerRegistryToManager;
		
		/** @var ManagerRegistry */
		private $managerRegistry;
		
		public function setManagerRegistry(ManagerRegistry $managerRegistry): self {
			$this->managerRegistry = $managerRegistry;
			return $this;
		}
		
		public function denormalize($data, $class, $format = null, array $context = []) {
			return $this->denormalizeImplement($data, $class, $format, $context);
		}
		public function supportsDenormalization($data, $type, $format = null): bool {
			return $this->supportsDenormalizationImplement($data, $type, $format);
		}
	}
} else
if (version_compare(Kernel::VERSION, '6.0.0', '<')) {
	class DoctrineIdDenormalizer implements DenormalizerInterface {
		
		use DoctrineIdDenormalizerTrait;
		use ManagerRegistryToManager;
		
		/** @var ManagerRegistry */
		private $managerRegistry;
		
		public function setManagerRegistry(ManagerRegistry $managerRegistry): self {
			$this->managerRegistry = $managerRegistry;
			return $this;
		}
		
		public function denormalize($data, string $class, string $format = null, array $context = []) {
			return $this->denormalizeImplement($data, $class, $format, $context);
		}
		public function supportsDenormalization($data, string $type, string $format = null): bool {
			return $this->supportsDenormalizationImplement($data, $type, $format);
		}
	}
} else {
	class DoctrineIdDenormalizer implements DenormalizerInterface {
		
		use DoctrineIdDenormalizerTrait;
		use ManagerRegistryToManager;
		
		/** @var ManagerRegistry */
		private $managerRegistry;
		
		public function setManagerRegistry(ManagerRegistry $managerRegistry): self {
			$this->managerRegistry = $managerRegistry;
			return $this;
		}
		
		public function denormalize(mixed $data, string $class, string $format = null, array $context = []): mixed {
			return $this->denormalizeImplement($data, $class, $format, $context);
		}
		public function supportsDenormalization(mixed $data, string $type, string $format = null): bool {
			return $this->supportsDenormalizationImplement($data, $type, $format);
		}
	}
}
// @codeCoverageIgnoreEnd
