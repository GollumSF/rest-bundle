<?php

namespace GollumSF\RestBundle\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Unserialize implements ConfigurationInterface {

	const ALIAS_NAME = 'gsf_unserialize';
	
	/** @var string */
	private $name = '';
	
	/** @var string[] */
	private $groups;
	
	/** @var boolean */
	private $save;
	
	/**
	 * @param int $name
	 * @param string|string[] $groups
	 * @param bool $save
	 */
	public function __construct(
		$name = '',
		$groups = [],
		$save = true
	)
	{
		if (is_array($name)) {
			if (function_exists('trigger_deprecation')) {
				// @codeCoverageIgnoreStart
				trigger_deprecation('gollumsf/rest_bundle', '2.8', 'Use native php attributes for %s', __CLASS__);
				// @codeCoverageIgnoreEnd
			}
			$this->name = '';
			if (isset($name['value'])) {
				$this->name = $name['value'];
			}
			if (isset($name['name'])) {
				$this->name = $name['name'];
			}
			$this->save = isset($name['save']) ? $name['save'] : true;
			$this->groups =  isset($name['groups']) ? (is_array($name['groups']) ? $name['groups'] : [ $name['groups'] ]) : [];
			
			return;
		}
		$this->name = $name;
		$this->groups = is_array($groups) ? $groups : [ $groups ];
		$this->save = $save;
	}
	
	/////////////
	// Getters //
	/////////////

	public function getName(): string {
		return $this->name;
	}

	public function getGroups(): array {
		return $this->groups;
	}

	public function isSave(): bool {
		return $this->save;
	}

	public function getAliasName() {
		return self::ALIAS_NAME;
	}

	public function allowArray() {
		return false;
	}
	
}
