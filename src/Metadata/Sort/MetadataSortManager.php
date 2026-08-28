<?php

namespace GollumSF\RestBundle\Metadata\Sort;

use GollumSF\RestBundle\Metadata\Sort\Handler\HandlerInterface;

class MetadataSortManager implements MetadataSortManagerInterface {

	/** @var HandlerInterface[] */
	private $handlers = [];

	/** @var MetadataSort[] */
	private $cache = [];

	public function addHandler(HandlerInterface $handler): void {
		$this->handlers[] = $handler;
	}

	public function getMetadata(string $entityClass, ?string $controller = null, ?string $action = null): MetadataSort {
		$cacheKey = $entityClass.'::'.$controller.'::'.$action;
		if (!array_key_exists($cacheKey, $this->cache)) {
			$this->cache[$cacheKey] = null;
			foreach ($this->handlers as $handler) {
				$metadata = $handler->getMetadata($entityClass, $controller, $action);
				if ($metadata) {
					$this->cache[$cacheKey] = $metadata;
					break;
				}
			}
			if (!$this->cache[$cacheKey]) {
				$this->cache[$cacheKey] = new MetadataSort();
			}
		}
		return $this->cache[$cacheKey];
	}
}
