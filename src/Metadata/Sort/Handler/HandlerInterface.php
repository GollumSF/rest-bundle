<?php

namespace GollumSF\RestBundle\Metadata\Sort\Handler;

use GollumSF\RestBundle\Metadata\Sort\MetadataSort;

interface HandlerInterface {

	/**
	 * @param string      $entityClass Listed entity.
	 * @param string|null $controller  Controller class of the current action, when known.
	 * @param string|null $action      Action method of the current action, when known.
	 */
	public function getMetadata(string $entityClass, ?string $controller = null, ?string $action = null): ?MetadataSort;
}
