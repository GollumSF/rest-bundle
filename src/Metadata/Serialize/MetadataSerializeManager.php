<?php

namespace GollumSF\RestBundle\Metadata\Serialize;

use GollumSF\RestBundle\Metadata\Serialize\Handler\HandlerInterface;

class MetadataSerializeManager implements MetadataSerializeManagerInterface {
	
	/** @var HandlerInterface[] */
	private $handlers = [];
	
	/**
	 * @var MetadataSerialize[]
	 */
	private $cache = [];
	
	public function addHandler(HandlerInterface $handler): void {
		$this->handlers[] = $handler;
	}
	
	public function getMetadata(string $controller, string $action): ?MetadataSerialize {
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
