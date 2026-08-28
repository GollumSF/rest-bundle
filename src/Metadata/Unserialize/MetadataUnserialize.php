<?php

namespace GollumSF\RestBundle\Metadata\Unserialize;

class MetadataUnserialize {
	
	/** @var string */
	private $name = '';
	
	/** @var string[] */
	private $groups;
	
	/** @var boolean */
	private $save;
	
	/** @var string|null */
	private $type;
	
	public function __construct(string $name, array $groups, bool $save, ?string $type = null) {
		$this->name = $name;
		$this->groups = $groups;
		$this->save = $save;
		$this->type = $type;
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
	
	/**
	 * Target class of the unserialization, resolved from the type of the
	 * controller parameter named getName() (or forced on the attribute).
	 */
	public function getType(): ?string {
		return $this->type;
	}
	
}
