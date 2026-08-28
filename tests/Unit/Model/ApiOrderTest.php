<?php
namespace Test\GollumSF\RestBundle\Unit\Model;

use GollumSF\RestBundle\Model\ApiOrder;
use GollumSF\RestBundle\Model\Direction;
use GollumSF\RestBundle\Sort\ApiSorterInterface;
use PHPUnit\Framework\TestCase;

class ApiOrderTest extends TestCase {

	public function testConstruct() {
		$order = new ApiOrder('author', Direction::DESC, 'author.name');
		$this->assertEquals('author', $order->getKey());
		$this->assertEquals(Direction::DESC, $order->getDirection());
		$this->assertEquals('author.name', $order->getPath());
		$this->assertNull($order->getSorter());
	}

	public function testConstructWithSorter() {
		$sorter = $this->createMock(ApiSorterInterface::class);
		$order = new ApiOrder('popularity', Direction::ASC, null, $sorter);
		$this->assertEquals('popularity', $order->getKey());
		$this->assertEquals(Direction::ASC, $order->getDirection());
		$this->assertNull($order->getPath());
		$this->assertSame($sorter, $order->getSorter());
	}
}
