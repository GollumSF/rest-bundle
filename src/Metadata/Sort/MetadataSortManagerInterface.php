<?php

namespace GollumSF\RestBundle\Metadata\Sort;

use GollumSF\RestBundle\Metadata\Sort\Handler\HandlerInterface;

interface MetadataSortManagerInterface
{
	const HANDLER_TAG = 'gollumsf.rest.metadata.sort_builder.handler';

	public function addHandler(HandlerInterface $handler): void;

	/**
	 * Never null: an empty MetadataSort means no whitelist was declared.
	 */
	public function getMetadata(string $entityClass, ?string $controller = null, ?string $action = null): MetadataSort;
}
