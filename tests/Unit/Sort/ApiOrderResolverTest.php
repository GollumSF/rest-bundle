<?php
namespace Test\GollumSF\RestBundle\Unit\Sort;

use GollumSF\RestBundle\Model\ApiOrder;
use GollumSF\RestBundle\Model\Direction;
use GollumSF\RestBundle\Sort\ApiOrderResolver;
use GollumSF\RestBundle\Sort\ApiSortContext;
use GollumSF\RestBundle\Sort\Handler\HandlerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ApiOrderResolverTest extends TestCase {

	/**
	 * Records what the resolver asked for, and answers with a matching ApiOrder.
	 */
	private function createRecordingHandler(array &$asked): HandlerInterface {
		$handler = $this->createMock(HandlerInterface::class);
		$handler
			->method('getOrder')
			->willReturnCallback(function (ApiSortContext $context, string $key, ?Direction $direction) use (&$asked) {
				$asked[] = [ $key, $direction ];
				return new ApiOrder($key, $direction ?? Direction::ASC, $key);
			})
		;
		return $handler;
	}

	public static function provideResolve() {
		return [
			'single key'                      => [ 'title', null, [ [ 'title', null ] ] ],
			'explicit direction'              => [ 'title:desc', null, [ [ 'title', Direction::DESC ] ] ],
			'uppercase direction'             => [ 'title:DESC', null, [ [ 'title', Direction::DESC ] ] ],
			'deprecated direction parameter'  => [ 'title', 'desc', [ [ 'title', Direction::DESC ] ] ],
			'explicit wins over deprecated'   => [ 'title:asc', 'desc', [ [ 'title', Direction::ASC ] ] ],
			'multi keys'                      => [ 'author:asc,id:desc', null, [ [ 'author', Direction::ASC ], [ 'id', Direction::DESC ] ] ],
			'deprecated applies to all keys'  => [ 'author,id', 'desc', [ [ 'author', Direction::DESC ], [ 'id', Direction::DESC ] ] ],
			'dotted path'                     => [ 'author.name:desc', null, [ [ 'author.name', Direction::DESC ] ] ],
			'spaces are trimmed'              => [ ' author , id : desc ', null, [ [ 'author', null ], [ 'id', Direction::DESC ] ] ],
			'empty items are skipped'         => [ 'author,,id', null, [ [ 'author', null ], [ 'id', null ] ] ],
			'invalid direction is ignored'    => [ 'title:sideways', null, [ [ 'title', null ] ] ],
			'invalid deprecated is ignored'   => [ 'title', 'sideways', [ [ 'title', null ] ] ],
		];
	}

	#[DataProvider('provideResolve')]
	public function testResolve($order, $direction, $expected) {

		$asked = [];
		$resolver = new ApiOrderResolver();
		$resolver->addHandler($this->createRecordingHandler($asked));

		$orders = $resolver->resolve(new ApiSortContext('ENTITY'), $order, $direction);

		$this->assertEquals($expected, $asked);
		$this->assertCount(count($expected), $orders);
	}

	public static function provideResolveEmpty() {
		return [
			'null order'  => [ null ],
			'empty order' => [ '' ],
			'only commas' => [ ',,' ],
			'only spaces' => [ '  ' ],
		];
	}

	#[DataProvider('provideResolveEmpty')]
	public function testResolveEmpty($order) {
		$asked = [];
		$resolver = new ApiOrderResolver();
		$resolver->addHandler($this->createRecordingHandler($asked));

		$orders = $resolver->resolve(new ApiSortContext('ENTITY'), $order);

		$this->assertTrue($orders->isEmpty());
		$this->assertEquals([], $asked);
	}

	public function testResolveWithoutHandlerDropsTheKey() {
		$resolver = new ApiOrderResolver();
		$this->assertTrue($resolver->resolve(new ApiSortContext('ENTITY'), 'title')->isEmpty());
	}

	public function testTheFirstHandlerReturningAnOrderWins() {

		$order = new ApiOrder('title', Direction::ASC, 'title');

		$declining = $this->createMock(HandlerInterface::class);
		$declining->expects($this->once())->method('getOrder')->willReturn(null);

		$accepting = $this->createMock(HandlerInterface::class);
		$accepting->expects($this->once())->method('getOrder')->willReturn($order);

		$never = $this->createMock(HandlerInterface::class);
		$never->expects($this->never())->method('getOrder');

		$resolver = new ApiOrderResolver();
		$resolver->addHandler($declining);
		$resolver->addHandler($accepting);
		$resolver->addHandler($never);

		$orders = $resolver->resolve(new ApiSortContext('ENTITY'), 'title');

		$this->assertEquals([ $order ], $orders->all());
	}

	public function testTheContextIsForwardedToTheHandlers() {

		$context = new ApiSortContext('ENTITY', 'CONTROLLER', 'ACTION');

		$handler = $this->createMock(HandlerInterface::class);
		$handler
			->expects($this->once())
			->method('getOrder')
			->with($context, 'title', null)
			->willReturn(null)
		;

		$resolver = new ApiOrderResolver();
		$resolver->addHandler($handler);
		$resolver->resolve($context, 'title');
	}
}
