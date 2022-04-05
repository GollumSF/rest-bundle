<?php

namespace GollumSF\RestBundle\Metadata\Serialize;

use GollumSF\RestBundle\Metadata\Serialize\Handler\HandlerInterface;

interface MetadataSerializeManagerInterface
{
	const HANDLER_TAG = 'gollumsf.rest.metadata.serialize_builder.handler';

	public function addHandler(HandlerInterface $handler): void;
	
	public function getMetadata(string $controller, string $action): ?MetadataSerialize;
}
