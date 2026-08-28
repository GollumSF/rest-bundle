<?php

namespace GollumSF\RestBundle\Attribute;

use GollumSF\RestBundle\Model\Direction;

/**
 * Declares a sort key usable through the `order` query parameter.
 *
 * On an entity property the key and the path both default to the property name:
 *
 *     #[ApiSortable]
 *     private string $title;                                    // ?order=title
 *
 *     #[ApiSortable(path: 'author.name')]                       // ?order=author
 *     private Author $author;
 *
 * On an entity class, or on a controller action, the key is required:
 *
 *     #[ApiSortable('author', path: 'author.name')]
 *     #[ApiSortable('popularity', sorter: PopularitySorter::class)]
 *
 * Declaring at least one sortable turns the whitelist on for that entity: any other
 * key is then rejected. Declaring none keeps the legacy permissive behaviour.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class ApiSortable {

	private ?string $key;

	private ?string $path;

	private ?string $sorter;

	private ?Direction $direction;

	/**
	 * @param string|null $key       Public name in the `order` query parameter.
	 *                               Defaults to the property name when on a property.
	 * @param string|null $path      Property path relative to the entity, dots crossing
	 *                               relations (`author.country.name`). Defaults to the key.
	 * @param string|null $sorter    Class name of an ApiSorterInterface service, for
	 *                               orderings no path can express.
	 * @param Direction|null $direction Direction applied when the request asks for none.
	 */
	public function __construct(
		?string $key = null,
		?string $path = null,
		?string $sorter = null,
		?Direction $direction = null
	) {
		$this->key = $key;
		$this->path = $path;
		$this->sorter = $sorter;
		$this->direction = $direction;
	}

	/////////////
	// Getters //
	/////////////

	public function getKey(): ?string {
		return $this->key;
	}

	public function getPath(): ?string {
		return $this->path;
	}

	public function getSorter(): ?string {
		return $this->sorter;
	}

	public function getDirection(): ?Direction {
		return $this->direction;
	}
}
