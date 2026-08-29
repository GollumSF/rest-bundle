<?php
namespace Test\GollumSF\RestBundle\Unit\Search;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use GollumSF\RestBundle\Model\ApiList;
use GollumSF\RestBundle\Model\ApiOrderCollection;
use GollumSF\RestBundle\Model\Direction;
use GollumSF\RestBundle\Model\StaticArrayApiList;
use GollumSF\RestBundle\Repository\ApiFinderRepository;
use GollumSF\RestBundle\Repository\ApiFinderRepositoryInterface;
use GollumSF\RestBundle\Search\ApiSearch;
use GollumSF\ControllerActionExtractorBundle\Extractor\ControllerAction;
use GollumSF\ControllerActionExtractorBundle\Extractor\ControllerActionExtractorInterface;
use GollumSF\RestBundle\Sort\ApiOrderResolver;
use GollumSF\RestBundle\Sort\ApiSortContext;
use GollumSF\RestBundle\Sort\Handler\HandlerInterface;
use GollumSF\RestBundle\Sort\Handler\PropertyPathHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Test\GollumSF\RestBundle\Helper\WithConsecutiveTrait;
use PHPUnit\Framework\Attributes\DataProvider;

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

abstract class ApiSearchTestLegacyRepository implements ApiFinderRepositoryInterface, ObjectRepository {
}

class ApiSearchTest extends TestCase {

	use ReflectionPropertyTrait;
	use WithConsecutiveTrait;

	public function testGetMasterRequest() {

		$requestStack    = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
		$logger          = $this->createMock(LoggerInterface::class);
		$configuration   = $this->createMock(ApiConfigurationInterface::class);
		$request         = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

		$requestStack
			->expects($this->once())
			->method('getMainRequest')
			->willReturn($request)
		;

		$apiSearch = new ApiSearch($requestStack, $logger, $configuration);

		$this->assertEquals(
			$this->reflectionCallMethod($apiSearch, 'getMasterRequest'), $request
		);
	}

	public static function providerApiFind() {
		return [
			[ 25 , 25, '', Direction::ASC ],
			[ 101 , 100, '', Direction::ASC ],
			[ 25 , 25, Direction::ASC->value, Direction::ASC ],
			[ 25 , 25, Direction::DESC->value, Direction::DESC ],
			[ 25 , 25, 'BAD_DIRECTIOn', Direction::ASC ],
		];
	}

	private function createApiOrderResolver(): ApiOrderResolver {
		$resolver = new ApiOrderResolver();
		$resolver->addHandler(new PropertyPathHandler());
		return $resolver;
	}

	#[DataProvider('providerApiFind')]
	public function testApiFind($limit, $limitResult, $direction, $directionResult) {

		$requestStack    = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
		$logger          = $this->createMock(LoggerInterface::class);
		$configuration   = $this->createMock(ApiConfigurationInterface::class);
		$repository      = $this->getMockBuilder(ApiFinderRepository::class)->disableOriginalConstructor()->getMock();
		$list            = $this->getMockBuilder(ApiList::class)->disableOriginalConstructor()->getMock();
		$closure         = function () {};

		$configuration
			->expects($this->once())
			->method('getDefaultLimitItem')
			->willReturn(25)
		;
		$configuration
			->expects($this->once())
			->method('getMaxLimitItem')
			->willReturn(100)
		;

		$query = ['limit' => $limit, 'page' => 0, 'order' => 'prop1'];
		if ($direction !== null && $direction !== '') { $query['direction'] = $direction; }
		$request = new Request($query);

		$repository
			->expects($this->once())
			->method('apiFindByOrder')
			->with(
				$limitResult,
				0,
				$this->callback(function (ApiOrderCollection $orders) use ($directionResult) {
					$this->assertCount(1, $orders);
					$order = $orders->all()[0];
					$this->assertEquals('prop1', $order->getKey());
					$this->assertEquals('prop1', $order->getPath());
					$this->assertEquals($directionResult, $order->getDirection());
					$this->assertNull($order->getSorter());
					return true;
				}),
				$closure
			)
			->willReturn($list)
		;

		if ($direction !== '') {
			$this->expectUserDeprecationMessage('Since gollumsf/rest-bundle 4.1: The "direction" query parameter is deprecated, use "order=field:direction" instead.');
		}

		$apiSearch = new ApiSearchTestApiFind($requestStack, $logger, $configuration);
		$apiSearch->setApiOrderResolver($this->createApiOrderResolver());
		$apiSearch->repository = $repository;
		$apiSearch->request = $request;

		$this->assertEquals(
			$apiSearch->apiFindBy(\stdClass::class, $closure), $list
		);
	}


	public static function providerApiFindLegacyRepository() {
		return [
			'one key kept'      => [ 'prop1:desc', 'prop1', 'DESC' ],
			'first key kept'    => [ 'prop1:desc,prop2:asc', 'prop1', 'DESC' ],
			'no order at all'   => [ null, null, null ],
		];
	}

	/**
	 * A repository implementing apiFindBy() by hand never gets the collection.
	 */
	#[DataProvider('providerApiFindLegacyRepository')]
	public function testApiFindWithALegacyRepository($order, $expectedOrder, $expectedDirection) {

		$requestStack  = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
		$logger        = $this->createMock(LoggerInterface::class);
		$configuration = $this->createMock(ApiConfigurationInterface::class);
		$repository    = $this->getMockBuilder(ApiSearchTestLegacyRepository::class)->getMock();
		$list          = $this->getMockBuilder(ApiList::class)->disableOriginalConstructor()->getMock();
		$closure       = function () {};

		$configuration->method('getDefaultLimitItem')->willReturn(25);
		$configuration->method('getMaxLimitItem')->willReturn(100);

		$query = [ 'limit' => 25, 'page' => 0 ];
		if ($order !== null) {
			$query['order'] = $order;
		}

		$repository
			->expects($this->once())
			->method('apiFindBy')
			->with(25, 0, $expectedOrder, $expectedDirection, $closure)
			->willReturn($list)
		;

		$apiSearch = new ApiSearchTestApiFind($requestStack, $logger, $configuration);
		$apiSearch->setApiOrderResolver($this->createApiOrderResolver());
		$apiSearch->repository = $repository;
		$apiSearch->request = new Request($query);

		$this->assertEquals($list, $apiSearch->apiFindBy(\stdClass::class, $closure));
	}

	public function testTheControllerActionFeedsTheSortContext() {

		$requestStack  = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
		$logger        = $this->createMock(LoggerInterface::class);
		$configuration = $this->createMock(ApiConfigurationInterface::class);
		$repository    = $this->getMockBuilder(ApiFinderRepository::class)->disableOriginalConstructor()->getMock();
		$list          = $this->getMockBuilder(ApiList::class)->disableOriginalConstructor()->getMock();
		$request       = new Request([ 'limit' => 25, 'page' => 0, 'order' => 'prop1' ]);

		$configuration->method('getDefaultLimitItem')->willReturn(25);
		$configuration->method('getMaxLimitItem')->willReturn(100);
		$repository->method('apiFindByOrder')->willReturn($list);

		$extractor = $this->createMock(ControllerActionExtractorInterface::class);
		$extractor
			->expects($this->once())
			->method('extractFromRequest')
			->with($request)
			->willReturn(new ControllerAction('CONTROLLER', 'ACTION'))
		;

		$context = null;
		$handler = $this->createMock(HandlerInterface::class);
		$handler
			->method('getOrder')
			->willReturnCallback(function (ApiSortContext $sortContext) use (&$context) {
				$context = $sortContext;
				return null;
			})
		;
		$resolver = new ApiOrderResolver();
		$resolver->addHandler($handler);

		$apiSearch = new ApiSearchTestApiFind($requestStack, $logger, $configuration);
		$apiSearch->setApiOrderResolver($resolver);
		$apiSearch->setControllerActionExtractor($extractor);
		$apiSearch->repository = $repository;
		$apiSearch->request = $request;

		$apiSearch->apiFindBy(\stdClass::class);

		$this->assertEquals(\stdClass::class, $context->getEntityClass());
		$this->assertEquals('CONTROLLER', $context->getController());
		$this->assertEquals('ACTION', $context->getAction());
	}

	public function testTheSortContextHasNoActionWithoutAControllerAction() {

		$requestStack  = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
		$logger        = $this->createMock(LoggerInterface::class);
		$configuration = $this->createMock(ApiConfigurationInterface::class);
		$repository    = $this->getMockBuilder(ApiFinderRepository::class)->disableOriginalConstructor()->getMock();
		$list          = $this->getMockBuilder(ApiList::class)->disableOriginalConstructor()->getMock();

		$configuration->method('getDefaultLimitItem')->willReturn(25);
		$configuration->method('getMaxLimitItem')->willReturn(100);
		$repository->method('apiFindByOrder')->willReturn($list);

		$extractor = $this->createMock(ControllerActionExtractorInterface::class);
		$extractor->method('extractFromRequest')->willReturn(null);

		$context = null;
		$handler = $this->createMock(HandlerInterface::class);
		$handler
			->method('getOrder')
			->willReturnCallback(function (ApiSortContext $sortContext) use (&$context) {
				$context = $sortContext;
				return null;
			})
		;
		$resolver = new ApiOrderResolver();
		$resolver->addHandler($handler);

		$apiSearch = new ApiSearchTestApiFind($requestStack, $logger, $configuration);
		$apiSearch->setApiOrderResolver($resolver);
		$apiSearch->setControllerActionExtractor($extractor);
		$apiSearch->repository = $repository;
		$apiSearch->request = new Request([ 'limit' => 25, 'page' => 0, 'order' => 'prop1' ]);

		$apiSearch->apiFindBy(\stdClass::class);

		$this->assertNull($context->getController());
		$this->assertNull($context->getAction());
	}

	public function testApiFindQueryException() {

		$requestStack    = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
		$configuration   = $this->createMock(ApiConfigurationInterface::class);
		$logger          = $this->createMock(LoggerInterface::class);
		$repository      = $this->getMockBuilder(ApiFinderRepository::class)->disableOriginalConstructor()->getMock();

		$configuration
			->expects($this->once())
			->method('getDefaultLimitItem')
			->willReturn(25)
		;
		$configuration
			->expects($this->once())
			->method('getMaxLimitItem')
			->willReturn(100)
		;

		$request = new Request(['limit' => 20, 'page' => 0, 'order' => 'prop1', 'direction' => Direction::ASC->value]);

		$repository
			->expects($this->once())
			->method('apiFindByOrder')
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
		$logger          = $this->createMock(LoggerInterface::class);
		$configuration   = $this->createMock(ApiConfigurationInterface::class);
		$repository      = $this->createMock(ObjectRepository::class);

		$configuration
			->expects($this->once())
			->method('getDefaultLimitItem')
			->willReturn(25)
		;
		$configuration
			->expects($this->once())
			->method('getMaxLimitItem')
			->willReturn(100)
		;

		$request = new Request(['limit' => 25, 'page' => 0, 'order' => 'prop1', 'direction' => Direction::ASC->value]);

		$apiSearch = new ApiSearchTestApiFind($requestStack, $logger, $configuration);
		$apiSearch->repository = $repository;
		$apiSearch->request = $request;

		$this->expectException(\LogicException::class);

		$apiSearch->apiFindBy(\stdClass::class);
	}

	public function testApiFindNoInstanceNoRepository() {

		$requestStack    = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
		$logger          = $this->createMock(LoggerInterface::class);
		$configuration   = $this->createMock(ApiConfigurationInterface::class);

		$configuration
			->expects($this->once())
			->method('getDefaultLimitItem')
			->willReturn(25)
		;
		$configuration
			->expects($this->once())
			->method('getMaxLimitItem')
			->willReturn(100)
		;

		$request = new Request(['limit' => 25, 'page' => 0, 'order' => 'prop1', 'direction' => Direction::ASC->value]);

		$apiSearch = new ApiSearchTestApiFind($requestStack, $logger, $configuration);
		$apiSearch->request = $request;

		$this->expectException(\LogicException::class);

		$apiSearch->apiFindBy(\stdClass::class);
	}

	public function testStaticArrayList() {
		$requestStack    = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
		$logger          = $this->createMock(LoggerInterface::class);
		$configuration   = $this->createMock(ApiConfigurationInterface::class);
		$request         = new Request();

		$configuration
			->expects($this->any())
			->method('getMaxLimitItem')
			->willReturn(4242)
		;
		$configuration
			->expects($this->any())
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
