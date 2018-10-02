<?php
namespace Serializer\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Unserialize {
	
	
	/**
	 * @var string
	 */
	public $name;
	
	/**
	 * @var string|string[]
	 */
	public $groups;
	
	/**
	 * @var boolean
	 */
	public $save;
	
	/**
	 * @param string $class
	 */
	public function __construct ($param) {
		$this->name   = isset ($param["value"])  ? $param["value"] : (isset ($param["name"]) ? $param["name"] : '');
		$this->groups = isset ($param["groups"]) ? $param["groups"] : [];
		$this->save   = isset ($param["save"])   ? $param["save"] : true;
	}
	
}