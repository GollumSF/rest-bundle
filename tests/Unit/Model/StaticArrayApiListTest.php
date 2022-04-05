<?php
namespace Test\GollumSF\RestBundle\Unit\Model;

use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use GollumSF\RestBundle\Model\Direction;
use GollumSF\RestBundle\Model\StaticArrayApiList;
use GollumSF\RestBundle\Repository\ApiFinderRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DumyClass {

	private $prop1;

	public function __construct($prop1) {
		$this->prop1 = $prop1;
	}

	public function getProp1() {
		return $this->prop1;
	}
}

class DumyClassHas {

	private $prop1;

	public function __construct($prop1) {
		$this->prop1 = $prop1;
	}

	public function hasProp1() {
		return $this->prop1;
	}
}

class DumyClassIs {

	private $prop1;

	public function __construct($prop1) {
		$this->prop1 = $prop1;
	}

	public function isProp1() {
		return $this->prop1;
	}
}

class StaticArrayApiListTest extends TestCase {
	
	use ReflectionPropertyTrait;
	
	public function testSetter() {
		$request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$closureProperties = function ($valueA, $valueB, $roder) { return 42; };
		$closureGlobal = function ($valueA, $valueB, $roder) { return 42; };

		$apiList = new StaticArrayApiList([], $request);

		$this->assertNotEquals($this->reflectionGetValue($apiList, 'maxLimitItem'), 42);
		$this->assertNotEquals($this->reflectionGetValue($apiList, 'defaultLimitItem'), 42);
		$this->assertNotEquals($this->reflectionGetValue($apiList, 'sortPropertiesCallback')(null, null, null, null, null), 42);
		
		$apiList->setMaxLimitItem(42);
		$apiList->setDefaultLimitItem(42);
		$apiList->setSortPropertiesCallback($closureProperties);

		$this->assertEquals($this->reflectionGetValue($apiList, 'maxLimitItem'), 42);
		$this->assertEquals($this->reflectionGetValue($apiList, 'defaultLimitItem'), 42);
		$this->assertEquals($this->reflectionGetValue($apiList, 'sortPropertiesCallback')(null, null, null, null, null), 42);

		$apiList2 = new StaticArrayApiList([], $request);
		$this->assertNotEquals($this->reflectionGetValue($apiList2, 'sortGlobalCallback')(null, null, null, null), 42);
		$apiList->setSortGlobalCallback($closureGlobal);
		$this->assertNotEquals($this->reflectionGetValue($apiList2, 'sortGlobalCallback')(null, null, null, null), 42);
	}
	
	public function providerGetData() {

		$AAA = new DumyClass('AAA');
		$BBB = new DumyClass('BBB');
		$CCC = new DumyClass('CCC');
		$DDD = new DumyClass('DDD');
		$EEE = new DumyClass('EEE');
		$NULL = new DumyClass(null);

		$AAAHas = new DumyClassHas('AAA');
		$BBBHas = new DumyClassHas('BBB');
		$CCCHas = new DumyClassHas('CCC');
		$DDDHas = new DumyClassHas('DDD');

		$AAAIs = new DumyClassIs('AAA');
		$BBBIs = new DumyClassIs('BBB');
		$CCCIs = new DumyClassIs('CCC');
		$DDDIs = new DumyClassIs('DDD');

		return [
			[
				[
					$AAA,
					$CCC,
					$DDD,
					$BBB,
				],
				20, 0, null, null,
				[
					$AAA,
					$CCC,
					$DDD,
					$BBB,
				]
			],

			[
				[
					$AAA,
					$CCC,
					$DDD,
					$BBB,
				],
				2, 1, null, null,
				[
					$DDD,
					$BBB,
				]
			],

			[
				[
					null,
					$NULL,
					null,
					$AAA,
					$CCC,
					$DDD,
					$NULL,
					$AAA,
					$BBB,
					$EEE,
					$NULL,
				],
				25, 0, 'prop1', Direction::ASC,
				[
					null,
					null,
					$NULL,
					$NULL,
					$NULL,
					$AAA,
					$AAA,
					$BBB,
					$CCC,
					$DDD,
					$EEE,
				]
			],

			[
				[
					$AAA,
					$CCC,
					$DDD,
					$NULL,
					$EEE,
				],
				2, 0, 'prop1', Direction::ASC,
				[
					$NULL,
					$AAA,
				]
			],

			[
				[
					$AAAHas,
					$CCCHas,
					$DDDHas,
					$BBBHas,
				],
				2, 1, 'prop1', Direction::ASC,
				[
					$CCCHas,
					$DDDHas,
				]
			],
			
			[
				[
					$AAAIs,
					$CCCIs,
					$DDDIs,
					$BBBIs,
				],
				2, 1, 'prop1', Direction::ASC,
				[
					$CCCIs,
					$DDDIs,
				]
			],

			[
				[
					$AAA,
					$CCC,
					$DDD,
					$BBB,
					$NULL,
					$EEE,
				],
				100, 0, 'prop1', 'BAD_DIRECTION',
				[
					$NULL,
					$AAA,
					$BBB,
					$CCC,
					$DDD,
					$EEE,
				]
			],

			[
				[
					$NULL,
					$AAA,
					$CCC,
					$DDD,
					$NULL,
					$AAA,
					$BBB,
					$EEE,
					$NULL,
				],
				99, 0, 'prop1', Direction::DESC,
				[
					$EEE,
					$DDD,
					$CCC,
					$BBB,
					$AAA,
					$AAA,
					$NULL,
					$NULL,
					$NULL,
				]
			],

			[
				[
					$AAA,
					$CCC,
					$DDD,
					$BBB,
					$NULL,
					$EEE,
				],
				999, 0, 'prop1', Direction::ASC,
				[
					$NULL,
					$AAA,
					$BBB,
					$CCC,
					$DDD,
					$EEE,
				]
			],

			[
				[
					null,
					null,
					null,
					'AAA',
					'CCC',
					'DDD',
					null,
					'AAA',
					'BBB',
					'EEE',
					null,
				],
				25, 0, null, Direction::ASC,
				[
					null,
					null,
					null,
					null,
					null,
					'AAA',
					'AAA',
					'BBB',
					'CCC',
					'DDD',
					'EEE',
				]
			],
		];
	}
	
	/**
	 * @dataProvider providerGetData
	 */
	public function testGetData($list, $limit, $page, $order, $direction, $result) {

		$request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$request
			->expects($this->exactly(4))
			->method('get')
			->withConsecutive(
				[ 'limit' ],
				[ 'page' ],
				[ 'order' ],
				[ 'direction' ]
			)
			->willReturnOnConsecutiveCalls(
				$limit,
				$page,
				$order,
				$direction
			)
		;

		$apiList = new StaticArrayApiList($list, $request);
		
		$this->assertEquals($apiList->getData(), $result);
	}

	public function providerGetDataClosure() {

		$AAA = new DumyClass('AAA');
		$BBB = new DumyClass('BBB');
		$CCC = new DumyClass('CCC');
		$DDD = new DumyClass('DDD');
		$EEE = new DumyClass('EEE');
		$NULL = new DumyClass(null);
		
		return [
			[
				[
					null,
					$NULL,
					null,
					$AAA,
					$CCC,
					$DDD,
					$NULL,
					$AAA,
					$BBB,
					$EEE,
					$NULL,
				],
				Direction::ASC,
				[
					null,
					null,
					$NULL,
					$NULL,
					$NULL,
					$EEE,
					$DDD,
					$CCC,
					$BBB,
					$AAA,
					$AAA,
				]
			],

			[
				[
					null,
					$NULL,
					null,
					$AAA,
					$CCC,
					$DDD,
					$NULL,
					$AAA,
					$BBB,
					$EEE,
					$NULL,
				],
				Direction::DESC,
				[
					$AAA,
					$AAA,
					$BBB,
					$CCC,
					$DDD,
					$EEE,
					$NULL,
					$NULL,
					$NULL,
					null,
					null,
				]
			]
		];
	}
	
	/**
	 * @dataProvider providerGetDataClosure
	 */
	public function testGetDataClosureProperties($list, $direction, $result) {

		$request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$request
			->expects($this->exactly(4))
			->method('get')
			->withConsecutive(
				[ 'limit' ],
				[ 'page' ],
				[ 'order' ],
				[ 'direction' ]
			)
			->willReturnOnConsecutiveCalls(
				100,
				0,
				'prop1',
				$direction
			)
		;
		
		$apiList = new StaticArrayApiList($list, $request);

		$called = false;
		$apiList->setSortPropertiesCallback(function ($valueA, $valueB, $objA, $objB, $order) use (&$called) {
			$called = true;
			$this->assertEquals($order, 'prop1');
			if ($valueA === null && $valueB) {
				return -1;
			}
			if ($valueB === null && $valueA) {
				return 1;
			}
			if ($valueA === null && $valueB === null ) {
				if ($objA === null && $objB) {
					return -1;
				}
				if ($objA && $objB === null) {
					return 1;
				}
				return 0;
			}

			if ($valueA === $valueB) {
				return 0;
			}
			return ($valueA < $valueB) ? 1 : -1;
		});
		
		$this->assertEquals($apiList->getData(), $result);
		$this->assertTrue($called);
	}


	public function testGetDataClosureGlobal() {

		$request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$request
			->expects($this->exactly(4))
			->method('get')
			->withConsecutive(
				[ 'limit' ],
				[ 'page' ],
				[ 'order' ],
				[ 'direction' ]
			)
			->willReturnOnConsecutiveCalls(
				100,
				0,
				'ORDER',
				Direction::DESC
			)
		;

		$apiList = new StaticArrayApiList([
			'AAA',
			'CCC',
			'AAA',
			'BBB',
			'DDD',
			'AAA',
		], $request);

		$called = false;
		$apiList->setSortGlobalCallback(function ($objA, $objB, $order, $direction) use (&$called) {
			$called = true;
			$this->assertEquals($order, 'ORDER');
			$this->assertEquals($direction, Direction::DESC);
			if ($objA === $objB) {
				return 0;
			}
			return ($objA < $objB) ? -1 : 1;
		});

		$this->assertEquals($apiList->getData(), [
			'AAA',
			'AAA',
			'AAA',
			'BBB',
			'CCC',
			'DDD',
		]);
		$this->assertTrue($called);
	}

	public function testGetDataException() {

		$request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$request
			->expects($this->exactly(4))
			->method('get')
			->withConsecutive(
				[ 'limit' ],
				[ 'page' ],
				[ 'order' ],
				[ 'direction' ]
			)
			->willReturnOnConsecutiveCalls(
				100,
				0,
				'prop1',
				Direction::ASC
			)
		;

		$apiList = new StaticArrayApiList([
			new DumyClass('BBB'),
			new DumyClass('AAA'),
		], $request);

		$apiList->setSortGlobalCallback(function ($objA, $objB, $order, $direction) use (&$called) {
			throw new \Exception();
		});
		
		$this->expectException(BadRequestHttpException::class);
		
		$apiList->getData();
	}
	
}
