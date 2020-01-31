<?php
namespace TestCase\GollumSF\RestBundle\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
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
	
	public function testMaxItem() {

		$em       = $this->getMockForAbstractClass(EntityManagerInterface::class); 
		$metadata = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();	
		
		$apiFinderRepository = new ApiFinderRepository($em, $metadata);

		$this->assertEquals($apiFinderRepository->getMaxItem(), ApiFinderRepositoryInterface::DEFAULT_MAX_ITEM);
		$this->assertEquals($apiFinderRepository->setMaxItem(999), $apiFinderRepository);
		$this->assertEquals($apiFinderRepository->getMaxItem(), 999);
		
	}
	public function testApiFindBy() {
		
		$limit = 10;
		$page = 0;
		$order = null;
		$direction = null;
		$queryCallback = null;
		$firstResult = 0;
		$orderResult = null;
		
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
			->with($limit)
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
				->with($orderResult)
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

		$apiFinderRepository->apiFindBy($limit, $page, $order, $direction, $queryCallback);
		
	}
	
}