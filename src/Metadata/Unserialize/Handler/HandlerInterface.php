<?php

namespace GollumSF\RestBundle\Metadata\Unserialize\Handler;

use GollumSF\RestBundle\Metadata\Unserialize\MetadataUnserialize;

interface HandlerInterface {
	public function getMetadata(string $controller, string $action): ?MetadataUnserialize;
}
