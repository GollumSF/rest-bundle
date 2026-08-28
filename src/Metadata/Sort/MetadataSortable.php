<?php

namespace GollumSF\RestBundle\Metadata\Sort;

use GollumSF\RestBundle\Model\Direction;

/**
 * One resolved sort key of an entity.
 */
class MetadataSortable {

	/** @var string */
	private $key;

	/** @var string|null */
	private $path;

	/** @var string|null */
	private $sorter;

	/** @var Direction|null */
	private $direction;

	public function __construct(string $key, ?string $path = null, ?string $sorter = null, ?Direction $direction = null) {
		$this->key = $key;
		$this->path = $path;
		$this->sorter = $sorter;
		$this->direction = $direction;
	}

	public function getKey(): string {
		return $this->key;
	}

	/**
	 * Property path relative to the entity, dots crossing relations.
	 * Null when the ordering is delegated to a sorter.
	 */
	public function getPath(): ?string {
		return $this->path;
	}

	/** Class name of an ApiSorterInterface service. */
	public function getSorter(): ?string {
		return $this->sorter;
	}

	/** Direction used when the request asks for none. */
	public function getDirection(): ?Direction {
		return $this->direction;
	}
}
