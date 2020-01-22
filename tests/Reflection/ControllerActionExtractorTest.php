<?php

namespace Test\GollumSF\RestBundle\Reflection;

use GollumSF\RestBundle\Reflection\ControllerAction;
use GollumSF\RestBundle\Reflection\ControllerActionExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StubController {
	public function action() {
	}
}

class ControllerActionExtractorTest extends TestCase {
	
	public function providerExtractFromString() {
		return [
			[ StubController::class, StubController::class, '__invoke' ],
			[ StubController::class.'::action', StubController::class, 'action' ],
			[ [ StubController::class, 'action' ], StubController::class, 'action' ],
			[ [ new StubController(), 'action' ], StubController::class, 'action' ],
		];
	}

	/**
	 * @dataProvider providerExtractFromString
	 */
	public function testExtractFromString($controllerAction, $controllerClass, $action) {

		$container = $this->getMockBuilder(ContainerInterface::class)
			->getMockForAbstractClass()
		;
			
		$container
			->method('has')
			->with($controllerClass)
			->willReturn(false)
		;

		$controllerActionExtractor = new ControllerActionExtractor($container);

		$controllerAction = $controllerActionExtractor->extractFromString($controllerAction);
		$this->assertInstanceOf(ControllerAction::class, $controllerAction);
		$this->assertEquals($controllerAction->getControllerClass(), $controllerClass);
		$this->assertEquals($controllerAction->getAction(), $action);
	}

	public function testExtractFromStringService() {

		$container = $this->getMockBuilder(ContainerInterface::class)
			->getMockForAbstractClass()
		;

		$container
			->method('has')
			->with('serviceName')
			->willReturn(true)
		;
		$container
			->method('get')
			->with('serviceName')
			->willReturn(new StubController())
		;
		
		$controllerActionExtractor = new ControllerActionExtractor($container);

		$controllerAction = $controllerActionExtractor->extractFromString(['serviceName', 'action']);
		$this->assertInstanceOf(ControllerAction::class, $controllerAction);
		$this->assertEquals($controllerAction->getControllerClass(), StubController::class);
		$this->assertEquals($controllerAction->getAction(), 'action');
	}

	public function testExtractFromStringNull() {

		$container = $this->getMockBuilder(ContainerInterface::class)
			->getMockForAbstractClass()
		;

		$controllerActionExtractor = new ControllerActionExtractor($container);

		$controllerAction = $controllerActionExtractor->extractFromString(null);
		$this->assertNull($controllerAction);
	}

	public function testExtractFromStringBad() {

		$container = $this->getMockBuilder(ContainerInterface::class)
			->getMockForAbstractClass()
		;

		$controllerActionExtractor = new ControllerActionExtractor($container);

		$controllerAction = $controllerActionExtractor->extractFromString(new \stdClass());
		$this->assertNull($controllerAction);
	}
}