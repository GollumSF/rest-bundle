<?php
namespace GollumSF\RestBundle\Serializer\Normalizer;

use Doctrine\Persistence\ManagerRegistry;
use GollumSF\RestBundle\Traits\ManagerRegistryToManager;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class DoctrineIdDenormalizer implements DenormalizerInterface {

	use ManagerRegistryToManager;
	
	/** @var ManagerRegistry */
	private $managerRegistry;
	
	/** @var array */
	private $cache = [];
	
	public function setManagerRegistry(ManagerRegistry $managerRegistry): self {
		$this->managerRegistry = $managerRegistry;
		return $this;
	}

	public function denormalize($data, $class, $format = null, array $context = []) {
		return $this->getEntityRepositoryForClass($class)->find($data);
	}
	
	public function supportsDenormalization($data, $type, $format = null) {
		if (!array_key_exists($type, $this->cache)) {
			$this->cache[$type] = class_exists($type) && $this->isEntity($type) && (is_int($data) || is_string($data));
		}
		return $this->cache[$type];
	}
}