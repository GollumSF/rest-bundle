<?php

namespace GollumSF\RestBundle\Metadata\Sort\Handler;

use GollumSF\RestBundle\Metadata\Sort\MetadataSortable;
use GollumSF\RestBundle\Model\Direction;

/**
 * Reads the sortables off `gollum_sf_rest.metadata`, for the entities and the
 * controllers you cannot annotate.
 */
class ConfigHandler extends AbstractHandler
{
	/** @var array */
	private $entities;

	/** @var array */
	private $controllers;

	public function __construct(array $entities = [], array $controllers = []) {
		$this->entities = $entities;
		$this->controllers = $controllers;
	}

	protected function getEntitySortables(string $entityClass): array {
		return $this->createSortables($this->entities[$entityClass]['sortable'] ?? []);
	}

	protected function getActionSortables(?string $controller, ?string $action): ?array {
		if (!$controller || !$action) {
			return null;
		}
		$sortables = $this->createSortables($this->controllers[$controller.'::'.$action]['sortable'] ?? []);
		return $sortables ?: null;
	}

	/**
	 * @return MetadataSortable[] Keyed by sort key.
	 */
	private function createSortables(array $config): array {
		$sortables = [];
		foreach ($config as $key => $sortable) {
			$path = $sortable['path'] ?? null;
			if (!$path && !($sortable['sorter'] ?? null)) {
				$path = $key;
			}
			$sortables[$key] = new MetadataSortable(
				$key,
				$path,
				$sortable['sorter'] ?? null,
				isset($sortable['direction']) ? Direction::from($sortable['direction']) : null
			);
		}
		return $sortables;
	}
}
