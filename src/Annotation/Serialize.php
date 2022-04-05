<?php

namespace GollumSF\RestBundle\Annotation;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Serialize {

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
	 * @param string|string[] $fullUrl
	 * @param string[] $key
	 */
	public function __construct(
		$code = Response::HTTP_OK,
		$groups = [],
		$headers = []
	)
	{
		if (is_array($code)) {
			if (function_exists('trigger_deprecation')) {
				// @codeCoverageIgnoreStart
				trigger_deprecation('gollumsf/rest_bundle', '2.8', 'Use native php attributes for %s', __CLASS__);
				// @codeCoverageIgnoreEnd
			}
			$this->code = isset($code['code']) ? $code['code'] : Response::HTTP_OK;
			$this->headers = isset($code['headers']) ? $code['headers'] : [];
			
			$this->groups = [];
			if (isset($code['groups'])) {
				$this->groups = $code['groups'] ? (is_array($code['groups']) ? $code['groups'] : [$code['groups']]) : $this->groups;
			}
			if (isset($code['value'])) {
				$this->groups = $code['value'] ? (is_array($code['value']) ? $code['value'] : [$code['value']]) : $this->groups;
			}
			
			return;
		}
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
