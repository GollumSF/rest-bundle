<?php
namespace GollumSF\RestBundle\Serializer\Normalizer;

trait DoctrineIdDenormalizerTrait {
	
	/** @var array */
	private $cache = [];
	
	protected function denormalizeImplement($data, string $class, string $format = null, array $context = []) {
		return $this->getEntityRepositoryForClass($class)->find($data);
	}
	
	protected function supportsDenormalizationImplement($data, string $type, string $format = null): bool {
		if (!array_key_exists($type, $this->cache)) {
			$this->cache[$type] = class_exists($type) && $this->isEntity($type) && (is_int($data) || is_string($data));
		}
		return $this->cache[$type];
	}
}
