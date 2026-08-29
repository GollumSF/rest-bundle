<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Serialize\Handler;

use GollumSF\RestBundle\Metadata\Serialize\Handler\ConfigHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ConfigHandlerTest extends TestCase {

	public function testNothingDeclared() {
		$handler = new ConfigHandler();
		$this->assertNull($handler->getMetadata('CONTROLLER', 'ACTION'));
	}

	public function testAnotherActionDeclared() {
		$handler = new ConfigHandler([ 'CONTROLLER::OTHER' => [ 'serialize' => [ 'code' => 200, 'groups' => [], 'headers' => [] ] ] ]);
		$this->assertNull($handler->getMetadata('CONTROLLER', 'ACTION'));
	}

	public function testDeclaredMetadata() {

		$handler = new ConfigHandler([
			'CONTROLLER::ACTION' => [
				'serialize' => [
					'code' => Response::HTTP_CREATED,
					'groups' => [ 'book_get' ],
					'headers' => [ 'X-Custom' => 'VALUE' ],
				],
			],
		]);

		$metadata = $handler->getMetadata('CONTROLLER', 'ACTION');

		$this->assertEquals(Response::HTTP_CREATED, $metadata->getCode());
		$this->assertEquals([ 'book_get' ], $metadata->getGroups());
		$this->assertEquals([ 'X-Custom' => 'VALUE' ], $metadata->getHeaders());
	}
}
