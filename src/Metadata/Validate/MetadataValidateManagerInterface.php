<?php

namespace GollumSF\RestBundle\Metadata\Validate;

use GollumSF\RestBundle\Metadata\Validate\Handler\HandlerInterface;

interface MetadataValidateManagerInterface
{
	const HANDLER_TAG = 'gollumsf.rest.metadata.Validate_builder.handler';

	public function addHandler(HandlerInterface $handler): void;
	
	public function getMetadata(string $controller, string $action): ?MetadataValidate;
}
