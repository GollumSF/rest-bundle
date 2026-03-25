<?php

namespace GollumSF\RestBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Unserialize {

	const REQUEST_ATTRIBUTE_CLASS = '_gsf_unserialize_class';
	const REQUEST_ATTRIBUTE_NAME = '_gsf_unserialize_name';

	private string $name;

	/** @var string[] */
	private array $groups;

	private bool $save;

	/**
	 * @param string|string[] $groups
	 */
	public function __construct(
		string $name = '',
		string|array $groups = [],
		bool $save = true
	)
	{
		$this->name = $name;
		$this->groups = is_array($groups) ? $groups : [ $groups ];
		$this->save = $save;
	}

	/////////////
	// Getters //
	/////////////

	public function getName(): string {
		return $this->name;
	}

	public function getGroups(): array {
		return $this->groups;
	}

	public function isSave(): bool {
		return $this->save;
	}

}
