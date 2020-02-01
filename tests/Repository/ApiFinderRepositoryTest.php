<?php
namespace TestCase\GollumSF\RestBundle\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use GollumSF\RestBundle\Model\Direction;
use GollumSF\RestBundle\Repository\ApiFinderRepository;
use GollumSF\RestBundle\Repository\ApiFinderRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiFinderRepositoryTestApiFindBy extends ApiFinderRepository {
	
	public $queryBuilder;
	
	public function createQueryBuilder($alias, $indexBy = null) {
		return $this->queryBuilder;
	}

}

class ApiFinderRepositoryTest extends WebTestCase {
	
	public function providerApiFindBy() {
		return [
			[ 10, 0, null, null, 10, 0, null, null ],
			[ 0, 0, null, null, 1, 0, null, null ],
			[ 10, -1, null, null, 10, 0, null, null ],
			[ 10, 2, null, null, 10, 20, null, null ],
			[ 10, 0, 'prop_09-', null, 10, 0, 't.`prop_09-`', null ],
			[ 10, 0, 'prop\\/.', null, 10, 0, 't.`prop`', null ],
			[ 10, 0, 'prop.', Direction::ASC, 10, 0, 't.`prop`', 'ASC' ],
			[ 10, 0, 'prop.', Direction::DESC, 10, 0, 't.`prop`', 'DESC' ],
		];
	}

	/**
	 * @dataProvider providerApiFindBy
	 */
	public function testApiFindBy($limit, $page, $order, $direction, $limitResult, $firstResult, $orderResult, $directionResult) {
		
		$em       = $this->getMockForAbstractClass(EntityManagerInterface::class);
		$metadata = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();

		$queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
		$queryCount   = $this->getMockBuilder(AbstractQuery::class)->disableOriginalConstructor()->getMock();
		$query        = $this->getMockBuilder(AbstractQuery::class)->disableOriginalConstructor()->getMock();

		$i = 0;
		$queryBuilder
			->expects($this->at($i++))
			->method('select')
			->with('COUNT(t)')
			->willReturn($queryBuilder)
		;
		$queryBuilder
			->expects($this->at($i++))
			->method('getQuery')
			->willReturn($queryCount)
		;
		$queryBuilder
			->expects($this->at($i++))
			->method('select')
			->with('t')
			->willReturn($queryBuilder)
		;
		$queryBuilder
			->expects($this->at($i++))
			->method('setMaxResults')
			->with($limitResult)
			->willReturn($queryBuilder)
		;
		$queryBuilder
			->expects($this->at($i++))
			->method('setFirstResult')
			->with($firstResult)
			->willReturn($queryBuilder)
		;
		if ($orderResult) {
			$queryBuilder
				->expects($this->at($i++))
				->method('orderBy')
				->with($orderResult, $directionResult)
				->willReturn($queryBuilder)
			;
		}
		$queryBuilder
			->expects($this->at($i++))
			->method('getQuery')
			->willReturn($query)
		;
		$queryCount
			->expects($this->once())
			->method('getSingleScalarResult')
			->willReturn(42)
		;
		$query
			->expects($this->once())
			->method('getResult')
			->willReturn([ 'RESULT1', 'RESULT2', 'RESULT3' ])
		;
		
		$apiFinderRepository = new ApiFinderRepositoryTestApiFindBy($em, $metadata);
		$apiFinderRepository->queryBuilder = $queryBuilder;

		$result = $apiFinderRepository->apiFindBy($limit, $page, $order, $direction, null);

		$this->assertEquals($result->getData(), [ 'RESULT1', 'RESULT2', 'RESULT3' ]);
		$this->assertEquals($result->getTotal(), 42);
	}
	
	public function testApiFindByException() {

		$em       = $this->getMockForAbstractClass(EntityManagerInterface::class);
		$metadata = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();

		$queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
		$query        = $this->getMockBuilder(AbstractQuery::class)->disableOriginalConstructor()->getMock();

		$i = 0;
		$queryBuilder
			->expects($this->at($i++))
			->method('select')
			->with('COUNT(t)')
			->willReturn($queryBuilder)
		;
		$queryBuilder
			->expects($this->at($i++))
			->method('getQuery')
			->willThrowException(new NonUniqueResultException())
		;
		$queryBuilder
			->expects($this->at($i++))
			->method('select')
			->with('t')
			->willReturn($queryBuilder)
		;
		$queryBuilder
			->expects($this->at($i++))
			->method('setMaxResults')
			->with(10)
			->willReturn($queryBuilder)
		;
		$queryBuilder
			->expects($this->at($i++))
			->method('setFirstResult')
			->with(0)
			->willReturn($queryBuilder)
		;
		$queryBuilder
			->expects($this->at($i++))
			->method('getQuery')
			->willReturn($query)
		;
		$query
			->expects($this->once())
			->method('getResult')
			->willReturn([ 'RESULT1', 'RESULT2', 'RESULT3' ])
		;

		$apiFinderRepository = new ApiFinderRepositoryTestApiFindBy($em, $metadata);
		$apiFinderRepository->queryBuilder = $queryBuilder;

		$result = $apiFinderRepository->apiFindBy(10, 0, null, null, null);

		$this->assertEquals($result->getData(), [ 'RESULT1', 'RESULT2', 'RESULT3' ]);
		$this->assertEquals($result->getTotal(), 0);
	}


	public function testApiFindByQueryCallback() {

		$em       = $this->getMockForAbstractClass(EntityManagerInterface::class);
		$metadata = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();

		$queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
		$queryCount   = $this->getMockBuilder(AbstractQuery::class)->disableOriginalConstructor()->getMock();
		$query        = $this->getMockBuilder(AbstractQuery::class)->disableOriginalConstructor()->getMock();

		$i = 0;
		$queryBuilder
			->expects($this->at($i++))
			->method('select')
			->with('COUNT(t)')
			->willReturn($queryBuilder)
		;
		$queryBuilder
			->expects($this->at($i++))
			->method('getQuery')
			->willReturn($queryCount)
		;
		$queryBuilder
			->expects($this->at($i++))
			->method('select')
			->with('t')
			->willReturn($queryBuilder)
		;
		$queryBuilder
			->expects($this->at($i++))
			->method('setMaxResults')
			->with(10)
			->willReturn($queryBuilder)
		;
		$queryBuilder
			->expects($this->at($i++))
			->method('setFirstResult')
			->with(0)
			->willReturn($queryBuilder)
		;
		$queryBuilder
			->expects($this->at($i++))
			->method('getQuery')
			->willReturn($query)
		;
		$queryCount
			->expects($this->once())
			->method('getSingleScalarResult')
			->willReturn(42)
		;
		$query
			->expects($this->once())
			->method('getResult')
			->willReturn([ 'RESULT1', 'RESULT2', 'RESULT3' ])
		;

		$called = false;
		$queryCallback = function ($queryBuilder) use (&$called) {
			$called = true;
			$this->assertInstanceOf(QueryBuilder::class, $queryBuilder);
		};
		
		$apiFinderRepository = new ApiFinderRepositoryTestApiFindBy($em, $metadata);
		$apiFinderRepository->queryBuilder = $queryBuilder;

		$result = $apiFinderRepository->apiFindBy(10, 0, null, null, $queryCallback);

		$this->assertEquals($result->getData(), [ 'RESULT1', 'RESULT2', 'RESULT3' ]);
		$this->assertEquals($result->getTotal(), 42);
		$this->assertTrue($called);
	}
	
}