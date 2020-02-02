<?php

namespace GollumSF\RestBundle\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Unserialize extends ConfigurationAnnotation {

	const ALIAS_NAME = 'gsf_unserialize';
	
	/** @var string */
	private $name = '';
	
	/** @var string[] */
	private $groups = [];
	
	/** @var boolean */
	private $save = true;
	
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

	public function getAliasName() {
		return self::ALIAS_NAME;
	}

	public function allowArray() {
		return false;
	}

	/////////////
	// Setters //
	/////////////

	public function setName(string $name): self {
		$this->name = $name;
		return $this;
	}

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

	public function setSave(bool $save): self {
		$this->save = $save;
		return $this;
	}

	public function setValue(string $name): self {
		return $this->setName($name);
	}
	
}