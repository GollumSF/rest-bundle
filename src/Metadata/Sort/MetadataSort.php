<?php

namespace GollumSF\RestBundle\Metadata\Sort;

/**
 * The sort keys an entity exposes. An empty collection means no whitelist was
 * declared: the legacy permissive behaviour applies.
 */
class MetadataSort {

	/** @var MetadataSortable[] */
	private $sortables = [];

	/**
	 * @param MetadataSortable[] $sortables
	 */
	public function __construct(array $sortables = []) {
		foreach ($sortables as $sortable) {
			$this->sortables[$sortable->getKey()] = $sortable;
		}
	}

	public function has(string $key): bool {
		return array_key_exists($key, $this->sortables);
	}

	public function get(string $key): ?MetadataSortable {
		return $this->sortables[$key] ?? null;
	}

	/**
	 * @return MetadataSortable[] Keyed by sort key.
	 */
	public function all(): array {
		return $this->sortables;
	}

	/**
	 * @return string[]
	 */
	public function getKeys(): array {
		return array_keys($this->sortables);
	}

	public function isEmpty(): bool {
		return !$this->sortables;
	}
}
