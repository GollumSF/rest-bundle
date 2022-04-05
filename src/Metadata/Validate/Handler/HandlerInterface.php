<?php

namespace GollumSF\RestBundle\Metadata\Validate\Handler;

use GollumSF\RestBundle\Metadata\Validate\MetadataValidate;

interface HandlerInterface {
	public function getMetadata(string $controller, string $action): ?MetadataValidate;
}
