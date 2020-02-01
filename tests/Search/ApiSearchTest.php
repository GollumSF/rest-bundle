<?php
namespace TestCase\GollumSF\RestBundle\Search;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use GollumSF\ReflectionPropertyTest\ReflectionPropertyTrait;
use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use GollumSF\RestBundle\Model\ApiList;
use GollumSF\RestBundle\Model\Direction;
use GollumSF\RestBundle\Model\StaticArrayApiList;
use GollumSF\RestBundle\Repository\ApiFinderRepositoryInterface;
use GollumSF\RestBundle\Search\ApiSearch;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ApiSearchTestApiFind extends ApiSearch {

	public $request;
	public $repository;
	
	public function getMasterRequest(): Request {
		return $this->request;
	}
	public function getRepository(string $entityClass) {
		return $this->repository;
	}
}

class ApiSearchTest extends TestCase {
	
	use ReflectionPropertyTrait;

	public function testGetMasterRequest() {

		$managerRegistry = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
		$requestStack    = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
		$configuration   = $this->getMockForAbstractClass(ApiConfigurationInterface::class);
		$request         = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

		$requestStack
			->expects($this->once())
			->method('getMasterRequest')
			->willReturn($request)
		;

		$apiSearch = new ApiSearch($managerRegistry, $requestStack, $configuration);

		$this->assertEquals(
			$this->reflectionCallMethod($apiSearch, 'getMasterRequest'), $request
		);
	}

	public function testGetRepository() {

		$managerRegistry = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
		$requestStack    = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
		$configuration   = $this->getMockForAbstractClass(ApiConfigurationInterface::class);
		$em              = $this->getMockForAbstractClass(EntityManagerInterface::class);
		$repository      = $this->getMockForAbstractClass(ObjectRepository::class);

		$managerRegistry
			->expects($this->once())
			->method('getManagerForClass')
			->with(\stdClass::class)
			->willReturn($em)
		;
		$em
			->expects($this->once())
			->method('getRepository')
			->with(\stdClass::class)
			->willReturn($repository)
		;

		$apiSearch = new ApiSearch($managerRegistry, $requestStack, $configuration);

		$this->assertEquals(
			$this->reflectionCallMethod($apiSearch, 'getRepository', [ \stdClass::class ]), $repository
		);
	}

	public function testGetRepositoryNull() {

		$managerRegistry = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
		$requestStack    = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
		$configuration   = $this->getMockForAbstractClass(ApiConfigurationInterface::class);
		$em              = $this->getMockForAbstractClass(EntityManagerInterface::class);
		$repository      = $this->getMockForAbstractClass(ObjectRepository::class);

		$managerRegistry
			->expects($this->once())
			->method('getManagerForClass')
			->with(\stdClass::class)
			->willReturn(null)
		;
		$em
			->expects($this->never())
			->method('getRepository')
			->with(\stdClass::class)
			->willReturn($repository)
		;

		$apiSearch = new ApiSearch($managerRegistry, $requestStack, $configuration);

		$this->assertNull(
			$this->reflectionCallMethod($apiSearch, 'getRepository', [ \stdClass::class ])
		);
	}

	public function testApiFindNoInstanceOfApiFindRepository() {

		$managerRegistry = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
		$requestStack    = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
		$configuration   = $this->getMockForAbstractClass(ApiConfigurationInterface::class);
		$request         = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
		$repository      = $this->getMockForAbstractClass(ObjectRepository::class);
		
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

		$apiSearch = new ApiSearchTestApiFind($managerRegistry, $requestStack, $configuration);
		$apiSearch->repository = $repository;
		$apiSearch->request = $request;

		$this->expectException(\LogicException::class);
		
		$apiSearch->apiFindBy(\stdClass::class);
	}

	public function testApiFindNoInstanceNoRepository() {

		$managerRegistry = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
		$requestStack    = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
		$configuration   = $this->getMockForAbstractClass(ApiConfigurationInterface::class);
		$request         = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

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

		$apiSearch = new ApiSearchTestApiFind($managerRegistry, $requestStack, $configuration);
		$apiSearch->request = $request;

		$this->expectException(\LogicException::class);

		$apiSearch->apiFindBy(\stdClass::class);
	}
	
//	public function apiFindBy(string $entityClass, \Closure $queryCallback = null): ApiList {
//
//		$request   = $this->getMasterRequest();
//		$limit     = (int)$request->get('limit', $this->apiConfiguration->getDefaultLimitItem());
//		$page      = (int)$request->get('page' , 0);
//		$order     = $request->get('order');
//		$direction = strtoupper($request->get('direction'));
//		
//		$maxtLimitItem = $this->apiConfiguration->getMaxLimitItem();
//		if ($maxtLimitItem && $limit >  $maxtLimitItem) {
//			$limit = $maxtLimitItem;
//		}
//		
//		if (!Direction::isValid($direction)) {
//			$direction = null;
//		}
//
//		/** @var ApiFinderRepositoryInterface $repository */
//		$repository = $this->getRepository($entityClass);
//		if (!$repository) {
//			throw new \LogicException(sprintf('Repository not found for class %s', $entityClass));
//		}
//		if (!($repository instanceof ApiFinderRepositoryInterface)) {
//			throw new \LogicException(sprintf('Repository of class %s must implement ApiFinderRepositoryInterface or extends ApiFinderRepository', $entityClass));
//		}
//		return $repository->apiFindBy($limit, $page, $order, $direction, $queryCallback);
//	}
//	
//	public function staticArrayList(array $data): StaticArrayApiList {
//		$request   = $this->getMasterRequest();
//		$arrayList = new StaticArrayApiList($data, $request);
//		$arrayList->setMaxLimitItem($this->apiConfiguration->getMaxLimitItem());
//		$arrayList->setDefaultLimitItem($this->apiConfiguration->getDefaultLimitItem());
//	}
}