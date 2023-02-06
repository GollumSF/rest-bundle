<?php

namespace GollumSF\RestBundle\Annotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Unserialize {

	const REQUEST_ATTRIBUTE_CLASS = '_gsf_unserialize_class';
	const REQUEST_ATTRIBUTE_NAME = '_gsf_unserialize_name';

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

}
