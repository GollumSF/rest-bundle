<?php

namespace GollumSF\RestBundle\Metadata\Sort\Handler;

use GollumSF\RestBundle\Attribute\ApiSortable;
use GollumSF\RestBundle\Metadata\Sort\MetadataSort;
use GollumSF\RestBundle\Metadata\Sort\MetadataSortable;

/**
 * Reads #[ApiSortable] from the entity (class level and property level) and from the
 * controller action.
 *
 * The entity declares the catalogue. When the action declares sortables too, it narrows
 * that catalogue down: a bare #[ApiSortable('title')] on the action keeps the entity
 * definition of `title`, while giving a path, a sorter or a direction overrides it.
 * An action key unknown to the entity stands on its own.
 */
class AttributeHandler implements HandlerInterface
{
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

	/**
	 * @return MetadataSortable[] Keyed by sort key.
	 */
	private function getEntitySortables(string $entityClass): array {
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

	/**
	 * @return MetadataSortable[]|null Keyed by sort key, null when the action declares none.
	 */
	private function getActionSortables(?string $controller, ?string $action): ?array {
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

	/**
	 * A bare action declaration keeps the entity definition; anything it sets overrides it.
	 */
	private function mergeSortable(?MetadataSortable $entitySortable, MetadataSortable $actionSortable): MetadataSortable {
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
