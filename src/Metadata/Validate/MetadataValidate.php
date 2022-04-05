<?php

namespace GollumSF\RestBundle\Metadata\Validate;

class MetadataValidate {
	
	/** @var string[] */
	private $groups;
	
	public function __construct(array $groups) {
		$this->groups = $groups;
	}
	
	public function getGroups(): array {
		return $this->groups;
	}
	
}
