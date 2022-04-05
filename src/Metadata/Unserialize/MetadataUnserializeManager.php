<?php

namespace GollumSF\RestBundle\Metadata\Unserialize;

use GollumSF\RestBundle\Metadata\Unserialize\Handler\HandlerInterface;

class MetadataUnserializeManager implements MetadataUnserializeManagerInterface {
	
	/** @var HandlerInterface[] */
	private $handlers = [];
	
	/**
	 * @var MetadataUnserialize[]
	 */
	private $cache = [];
	
	public function addHandler(HandlerInterface $handler): void {
		$this->handlers[] = $handler;
	}
	
	public function getMetadata(string $controller, string $action): ?MetadataUnserialize {
		if (!array_key_exists($controller.'::'.$action, $this->cache)) {
			foreach ($this->handlers as $handler) {
				$metadata = $handler->getMetadata($controller, $action);
				if ($metadata) {
					$this->cache[$controller.'::'.$action] = $metadata;
					break;
				}
			}
			if (!array_key_exists($controller.'::'.$action, $this->cache)) {
				$this->cache[$controller.'::'.$action] = null;
			}
		}
		return $this->cache[$controller.'::'.$action];
	}
}
