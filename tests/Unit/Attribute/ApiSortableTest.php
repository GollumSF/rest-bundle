<?php

namespace Test\GollumSF\RestBundle\Unit\Attribute;

use GollumSF\RestBundle\Attribute\ApiSortable;
use GollumSF\RestBundle\Model\Direction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ApiSortableTest extends TestCase
{
	public static function provideConstruct() {
		return [
			[ null, null, null, null ],
			[ 'title', null, null, null ],
			[ 'author', 'author.name', null, null ],
			[ 'popularity', null, 'SORTER_CLASS', null ],
			[ 'createdAt', 'createdAt', null, Direction::DESC ],
		];
	}

	#[DataProvider('provideConstruct')]
	public function testConstruct($key, $path, $sorter, $direction) {
		$annotation = new ApiSortable($key, $path, $sorter, $direction);
		$this->assertEquals($key, $annotation->getKey());
		$this->assertEquals($path, $annotation->getPath());
		$this->assertEquals($sorter, $annotation->getSorter());
		$this->assertEquals($direction, $annotation->getDirection());
	}

	public function testConstructDefaults() {
		$annotation = new ApiSortable();
		$this->assertNull($annotation->getKey());
		$this->assertNull($annotation->getPath());
		$this->assertNull($annotation->getSorter());
		$this->assertNull($annotation->getDirection());
	}
}
