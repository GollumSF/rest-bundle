<?php

namespace GollumSF\RestBundle\Metadata\Unserialize;

use GollumSF\RestBundle\Metadata\Unserialize\Handler\HandlerInterface;

interface MetadataUnserializeManagerInterface
{
	const HANDLER_TAG = 'gollumsf.rest.metadata.unserialize_builder.handler';

	public function addHandler(HandlerInterface $handler): void;
	
	public function getMetadata(string $controller, string $action): ?MetadataUnserialize;
}
