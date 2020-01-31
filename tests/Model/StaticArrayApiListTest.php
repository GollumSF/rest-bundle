<?php
namespace Test\GollumSF\RestBundle\Model;

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
	
	public function providerGetData() {

		$AAA = new DumyClass('AAA');
		$BBB = new DumyClass('BBB');
		$CCC = new DumyClass('CCC');
		$DDD = new DumyClass('DDD');
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
				20, 0, null, ApiFinderRepositoryInterface::DIRECTION_ASC,
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
				2, 1, null, ApiFinderRepositoryInterface::DIRECTION_ASC,
				[
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
					$NULL,
				],
				2, 1, 'prop1', ApiFinderRepositoryInterface::DIRECTION_ASC,
				[
					$CCC,
					$DDD,
				]
			],

			[
				[
					$AAAHas,
					$CCCHas,
					$DDDHas,
					$BBBHas,
				],
				2, 1, 'prop1', ApiFinderRepositoryInterface::DIRECTION_ASC,
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
				2, 1, 'prop1', ApiFinderRepositoryInterface::DIRECTION_ASC,
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
				],
				2, 1, 'prop1', 'BAD_DIRECTION',
				[
					$CCC,
					$DDD,
				]
			],

			[
				[
					$AAA,
					$CCC,
					$DDD,
					$BBB,
					$NULL,
				],
				2, 1, 'prop1', ApiFinderRepositoryInterface::DIRECTION_DESC,
				[
					$BBB,
					$AAA,
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
			->expects($this->at(0))
			->method('get')
			->with('limit')
			->willReturn($limit)
		;
		$request
			->expects($this->at(1))
			->method('get')
			->with('page')
			->willReturn($page)
		;
		$request
			->expects($this->at(2))
			->method('get')
			->with('order')
			->willReturn($order)
		;
		$request
			->expects($this->at(3))
			->method('get')
			->with('direction')
			->willReturn($direction)
		;

		$apiList = new StaticArrayApiList($list, $request);

		$this->assertEquals($apiList->getData(), $result);
	}

	public function testGetDataException() {

		$request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$request
			->expects($this->at(0))
			->method('get')
			->with('limit')
			->willReturn([
			])
		;
		$request
			->expects($this->at(1))
			->method('get')
			->with('page')
			->willReturn(0)
		;
		$request
			->expects($this->at(2))
			->method('get')
			->with('order')
			->willReturn('bad_param')
		;
		$request
			->expects($this->at(3))
			->method('get')
			->with('direction')
			->willReturn(ApiFinderRepositoryInterface::DIRECTION_ASC)
		;

		$apiList = new StaticArrayApiList([
			new DumyClass('BBB'),
			new DumyClass('AAA'),
		], $request);
		
		$this->expectException(BadRequestHttpException::class);
		
		$apiList->getData();
	}
	
}