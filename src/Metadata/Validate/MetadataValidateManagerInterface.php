<?php

namespace GollumSF\RestBundle\Metadata\Validate;

use GollumSF\RestBundle\Metadata\Validate\Handler\HandlerInterface;

interface MetadataValidateManagerInterface
{
	const HANDLER_TAG = 'gollumsf.rest.metadata.validate_builder.handler';

	/**
	 * @deprecated since 4.2, the tag was capitalised by mistake. Still collected, use
	 *             HANDLER_TAG instead.
	 */
	const HANDLER_TAG_LEGACY = 'gollumsf.rest.metadata.Validate_builder.handler';

	public function addHandler(HandlerInterface $handler): void;
	
	public function getMetadata(string $controller, string $action): ?MetadataValidate;
}
