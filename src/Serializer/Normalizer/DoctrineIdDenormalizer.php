<?php
namespace GollumSF\RestBundle\Serializer\Normalizer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class DoctrineIdDenormalizer implements DenormalizerInterface {
	
	/**
	 * @var EntityManagerInterface
	 */
	private $em;
	
	/**
	 * @var array
	 */
	private $cache = [];
	
	public function __construct(EntityManagerInterface $em) {
		$this->em = $em;
	}

	public function denormalize($data, $class, $format = null, array $context = []) {
		return $this->em->getRepository($class)->find($data);
	}
	
	public function supportsDenormalization($data, $type, $format = null) {
		if (!array_key_exists($type, $this->cache)) {
			$this->cache[$type] = class_exists($type) && (is_int($data) || is_string($data));
		}
		return $this->cache[$type];
	}
}