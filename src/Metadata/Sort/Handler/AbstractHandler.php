<?php

namespace GollumSF\RestBundle\Metadata\Sort\Handler;

use GollumSF\RestBundle\Metadata\Sort\MetadataSort;
use GollumSF\RestBundle\Metadata\Sort\MetadataSortable;

/**
 * The entity declares the catalogue. When the action declares sortables too, it narrows
 * that catalogue down: naming a key alone keeps the entity definition, while giving a
 * path, a sorter or a direction overrides it. An action key unknown to the entity stands
 * on its own.
 */
abstract class AbstractHandler implements HandlerInterface
{
	/**
	 * @return MetadataSortable[] Keyed by sort key.
	 */
	abstract protected function getEntitySortables(string $entityClass): array;

	/**
	 * @return MetadataSortable[]|null Keyed by sort key, null when the action declares none.
	 */
	abstract protected function getActionSortables(?string $controller, ?string $action): ?array;

	public function getMetadata(string $entityClass, ?string $controller = null, ?string $action = null): ?MetadataSort {

		$entitySortables = $this->getEntitySortables($entityClass);
		$actionSortables = $this->getActionSortables($controller, $action);

		if ($actionSortables === null) {
			return $entitySortables ? new MetadataSort($entitySortables) : null;
		}

		$sortables = [];
		foreach ($actionSortables as $key => $sortable) {
			$sortables[] = $this->mergeSortable($entitySortables[$key] ?? null, $sortable);
		}

		return $sortables ? new MetadataSort($sortables) : null;
	}

	protected function mergeSortable(?MetadataSortable $entitySortable, MetadataSortable $actionSortable): MetadataSortable {
		if (!$entitySortable) {
			return $actionSortable;
		}
		$overridesPath = $actionSortable->getPath() !== null && $actionSortable->getPath() !== $actionSortable->getKey();
		return new MetadataSortable(
			$actionSortable->getKey(),
			$overridesPath || $actionSortable->getSorter() ? $actionSortable->getPath() : $entitySortable->getPath(),
			$actionSortable->getSorter() ?: ($overridesPath ? null : $entitySortable->getSorter()),
			$actionSortable->getDirection() ?: $entitySortable->getDirection()
		);
	}
}
