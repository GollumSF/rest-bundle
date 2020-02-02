<?php

namespace GollumSF\RestBundle\Annotation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Serialize extends ConfigurationAnnotation {

	const ALIAS_NAME = 'gsf_serialize';

	/**
	 * @var int
	 */
	private $code = Response::HTTP_OK;

	/**
	 * @var string[]
	 */
	private $groups = [];

	/**
	 * @var string[]
	 */
	private $headers = [];

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

	public function getAliasName() {
		return self::ALIAS_NAME;
	}

	public function allowArray() {
		return false;
	}

	/////////////
	// Setters //
	/////////////

	public function setCode(int $code): self {
		$this->code = $code;
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

	public function setHeaders(array $headers): self {
		$this->headers = $headers;
		return $this;
	}
}