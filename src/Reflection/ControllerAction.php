<?php

namespace GollumSF\RestBundle\Reflection;

class ControllerAction
{
	/** @var string */
	private $controllerClass;

	/** @var string */
	private $action;
	
	public function __construct(
		string $controllerClass,
		string $action
	) {
		$this->controllerClass = $controllerClass;
		$this->action = $action;
	}

	public function getControllerClass(): string {
		return $this->controllerClass;
	}

	public function getAction(): string {
		return $this->action;
	}
}