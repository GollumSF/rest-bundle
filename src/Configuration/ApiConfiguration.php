<?php

namespace GollumSF\RestBundle\Configuration;

class ApiConfiguration implements ApiConfigurationInterface {

	/** @var int */
	private $maxLimitItem;

	/** @var int */
	private $defaultLimitItem;

	public function __construct(
		string $maxLimitItem,
		string $defaultLimitItem
	) {
		$this->maxLimitItem = $maxLimitItem;
		$this->defaultLimitItem = $defaultLimitItem;
	}
	
	public function getMaxLimitItem(): int {
		return $this->maxLimitItem;
	}
	
	public function getDefaultLimitItem(): int {
		return $this->defaultLimitItem;
	}
}