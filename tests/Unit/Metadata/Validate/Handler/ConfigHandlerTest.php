<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Validate\Handler;

use GollumSF\RestBundle\Metadata\Validate\Handler\ConfigHandler;
use PHPUnit\Framework\TestCase;

class ConfigHandlerTest extends TestCase {

	public function testNothingDeclared() {
		$handler = new ConfigHandler();
		$this->assertNull($handler->getMetadata('CONTROLLER', 'ACTION'));
	}

	public function testAnotherActionDeclared() {
		$handler = new ConfigHandler([ 'CONTROLLER::OTHER' => [ 'validate' => [ 'groups' => [ 'Default' ] ] ] ]);
		$this->assertNull($handler->getMetadata('CONTROLLER', 'ACTION'));
	}

	public function testDeclaredMetadata() {
		$handler = new ConfigHandler([ 'CONTROLLER::ACTION' => [ 'validate' => [ 'groups' => [ 'book_post' ] ] ] ]);
		$this->assertEquals([ 'book_post' ], $handler->getMetadata('CONTROLLER', 'ACTION')->getGroups());
	}
}
