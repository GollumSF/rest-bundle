<?php

namespace GollumSF\RestBundle\Metadata\Serialize;

class MetadataSerialize {
	
	/**
	 * @var int
	 */
	private $code;
	
	/**
	 * @var string[]
	 */
	private $groups;
	
	/**
	 * @var string[]
	 */
	private $headers;
	
	/**
	 * @param int $code
	 * @param string[] $groups
	 * @param string[] $headers
	 */
	public function __construct(int $code, array $groups, array $headers) {
		$this->code = $code;
		$this->groups = $groups;
		$this->headers = $headers;
	}
	
	public function getCode(): int {
		return $this->code;
	}
	
	/**
	 * @return string[]
	 */
	public function getGroups(): array {
		return $this->groups;
	}
	
	/**
	 * @return string[]
	 */
	public function getHeaders(): array {
		return $this->headers;
	}
	
	
}
