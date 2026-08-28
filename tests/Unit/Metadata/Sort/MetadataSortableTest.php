<?php
namespace Test\GollumSF\RestBundle\Unit\Metadata\Sort;

use GollumSF\RestBundle\Metadata\Sort\MetadataSortable;
use GollumSF\RestBundle\Model\Direction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MetadataSortableTest extends TestCase {

	public static function provideConstruct() {
		return [
			[ 'title', 'title', null, null ],
			[ 'author', 'author.name', null, null ],
			[ 'popularity', null, 'SORTER_CLASS', null ],
			[ 'createdAt', 'createdAt', null, Direction::DESC ],
		];
	}

	#[DataProvider('provideConstruct')]
	public function testConstruct($key, $path, $sorter, $direction) {
		$metadata = new MetadataSortable($key, $path, $sorter, $direction);
		$this->assertEquals($key, $metadata->getKey());
		$this->assertEquals($path, $metadata->getPath());
		$this->assertEquals($sorter, $metadata->getSorter());
		$this->assertEquals($direction, $metadata->getDirection());
	}

	public function testConstructDefaults() {
		$metadata = new MetadataSortable('title');
		$this->assertEquals('title', $metadata->getKey());
		$this->assertNull($metadata->getPath());
		$this->assertNull($metadata->getSorter());
		$this->assertNull($metadata->getDirection());
	}
}
