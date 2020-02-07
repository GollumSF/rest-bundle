<?php

namespace GollumSF\RestBundle\Configuration;

class ApiConfiguration implements ApiConfigurationInterface {

	/** @var int */
	private $maxLimitItem;

	/** @var int */
	private $defaultLimitItem;

	/** @var bool */
	private $alwaysSerializedException;

	public function __construct(
		string $maxLimitItem,
		string $defaultLimitItem,
		bool $alwaysSerializedException
	) {
		$this->maxLimitItem = $maxLimitItem;
		$this->defaultLimitItem = $defaultLimitItem;
		$this->alwaysSerializedException = $alwaysSerializedException;
	}
	
	public function getMaxLimitItem(): int {
		return $this->maxLimitItem;
	}
	
	public function getDefaultLimitItem(): int {
		return $this->defaultLimitItem;
	}
	
	public function isAlwaysSerializedException(): bool {
		return $this->alwaysSerializedException;
	}
}