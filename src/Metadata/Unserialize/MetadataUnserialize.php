<?php

namespace GollumSF\RestBundle\Metadata\Unserialize;

class MetadataUnserialize {
	
	/** @var string */
	private $name = '';
	
	/** @var string[] */
	private $groups;
	
	/** @var boolean */
	private $save;
	
	public function __construct(string $name, array $groups, bool $save) {
		$this->name = $name;
		$this->groups = $groups;
		$this->save = $save;
	}
	
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
