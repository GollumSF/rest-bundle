<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Unserialize\Handler;

use GollumSF\RestBundle\Metadata\Unserialize\Handler\ConfigHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConfigDummyEntity {}
class ConfigDummyInput {}

class ConfigDummyController {
	public function typed(ConfigDummyEntity $book) {}
	public function untyped($book) {}
	public function builtin(string $book) {}
	public function other(ConfigDummyEntity $another) {}
}

class ConfigHandlerTest extends TestCase {

	private function config(array $unserialize): array {
		return [ ConfigDummyController::class.'::typed' => [ 'unserialize' => $unserialize ] ];
	}

	private function unserialize(array $overrides = []): array {
		return array_merge([ 'name' => 'book', 'groups' => [ 'book_post' ], 'save' => true, 'type' => null ], $overrides);
	}

	public function testNothingDeclared() {
		$handler = new ConfigHandler();
		$this->assertNull($handler->getMetadata(ConfigDummyController::class, 'typed'));
	}

	public function testAnotherActionDeclared() {
		$handler = new ConfigHandler($this->config($this->unserialize()));
		$this->assertNull($handler->getMetadata(ConfigDummyController::class, 'untyped'));
	}

	public function testDeclaredMetadata() {

		$handler = new ConfigHandler($this->config($this->unserialize([ 'save' => false ])));

		$metadata = $handler->getMetadata(ConfigDummyController::class, 'typed');

		$this->assertEquals('book', $metadata->getName());
		$this->assertEquals([ 'book_post' ], $metadata->getGroups());
		$this->assertFalse($metadata->isSave());
	}

	public function testAnExplicitTypeWins() {

		$handler = new ConfigHandler($this->config($this->unserialize([ 'type' => ConfigDummyInput::class ])));

		$this->assertEquals(
			ConfigDummyInput::class,
			$handler->getMetadata(ConfigDummyController::class, 'typed')->getType()
		);
	}

	public static function provideResolvedType() {
		return [
			'from the targeted parameter' => [ 'typed', 'book', ConfigDummyEntity::class ],
			'untyped parameter'           => [ 'untyped', 'book', null ],
			'builtin typed parameter'     => [ 'builtin', 'book', null ],
			'no parameter with that name' => [ 'other', 'book', null ],
			'no name declared'            => [ 'typed', '', null ],
		];
	}

	#[DataProvider('provideResolvedType')]
	public function testTheTypeFallsBackOnTheParameter($action, $name, $expected) {

		$handler = new ConfigHandler([
			ConfigDummyController::class.'::'.$action => [ 'unserialize' => $this->unserialize([ 'name' => $name ]) ],
		]);

		$this->assertEquals($expected, $handler->getMetadata(ConfigDummyController::class, $action)->getType());
	}

	public function testAnUnknownControllerResolvesNoType() {

		$handler = new ConfigHandler([
			'Not\\A\\Class::action' => [ 'unserialize' => $this->unserialize() ],
		]);

		$this->assertNull($handler->getMetadata('Not\\A\\Class', 'action')->getType());
	}

	public function testAnUnknownActionResolvesNoType() {

		$handler = new ConfigHandler([
			ConfigDummyController::class.'::nope' => [ 'unserialize' => $this->unserialize() ],
		]);

		$this->assertNull($handler->getMetadata(ConfigDummyController::class, 'nope')->getType());
	}
}
