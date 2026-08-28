<?php
namespace Test\GollumSF\RestBundle\Unit\Model;

use GollumSF\RestBundle\Model\ApiOrder;
use GollumSF\RestBundle\Model\ApiOrderCollection;
use GollumSF\RestBundle\Model\Direction;
use PHPUnit\Framework\TestCase;

class ApiOrderCollectionTest extends TestCase {

	public function testEmpty() {
		$collection = new ApiOrderCollection();
		$this->assertTrue($collection->isEmpty());
		$this->assertCount(0, $collection);
		$this->assertEquals([], $collection->all());
		$this->assertEquals([], iterator_to_array($collection));
	}

	public function testConstructAndAdd() {
		$title = new ApiOrder('title', Direction::ASC, 'title');
		$id = new ApiOrder('id', Direction::DESC, 'id');

		$collection = new ApiOrderCollection([ $title ]);
		$this->assertSame($collection, $collection->add($id));

		$this->assertFalse($collection->isEmpty());
		$this->assertCount(2, $collection);
		$this->assertEquals([ $title, $id ], $collection->all());
		$this->assertEquals([ $title, $id ], iterator_to_array($collection));
	}

	public function testConstructReindexes() {
		$title = new ApiOrder('title', Direction::ASC, 'title');
		$collection = new ApiOrderCollection([ 5 => $title ]);
		$this->assertEquals([ 0 => $title ], $collection->all());
	}
}
