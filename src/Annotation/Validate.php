<?php

namespace GollumSF\RestBundle\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Validate extends ConfigurationAnnotation {

	const ALIAS_NAME = 'gsf_validate';
	
	/** @var string[] */
	private $groups = [ 'Default' ];
	
	/////////////
	// Getters //
	/////////////
	
	public function getGroups(): array {
		return $this->groups;
	}

	public function getAliasName() {
		return self::ALIAS_NAME;
	}

	public function allowArray() {
		return false;
	}

	/////////////
	// Setters //
	/////////////

	/**
	 * @param string|string[] $groups
	 */
	public function setGroups($groups): self {
		if (!is_array($groups)) {
			$groups = [$groups];
		}
		$this->groups = $groups;
		return $this;
	}

	public function setValue($groups): self {
		return $this->setGroups($groups);
	}
	
}