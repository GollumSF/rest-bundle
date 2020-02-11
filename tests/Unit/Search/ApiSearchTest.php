<?php
namespace Test\GollumSF\RestBundle\Unit\Search;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use GollumSF\RestBundle\Model\ApiList;
use GollumSF\RestBundle\Model\Direction;
use GollumSF\RestBundle\Model\StaticArrayApiList;
use GollumSF\RestBundle\Repository\ApiFinderRepository;
use GollumSF\RestBundle\Repository\ApiFinderRepositoryInterface;
use GollumSF\RestBundle\Search\ApiSearch;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiSearchTestApiFind extends ApiSearch {

	public $request;
	public $repository;
	
	public function getMasterRequest(): Request {
		return $this->request;
	}
	protected function getEntityRepositoryForClass($entityOrClass): ?ObjectRepository {
		return $this->repository;
	}
}

class ApiSearchTest extends TestCase {
	
	use ReflectionPropertyTrait;

	public function testGetMasterRequest() {

		$requestStack    = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
		$logger          = $this->getMockForAbstractClass(LoggerInterface::class);
		$configuration   = $this->getMockForAbstractClass(ApiConfigurationInterface::class);
		$request         = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

		$requestStack
			->expects($this->once())
			->method('getMasterRequest')
			->willReturn($request)
		;

		$apiSearch = new ApiSearch($requestStack, $logger, $configuration);

		$this->assertEquals(
			$this->reflectionCallMethod($apiSearch, 'getMasterRequest'), $request
		);
	}

	public function providerApiFind() {
		return [
			[ 25 , 25, null, null ],
			[ 101 , 100, null, null ],
			[ 25 , 25, Direction::ASC, Direction::ASC ],
			[ 25 , 25, 'BAD_DIRECTIOn', null ],
		];
	}

	/**
	 * @dataProvider providerApiFind
	 */
	public function testApiFind($limit, $limitResult, $direction, $directionResult) {

		$requestStack    = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
		$logger          = $this->getMockForAbstractClass(LoggerInterface::class);
		$configuration   = $this->getMockForAbstractClass(ApiConfigurationInterface::class);
		$request         = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$repository      = $this->getMockBuilder(ApiFinderRepository::class)->disableOriginalConstructor()->getMock();
		$list            = $this->getMockBuilder(ApiList::class)->disableOriginalConstructor()->getMock();
		$closure         = function () {};

		$configuration
			->expects($this->at(0))
			->method('getDefaultLimitItem')
			->willReturn(25)
		;
		$configuration
			->expects($this->at(1))
			->method('getMaxLimitItem')
			->willReturn(100)
		;
		
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
			->willReturn(0)
		;
		$request
			->expects($this->at(2))
			->method('get')
			->with('order')
			->willReturn('prop1')
		;
		$request
			->expects($this->at(3))
			->method('get')
			->with('direction')
			->willReturn($direction)
		;

		$repository
			->expects($this->once())
			->method('apiFindBy')
			->with($limitResult, 0, 'prop1', $directionResult, $closure)
			->willReturn($list)
		;

		$apiSearch = new ApiSearchTestApiFind($requestStack, $logger, $configuration);
		$apiSearch->repository = $repository;
		$apiSearch->request = $request;

		$this->assertEquals(
			$apiSearch->apiFindBy(\stdClass::class, $closure), $list
		);
	}

	public function testApiFindQueryException() {

		$requestStack    = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
		$logger          = $this->getMockForAbstractClass(LoggerInterface::class);
		$configuration   = $this->getMockForAbstractClass(ApiConfigurationInterface::class);
		$logger          = $this->getMockForAbstractClass(LoggerInterface::class);
		$request         = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$repository      = $this->getMockBuilder(ApiFinderRepository::class)->disableOriginalConstructor()->getMock();
		$list            = $this->getMockBuilder(ApiList::class)->disableOriginalConstructor()->getMock();

		$configuration
			->expects($this->at(0))
			->method('getDefaultLimitItem')
			->willReturn(25)
		;
		$configuration
			->expects($this->at(1))
			->method('getMaxLimitItem')
			->willReturn(100)
		;

		$request
			->expects($this->at(0))
			->method('get')
			->with('limit')
			->willReturn(20)
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
			->willReturn('prop1')
		;
		$request
			->expects($this->at(3))
			->method('get')
			->with('direction')
			->willReturn(Direction::ASC)
		;

		$repository
			->expects($this->once())
			->method('apiFindBy')
			->willThrowException(new QueryException('MESSAGE'))
		;

		$logger
			->expects($this->once())
			->method('warning')
			->with('Error on execute ApiSearch: MESSAGE')
		;
		
		$apiSearch = new ApiSearchTestApiFind($requestStack, $logger, $configuration);
		$apiSearch->repository = $repository;
		$apiSearch->request = $request;
		
		$this->expectException(BadRequestHttpException::class);
		
		$apiSearch->apiFindBy(\stdClass::class);
	}

	public function testApiFindNoInstanceOfApiFindRepository() {

		$requestStack    = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
		$logger          = $this->getMockForAbstractClass(LoggerInterface::class);
		$configuration   = $this->getMockForAbstractClass(ApiConfigurationInterface::class);
		$request         = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$repository      = $this->getMockForAbstractClass(ObjectRepository::class);

		$configuration
			->expects($this->at(0))
			->method('getDefaultLimitItem')
			->willReturn(25)
		;
		$configuration
			->expects($this->at(1))
			->method('getMaxLimitItem')
			->willReturn(100)
		;
		
		$request
			->expects($this->at(0))
			->method('get')
			->with('limit')
			->willReturn(25)
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
			->willReturn('prop1')
		;
		$request
			->expects($this->at(3))
			->method('get')
			->with('direction')
			->willReturn(Direction::ASC)
		;

		$apiSearch = new ApiSearchTestApiFind($requestStack, $logger, $configuration);
		$apiSearch->repository = $repository;
		$apiSearch->request = $request;

		$this->expectException(\LogicException::class);
		
		$apiSearch->apiFindBy(\stdClass::class);
	}

	public function testApiFindNoInstanceNoRepository() {

		$requestStack    = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
		$logger          = $this->getMockForAbstractClass(LoggerInterface::class);
		$configuration   = $this->getMockForAbstractClass(ApiConfigurationInterface::class);
		$request         = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

		$configuration
			->expects($this->at(0))
			->method('getDefaultLimitItem')
			->willReturn(25)
		;
		$configuration
			->expects($this->at(1))
			->method('getMaxLimitItem')
			->willReturn(100)
		;

		$request
			->expects($this->at(0))
			->method('get')
			->with('limit')
			->willReturn(25)
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
			->willReturn('prop1')
		;
		$request
			->expects($this->at(3))
			->method('get')
			->with('direction')
			->willReturn(Direction::ASC)
		;

		$apiSearch = new ApiSearchTestApiFind($requestStack, $logger, $configuration);
		$apiSearch->request = $request;

		$this->expectException(\LogicException::class);

		$apiSearch->apiFindBy(\stdClass::class);
	}

	public function testStaticArrayList() {
		$requestStack    = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
		$logger          = $this->getMockForAbstractClass(LoggerInterface::class);
		$configuration   = $this->getMockForAbstractClass(ApiConfigurationInterface::class);
		$request         = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

		$configuration
			->expects($this->at(0))
			->method('getMaxLimitItem')
			->willReturn(4242)
		;
		$configuration
			->expects($this->at(1))
			->method('getDefaultLimitItem')
			->willReturn(42)
		;
		$apiSearch = new ApiSearchTestApiFind($requestStack, $logger, $configuration);
		$apiSearch->request = $request;

		$arrayList = $apiSearch->staticArrayList([
			'DATA1',
			'DATA3',
			'DATA2'
		]);
		
		$this->assertEquals($this->reflectionGetValue($arrayList, 'data', ApiList::class), [
			'DATA1',
			'DATA3',
			'DATA2'
		]);
		$this->assertEquals($this->reflectionGetValue($arrayList, 'total', ApiList::class), 3);
		$this->assertEquals($this->reflectionGetValue($arrayList, 'maxLimitItem'), 4242);
		$this->assertEquals($this->reflectionGetValue($arrayList, 'defaultLimitItem'), 42);

		$closureProperties = function ($valueA, $valueB, $objA, $objB, $order) {
			return 212121;
		};
		$closureGlobal = function ($objA, $objB, $order, $direction) {
			return 424242;
		};

		$arrayList2 = $apiSearch->staticArrayList([ 'DATA1' ], $closureProperties);
		$arrayList3 = $apiSearch->staticArrayList([ 'DATA1' ], $closureGlobal, true);

		$this->assertEquals($this->reflectionGetValue($arrayList2, 'sortPropertiesCallback')(null, null, null, null, null), 212121);
		$this->assertEquals($this->reflectionGetValue($arrayList3, 'sortGlobalCallback')(null, null, null, null), 424242);
	}
}