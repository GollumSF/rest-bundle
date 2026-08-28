<?php
namespace GollumSF\RestBundle\Model;

use GollumSF\RestBundle\Sort\ApiSorterInterface;

/**
 * A single resolved ordering: the public key asked for, the direction, and how to
 * apply it — either a property path relative to the root entity, or a handler.
 */
class ApiOrder {

	/** @var string */
	private $key;

	/** @var Direction */
	private $direction;

	/** @var string|null */
	private $path;

	/** @var ApiSorterInterface|null */
	private $sorter;

	public function __construct(
		string $key,
		Direction $direction,
		?string $path = null,
		?ApiSorterInterface $sorter = null
	) {
		$this->key = $key;
		$this->direction = $direction;
		$this->path = $path;
		$this->sorter = $sorter;
	}

	public function getKey(): string {
		return $this->key;
	}

	public function getDirection(): Direction {
		return $this->direction;
	}

	/**
	 * Property path relative to the root entity, dots crossing relations
	 * (`title`, `author.name`, `author.country.name`).
	 */
	public function getPath(): ?string {
		return $this->path;
	}

	public function getSorter(): ?ApiSorterInterface {
		return $this->sorter;
	}
}
