<?php

namespace GollumSF\RestBundle\Metadata\Unserialize\Handler;

use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserialize;

/**
 * Reads the unserialization off `gollum_sf_rest.metadata.controllers`, for the actions
 * you cannot annotate.
 */
class ConfigHandler implements HandlerInterface
{
	/** @var array */
	private $controllers;

	public function __construct(array $controllers = []) {
		$this->controllers = $controllers;
	}

	public function getMetadata(string $controller, string $action): ?MetadataUnserialize {

		$config = $this->controllers[$controller.'::'.$action]['unserialize'] ?? null;
		if ($config === null) {
			return null;
		}

		return new MetadataUnserialize(
			$config['name'],
			$config['groups'],
			$config['save'],
			$config['type'] ?: $this->resolveType($controller, $action, $config['name'])
		);
	}

	/**
	 * Same rule as the attribute: without an explicit type, the one of the action
	 * parameter the declaration points at.
	 */
	private function resolveType(string $controller, string $action, string $name): ?string {
		if (!$name || !class_exists($controller)) {
			return null;
		}
		$rClass = new \ReflectionClass($controller);
		if (!$rClass->hasMethod($action)) {
			return null;
		}
		foreach ($rClass->getMethod($action)->getParameters() as $rParameter) {
			if ($rParameter->getName() !== $name) {
				continue;
			}
			$rType = $rParameter->getType();
			return $rType instanceof \ReflectionNamedType && !$rType->isBuiltin()
				? $rType->getName()
				: null
			;
		}
		return null;
	}
}
