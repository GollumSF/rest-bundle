<?php

namespace Test\GollumSF\RestBundle\Reflection;

use GollumSF\RestBundle\Reflection\ControllerAction;
use PHPUnit\Framework\TestCase;

class ControllerActionTest extends TestCase {
	
	public function testModel() {
		$controllerAction = new ControllerAction(
			'controllerClass',
			'action'
		);

		$this->assertEquals($controllerAction->getControllerClass(), 'controllerClass');
		$this->assertEquals($controllerAction->getAction(), 'action');
	}
}