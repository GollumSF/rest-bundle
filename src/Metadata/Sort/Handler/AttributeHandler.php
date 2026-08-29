<?php

namespace GollumSF\RestBundle\Metadata\Sort\Handler;

use GollumSF\RestBundle\Attribute\ApiSortable;
use GollumSF\RestBundle\Metadata\Sort\MetadataSortable;

/**
 * Reads #[ApiSortable] from the entity, class level and property level, and from the
 * controller action.
 */
class AttributeHandler extends AbstractHandler
{
	protected function getEntitySortables(string $entityClass): array {
		if (!class_exists($entityClass)) {
			return [];
		}

		$rClass = new \ReflectionClass($entityClass);
		$sortables = [];

		foreach ($rClass->getAttributes(ApiSortable::class) as $rAttribute) {
			$sortable = $this->createSortable($rAttribute->newInstance(), null);
			if ($sortable) {
				$sortables[$sortable->getKey()] = $sortable;
			}
		}

		foreach ($rClass->getProperties() as $rProperty) {
			foreach ($rProperty->getAttributes(ApiSortable::class) as $rAttribute) {
				$sortable = $this->createSortable($rAttribute->newInstance(), $rProperty->getName());
				if ($sortable) {
					$sortables[$sortable->getKey()] = $sortable;
				}
			}
		}

		return $sortables;
	}

	protected function getActionSortables(?string $controller, ?string $action): ?array {
		if (!$controller || !$action || !class_exists($controller)) {
			return null;
		}

		$rClass = new \ReflectionClass($controller);
		if (!$rClass->hasMethod($action)) {
			return null;
		}

		$sortables = [];
		foreach ($rClass->getMethod($action)->getAttributes(ApiSortable::class) as $rAttribute) {
			$sortable = $this->createSortable($rAttribute->newInstance(), null);
			if ($sortable) {
				$sortables[$sortable->getKey()] = $sortable;
			}
		}

		return $sortables ?: null;
	}

	private function createSortable(ApiSortable $annotation, ?string $propertyName): ?MetadataSortable {
		$key = $annotation->getKey() ?: $propertyName;
		if (!$key) {
			return null;
		}
		$path = $annotation->getPath();
		if (!$path && !$annotation->getSorter()) {
			$path = $propertyName ?: $key;
		}
		return new MetadataSortable($key, $path, $annotation->getSorter(), $annotation->getDirection());
	}

}
