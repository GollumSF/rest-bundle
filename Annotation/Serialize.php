<?php
namespace GollumSF\RestBundle\Annotation;

use Symfony\Component\HttpFoundation\Response;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Serialize {
	
	/**
	 * @var int
	 */
	public $code;
	
	/**
	 * @var string|string[]
	 */
	public $groups;
	
	/**
	 * @var string[]
	 */
	public $headers;
	
	/**
	 * @param string $class
	 */
	public function __construct ($param) {
		$this->groups  = isset ($param["groups"]) ? $param["groups"] : [];
		$this->code    = isset ($param["code"])   ? $param["code"] : Response::HTTP_OK;
		$this->headers = isset ($param["headers"])   ? $param["headers"] : [];
	}
	
}