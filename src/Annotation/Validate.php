<?php

namespace GollumSF\RestBundle\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Validate {
	
	
	/**
	 * @var string|string[]
	 */
	public $groups;
	
	/**
	 * @param string $class
	 */
	public function __construct ($param) {
		$this->groups = isset ($param["value"]) ? $param["value"] : [];
	}
	
}