<?php

namespace GollumSF\RestBundle\Attribute;
use Symfony\Component\HttpFoundation\Response;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Serialize {

	private int $code;

	/** @var string[] */
	private array $groups;

	/** @var string[] */
	private array $headers;

	/**
	 * @param string|string[] $groups
	 * @param string[] $headers
	 */
	public function __construct(
		int $code = Response::HTTP_OK,
		string|array $groups = [],
		array $headers = []
	)
	{
		$this->code = $code;
		$this->groups = is_array($groups) ? $groups : [ $groups ];
		$this->headers = $headers;
	}

	/////////////
	// Getters //
	/////////////

	public function getCode(): int {
		return $this->code;
	}

	public function getGroups(): array {
		return $this->groups;
	}

	public function getHeaders(): array {
		return $this->headers;
	}
}
