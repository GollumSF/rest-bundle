<?php

namespace GollumSF\RestBundle\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Validate implements ConfigurationInterface {

	const ALIAS_NAME = 'gsf_validate';
	
	/** @var string[] */
	private $groups;
	
	/**
	 * @param string|string[] $groups
	 */
	public function __construct(
		$groups = [ 'Default' ]
	)
	{
		if (is_array($groups) && count($groups) && (isset($groups['groups']) || isset($groups['value']))) {
			if (function_exists('trigger_deprecation')) {
				// @codeCoverageIgnoreStart
				trigger_deprecation('gollumsf/rest_bundle', '2.8', 'Use native php attributes for %s', __CLASS__);
				// @codeCoverageIgnoreEnd
			}
			
			$this->groups = [ 'Default' ];
			if (isset($groups['groups'])) {
				$this->groups = $groups['groups'] ? (is_array($groups['groups']) ? $groups['groups'] : [$groups['groups']]) : $this->groups;
			}
			if (isset($groups['value'])) {
				$this->groups = $groups['value'] ? (is_array($groups['value']) ? $groups['value'] : [$groups['value']]) : $this->groups;
			}
			return;
		}
		
		if (!$groups) {
			$groups = [ 'Default' ];
		}
		
		$this->groups = is_array($groups) ? $groups : [ $groups ];
	}
	
	/////////////
	// Getters //
	/////////////
	
	public function getGroups(): array {
		return $this->groups;
	}

	public function getAliasName() {
		return self::ALIAS_NAME;
	}

	public function allowArray() {
		return false;
	}
	
}
