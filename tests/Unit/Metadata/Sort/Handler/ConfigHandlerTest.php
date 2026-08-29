<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Sort\Handler;

use GollumSF\RestBundle\Metadata\Sort\Handler\ConfigHandler;
use GollumSF\RestBundle\Model\Direction;
use PHPUnit\Framework\TestCase;

class SortConfigHandlerTest extends TestCase {

	private function entities(): array {
		return [
			'ENTITY' => [
				'sortable' => [
					'title'      => [ 'path' => null, 'sorter' => null, 'direction' => null ],
					'author'     => [ 'path' => 'author.name', 'sorter' => null, 'direction' => null ],
					'popularity' => [ 'path' => null, 'sorter' => 'SORTER_ID', 'direction' => Direction::DESC->value ],
				],
			],
		];
	}

	public function testNothingDeclared() {
		$this->assertNull((new ConfigHandler())->getMetadata('ENTITY'));
	}

	public function testEntitySortables() {

		$metadata = (new ConfigHandler($this->entities()))->getMetadata('ENTITY');

		$this->assertEquals([ 'title', 'author', 'popularity' ], $metadata->getKeys());
		$this->assertEquals('title', $metadata->get('title')->getPath());
		$this->assertEquals('author.name', $metadata->get('author')->getPath());

		$popularity = $metadata->get('popularity');
		$this->assertNull($popularity->getPath());
		$this->assertEquals('SORTER_ID', $popularity->getSorter());
		$this->assertEquals(Direction::DESC, $popularity->getDirection());
	}

	public function testAnotherEntityDeclared() {
		$this->assertNull((new ConfigHandler($this->entities()))->getMetadata('OTHER'));
	}

	public function testAnActionNarrowsTheCatalogue() {

		$handler = new ConfigHandler($this->entities(), [
			'CONTROLLER::ACTION' => [ 'sortable' => [ 'title' => [ 'path' => null, 'sorter' => null, 'direction' => null ] ] ],
		]);

		$metadata = $handler->getMetadata('ENTITY', 'CONTROLLER', 'ACTION');

		$this->assertEquals([ 'title' ], $metadata->getKeys());
		$this->assertEquals('title', $metadata->get('title')->getPath());
	}

	public function testAnActionOverridesThePath() {

		$handler = new ConfigHandler($this->entities(), [
			'CONTROLLER::ACTION' => [ 'sortable' => [ 'author' => [ 'path' => 'author.country.name', 'sorter' => null, 'direction' => null ] ] ],
		]);

		$this->assertEquals(
			'author.country.name',
			$handler->getMetadata('ENTITY', 'CONTROLLER', 'ACTION')->get('author')->getPath()
		);
	}

	public function testAnActionWithoutSortableKeepsTheCatalogue() {

		$handler = new ConfigHandler($this->entities(), [
			'CONTROLLER::ACTION' => [ 'serialize' => [] ],
		]);

		$this->assertEquals(
			[ 'title', 'author', 'popularity' ],
			$handler->getMetadata('ENTITY', 'CONTROLLER', 'ACTION')->getKeys()
		);
	}

	public function testTheActionIsIgnoredWithoutAController() {

		$handler = new ConfigHandler($this->entities(), [
			'CONTROLLER::ACTION' => [ 'sortable' => [ 'title' => [ 'path' => null, 'sorter' => null, 'direction' => null ] ] ],
		]);

		$this->assertEquals([ 'title', 'author', 'popularity' ], $handler->getMetadata('ENTITY', null, 'ACTION')->getKeys());
		$this->assertEquals([ 'title', 'author', 'popularity' ], $handler->getMetadata('ENTITY', 'CONTROLLER', null)->getKeys());
	}
}
