<?php

namespace GollumSF\RestBundle\Metadata\Serialize\Handler;

use GollumSF\RestBundle\Metadata\Serialize\MetadataSerialize;

/**
 * Reads the serialization off `gollum_sf_rest.metadata.controllers`, for the actions you
 * cannot annotate.
 */
class ConfigHandler implements HandlerInterface
{
	/** @var array */
	private $controllers;

	public function __construct(array $controllers = []) {
		$this->controllers = $controllers;
	}

	public function getMetadata(string $controller, string $action): ?MetadataSerialize {

		$config = $this->controllers[$controller.'::'.$action]['serialize'] ?? null;
		if ($config === null) {
			return null;
		}

		return new MetadataSerialize(
			$config['code'],
			$config['groups'],
			$config['headers']
		);
	}
}
