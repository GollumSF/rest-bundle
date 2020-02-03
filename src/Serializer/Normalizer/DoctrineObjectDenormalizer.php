<?php

namespace GollumSF\RestBundle\Serializer\Normalizer;

use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\ManagerRegistry;
use GollumSF\RestBundle\Traits\ManagerRegistryToManager;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class DoctrineObjectDenormalizer implements DenormalizerInterface {

	use ManagerRegistryToManager;

	/** @var ManagerRegistry */
	private $managerRegistry;
	
	/** @var RecursiveObjectNormalizer */
	private $recursiveObjectNormalizer;
	
	/** @var array */
	private $cache = [];
	
	public function __construct(
		RecursiveObjectNormalizer $recursiveObjectNormalizer
	) {
		$this->recursiveObjectNormalizer = $recursiveObjectNormalizer;
	}

	public function setManagerRegistry(ManagerRegistry $managerRegistry): self {
		$this->managerRegistry = $managerRegistry;
		return $this;
	}
	
	public function denormalize($data, $class, $format = null, array $context = array()) {
		
		if (is_null($data)) {
			return null;
		}
		
		if (isset($context['object_to_populate'])) {
			return $this->recursiveObjectNormalizer->denormalize($data, $class, $format, $context);
		}
		
		$em = $this->getEntityManagerForClass($class);
		$metadata = $em->getClassMetadata($class);
		
		$ids = $metadata->getIdentifier();
		if (empty($ids)) {
			return $this->recursiveObjectNormalizer->denormalize($data, $class, $format, $context);
		}
		
		$ctriteria = [];
		foreach ($ids as $id) {
			$ctriteria[$id] = array_key_exists($id, $data) ? $data[$id] : null;
		}
		
		$entity = $this->getEntityRepositoryForClass($class)->findOneBy($ctriteria);
		if ($entity) {
			$context['object_to_populate'] = $entity;
		}
		
		return $this->recursiveObjectNormalizer->denormalize($data, $class, $format, $context);
	}
	
	public function supportsDenormalization($data, $type, $format = null) {
		if (!array_key_exists($type, $this->cache)) {
			$this->cache[$type] = class_exists($type) && $this->isEntity($type) && (is_array($data) || is_null($data));
		}
		return $this->cache[$type];
	}
}