<?php

namespace GollumSF\RestBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Validate {

	/** @var string[] */
	private array $groups;

	/**
	 * @param string|string[] $groups
	 */
	public function __construct(
		string|array $groups = [ 'Default' ]
	)
	{
		if (!$groups) {
			$groups = [ 'Default' ];
		}

		$this->groups = is_array($groups) ? $groups : [ $groups ];
	}

	/////////////
	// Getters //
	/////////////

	public function getGroups(): array {
		return $this->groups;
	}

}
