<?php

namespace GollumSF\RestBundle\Serializer\Normalizer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class DoctrineObjectDenormalizer implements DenormalizerInterface {
	
	/** @var EntityManagerInterface */
	private $em;
	
	/** @var RecursiveObjectNormalizer */
	private $recursiveObjectNormalizer;
	
	/** @var array */
	private $cache = [];
	
	public function __construct(
		EntityManagerInterface $em,
		RecursiveObjectNormalizer $recursiveObjectNormalizer
	) {
		$this->em = $em;
		$this->recursiveObjectNormalizer = $recursiveObjectNormalizer;
	}
	
	public function denormalize($data, string $class, string $format = null, array $context = array()) {
		
		if (is_null($data)) {
			return null;
		}
		
		if (isset($context['object_to_populate'])) {
			return $this->recursiveObjectNormalizer->denormalize($data, $class, $format, $context);
		}
		try {
			$metadata = $this->em->getClassMetadata($class);
		} catch (MappingException $e) {
			return $this->recursiveObjectNormalizer->denormalize($data, $class, $format, $context);
		}
		
		$ids = $metadata->getIdentifier();
		if (empty($ids)) {
			return $this->recursiveObjectNormalizer->denormalize($data, $class, $format, $context);
		}
		
		$ctriteria = [];
		foreach ($ids as $id) {
			$ctriteria[$id] = array_key_exists($id, $data) ? $data[$id] : null;
		}
		
		$entity = $this->em->getRepository($class)->findOneBy($ctriteria);
		if ($entity) {
			$context['object_to_populate'] = $entity;
		}
		
		return $this->recursiveObjectNormalizer->denormalize($data, $class, $format, $context);
	}
	
	public function supportsDenormalization($data, string $type, string $format = null) {
		if (!array_key_exists($type, $this->cache)) {
			$this->cache[$type] = class_exists($type) && (is_array($data) || is_null($data));
		}
		return $this->cache[$type];
	}
}