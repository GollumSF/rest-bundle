<?php
namespace Serializer\Serializer\Normalizer;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Exception\BadMethodCallException;
use Symfony\Component\Serializer\Exception\ExtraAttributesException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class DoctrineIdNormalizer implements DenormalizerInterface {
	
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
	
	public function denormalize($data, $class, $format = null, array $context = array()) {
		return $this->em->getRepository($class)->find($data);
	}
	
	public function supportsDenormalization($data, $type, $format = null) {
		return
			(isset($this->cache[$type]) ? $this->cache[$type] : $this->cache[$type] = class_exists($type)) &&
			is_int($data)
		;
	}
}