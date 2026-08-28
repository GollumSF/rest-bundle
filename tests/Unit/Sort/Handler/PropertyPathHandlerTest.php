<?php
namespace Test\GollumSF\RestBundle\Unit\Sort\Handler;

use GollumSF\RestBundle\Model\Direction;
use GollumSF\RestBundle\Sort\ApiSortContext;
use GollumSF\RestBundle\Sort\Handler\PropertyPathHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PropertyPathHandlerTest extends TestCase {

	public static function provideGetOrder() {
		return [
			'plain property'          => [ 'title', Direction::ASC, 'title', Direction::ASC ],
			'no direction asked'      => [ 'title', null, 'title', Direction::ASC ],
			'dots cross relations'    => [ 'author.name', Direction::DESC, 'author.name', Direction::DESC ],
			'illegal chars stripped'  => [ 'prop\\/!', null, 'prop', Direction::ASC ],
			'empty segments dropped'  => [ 'author..name.', null, 'author.name', Direction::ASC ],
			'dashes and underscores'  => [ 'prop_09-', null, 'prop_09-', Direction::ASC ],
		];
	}

	#[DataProvider('provideGetOrder')]
	public function testGetOrder($key, $direction, $expectedPath, $expectedDirection) {

		$handler = new PropertyPathHandler();

		$order = $handler->getOrder(new ApiSortContext('ENTITY'), $key, $direction);

		$this->assertEquals($key, $order->getKey());
		$this->assertEquals($expectedPath, $order->getPath());
		$this->assertEquals($expectedDirection, $order->getDirection());
		$this->assertNull($order->getSorter());
	}

	public static function provideGetOrderNull() {
		return [
			'nothing left after sanitizing' => [ '!!!' ],
			'only dots'                     => [ '...' ],
		];
	}

	#[DataProvider('provideGetOrderNull')]
	public function testGetOrderNull($key) {
		$handler = new PropertyPathHandler();
		$this->assertNull($handler->getOrder(new ApiSortContext('ENTITY'), $key, null));
	}
}
