<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Sort\Handler;

use GollumSF\RestBundle\Attribute\ApiSortable;
use GollumSF\RestBundle\Metadata\Sort\Handler\AttributeHandler;
use GollumSF\RestBundle\Model\Direction;
use PHPUnit\Framework\TestCase;

class SortDummyPlain {
	private $title;
}

class SortDummyEntity {
	#[ApiSortable]
	private $title;

	#[ApiSortable(path: 'author.name')]
	private $author;

	private $secret;
}

#[ApiSortable('popularity', sorter: 'SORTER_ID')]
#[ApiSortable('createdAt', direction: Direction::DESC)]
class SortDummyClassLevel {
	#[ApiSortable]
	private $title;
}

#[ApiSortable]
class SortDummyClassLevelWithoutKey {
	private $title;
}

class SortDummyRenamed {
	#[ApiSortable(key: 'writer', path: 'author.name')]
	private $author;
}

class SortDummyController {

	public function noSortable() {}

	#[ApiSortable('title')]
	public function restricts() {}

	#[ApiSortable('author', path: 'author.country.name')]
	public function overridesPath() {}

	#[ApiSortable('title', direction: Direction::DESC)]
	public function overridesDirection() {}

	#[ApiSortable('extra', path: 'extra')]
	public function addsItsOwn() {}

	#[ApiSortable('title', sorter: 'SORTER_ID')]
	public function overridesWithASorter() {}
}

class AttributeHandlerTest extends TestCase {

	public function testAnEntityWithoutSortableIsNotWhitelisted() {
		$handler = new AttributeHandler();
		$this->assertNull($handler->getMetadata(SortDummyPlain::class));
	}

	public function testAnUnknownClassIsNotWhitelisted() {
		$handler = new AttributeHandler();
		$this->assertNull($handler->getMetadata('Not\\A\\Class'));
	}

	public function testAClassLevelSortableWithoutKeyIsIgnored() {
		$handler = new AttributeHandler();
		$this->assertNull($handler->getMetadata(SortDummyClassLevelWithoutKey::class));
	}

	public function testPropertyLevelSortables() {

		$metadata = (new AttributeHandler())->getMetadata(SortDummyEntity::class);

		$this->assertEquals([ 'title', 'author' ], $metadata->getKeys());
		$this->assertEquals('title', $metadata->get('title')->getPath());
		$this->assertEquals('author.name', $metadata->get('author')->getPath());
		$this->assertFalse($metadata->has('secret'));
	}

	public function testTheKeyCanBeRenamed() {

		$metadata = (new AttributeHandler())->getMetadata(SortDummyRenamed::class);

		$this->assertEquals([ 'writer' ], $metadata->getKeys());
		$this->assertEquals('author.name', $metadata->get('writer')->getPath());
	}

	public function testClassLevelSortables() {

		$metadata = (new AttributeHandler())->getMetadata(SortDummyClassLevel::class);

		$this->assertEquals([ 'popularity', 'createdAt', 'title' ], $metadata->getKeys());

		$popularity = $metadata->get('popularity');
		$this->assertEquals('SORTER_ID', $popularity->getSorter());
		$this->assertNull($popularity->getPath());

		$createdAt = $metadata->get('createdAt');
		$this->assertEquals('createdAt', $createdAt->getPath());
		$this->assertEquals(Direction::DESC, $createdAt->getDirection());
	}

	public function testAnActionWithoutSortableKeepsTheEntityCatalogue() {

		$metadata = (new AttributeHandler())->getMetadata(
			SortDummyEntity::class,
			SortDummyController::class,
			'noSortable'
		);

		$this->assertEquals([ 'title', 'author' ], $metadata->getKeys());
	}

	public function testAnActionNarrowsTheEntityCatalogue() {

		$metadata = (new AttributeHandler())->getMetadata(
			SortDummyEntity::class,
			SortDummyController::class,
			'restricts'
		);

		$this->assertEquals([ 'title' ], $metadata->getKeys());
		$this->assertEquals('title', $metadata->get('title')->getPath());
	}

	public function testAnActionOverridesThePath() {

		$metadata = (new AttributeHandler())->getMetadata(
			SortDummyEntity::class,
			SortDummyController::class,
			'overridesPath'
		);

		$this->assertEquals([ 'author' ], $metadata->getKeys());
		$this->assertEquals('author.country.name', $metadata->get('author')->getPath());
	}

	public function testAnActionOverridesTheDirectionButKeepsThePath() {

		$metadata = (new AttributeHandler())->getMetadata(
			SortDummyEntity::class,
			SortDummyController::class,
			'overridesDirection'
		);

		$this->assertEquals('title', $metadata->get('title')->getPath());
		$this->assertEquals(Direction::DESC, $metadata->get('title')->getDirection());
	}

	public function testAnActionOverridesWithASorter() {

		$metadata = (new AttributeHandler())->getMetadata(
			SortDummyEntity::class,
			SortDummyController::class,
			'overridesWithASorter'
		);

		$this->assertEquals('SORTER_ID', $metadata->get('title')->getSorter());
	}

	public function testAnActionKeyUnknownToTheEntityStandsOnItsOwn() {

		$metadata = (new AttributeHandler())->getMetadata(
			SortDummyEntity::class,
			SortDummyController::class,
			'addsItsOwn'
		);

		$this->assertEquals([ 'extra' ], $metadata->getKeys());
		$this->assertEquals('extra', $metadata->get('extra')->getPath());
	}

	public function testAnActionAloneIsEnough() {

		$metadata = (new AttributeHandler())->getMetadata(
			SortDummyPlain::class,
			SortDummyController::class,
			'restricts'
		);

		$this->assertEquals([ 'title' ], $metadata->getKeys());
	}

	public function testAnUnknownControllerIsIgnored() {

		$metadata = (new AttributeHandler())->getMetadata(SortDummyEntity::class, 'Not\\A\\Class', 'action');
		$this->assertEquals([ 'title', 'author' ], $metadata->getKeys());
	}

	public function testAnUnknownActionIsIgnored() {

		$metadata = (new AttributeHandler())->getMetadata(SortDummyEntity::class, SortDummyController::class, 'nope');
		$this->assertEquals([ 'title', 'author' ], $metadata->getKeys());
	}
}
