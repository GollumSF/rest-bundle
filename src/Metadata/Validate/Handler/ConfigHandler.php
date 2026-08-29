<?php

namespace GollumSF\RestBundle\Metadata\Validate\Handler;

use GollumSF\RestBundle\Metadata\Validate\MetadataValidate;

/**
 * Reads the validation off `gollum_sf_rest.metadata.controllers`, for the actions you
 * cannot annotate.
 */
class ConfigHandler implements HandlerInterface
{
	/** @var array */
	private $controllers;

	public function __construct(array $controllers = []) {
		$this->controllers = $controllers;
	}

	public function getMetadata(string $controller, string $action): ?MetadataValidate {

		$config = $this->controllers[$controller.'::'.$action]['validate'] ?? null;
		if ($config === null) {
			return null;
		}

		return new MetadataValidate($config['groups']);
	}
}
