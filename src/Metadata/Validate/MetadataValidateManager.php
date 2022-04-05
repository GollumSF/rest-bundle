<?php

namespace GollumSF\RestBundle\Metadata\Validate;

use GollumSF\RestBundle\Metadata\Validate\Handler\HandlerInterface;

class MetadataValidateManager implements MetadataValidateManagerInterface {
	
	/** @var HandlerInterface[] */
	private $handlers = [];
	
	/**
	 * @var MetadataValidate[]
	 */
	private $cache = [];
	
	public function addHandler(HandlerInterface $handler): void {
		$this->handlers[] = $handler;
	}
	
	public function getMetadata(string $controller, string $action): ?MetadataValidate {
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
