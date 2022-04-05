<?php

namespace GollumSF\RestBundle\Metadata\Serialize\Handler;

use GollumSF\RestBundle\Metadata\Serialize\MetadataSerialize;

interface HandlerInterface {
	public function getMetadata(string $controller, string $action): ?MetadataSerialize;
}
