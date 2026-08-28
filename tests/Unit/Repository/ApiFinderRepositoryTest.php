<?php
namespace Test\GollumSF\RestBundle\Unit\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use GollumSF\RestBundle\Configuration\ApiConfigurationInterface;
use GollumSF\RestBundle\Model\ApiOrder;
use GollumSF\RestBundle\Model\ApiOrderCollection;
use GollumSF\RestBundle\Model\Direction;
use GollumSF\RestBundle\Repository\ApiFinderRepository;
use GollumSF\RestBundle\Repository\ApiFinderRepositoryInterface;
use GollumSF\RestBundle\Sort\ApiSorterInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Test\GollumSF\RestBundle\Helper\WithConsecutiveTrait;
use PHPUnit\Framework\Attributes\DataProvider;

class ApiFinderRepositoryTestApiFindBy extends ApiFinderRepository {

	public $queryBuilder;

	public function createQueryBuilder(string $alias, ?string $indexBy = null): QueryBuilder {
		return $this->queryBuilder;
	}

}

class ApiFinderRepositoryTest extends WebTestCase {

	use WithConsecutiveTrait;

	public static function providerApiFindBy() {
		return [
			[ 10, 0, null, null, 10, 0, null, null ],
			[ 0, 0, null, null, 1, 0, null, null ],
			[ 10, -1, null, null, 10, 0, null, null ],
			[ 10, 2, null, null, 10, 20, null, null ],
			[ 10, 0, 'prop_09-', null, 10, 0, 't.prop_09-', 'ASC' ],
			[ 10, 0, 'prop\\/.', null, 10, 0, 't.prop', 'ASC' ],
			[ 10, 0, 'prop.', Direction::ASC->value, 10, 0, 't.prop', 'ASC' ],
			[ 10, 0, 'prop.', Direction::DESC->value, 10, 0, 't.prop', 'DESC' ],
		];
	}

	#[DataProvider('providerApiFindBy')]
	public function testApiFindBy($limit, $page, $order, $direction, $limitResult, $firstResult, $orderResult, $directionResult) {

		$em       = $this->createMock(EntityManagerInterface::class);
		$metadata = new ClassMetadata(\stdClass::class);

		$queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
		$queryCount   = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->getMock();
		$query        = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->getMock();

		[$selectCallback, $selectCount] = self::withConsecutiveArgs(
			[[ 'COUNT(t)' ], [ 't' ]],
			[$queryBuilder, $queryBuilder]
		);
		$queryBuilder
			->expects($this->exactly($selectCount))
			->method('select')
			->willReturnCallback($selectCallback)
		;

		[$getQueryCallback, $getQueryCount] = self::withConsecutiveArgs(
			[[], []],
			[$queryCount, $query]
		);
		$queryBuilder
			->expects($this->exactly($getQueryCount))
			->method('getQuery')
			->willReturnCallback($getQueryCallback)
		;
		$queryBuilder
			->expects($this->once())
			->method('setMaxResults')
			->with($limitResult)
			->willReturn($queryBuilder)
		;
		$queryBuilder
			->expects($this->once())
			->method('setFirstResult')
			->with($firstResult)
			->willReturn($queryBuilder)
		;
		$queryBuilder
			->method('getRootAliases')
			->willReturn([ 't' ])
		;
		if ($orderResult) {
			$queryBuilder
				->expects($this->once())
				->method('addOrderBy')
				->with($orderResult, $directionResult)
				->willReturn($queryBuilder)
			;
		} else {
			$queryBuilder
				->expects($this->never())
				->method('addOrderBy')
			;
		}

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

		$em       = $this->createMock(EntityManagerInterface::class);
		$metadata = new ClassMetadata(\stdClass::class);

		$queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
		$queryCount   = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->getMock();
		$query        = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->getMock();

		[$selectCallback, $selectCount] = self::withConsecutiveArgs(
			[[ 'COUNT(t)' ], [ 't' ]],
			[$queryBuilder, $queryBuilder]
		);
		$queryBuilder
			->expects($this->exactly($selectCount))
			->method('select')
			->willReturnCallback($selectCallback)
		;

		[$getQueryCallback, $getQueryCount] = self::withConsecutiveArgs(
			[[], []],
			[$queryCount, $query]
		);
		$queryBuilder
			->expects($this->exactly($getQueryCount))
			->method('getQuery')
			->willReturnCallback($getQueryCallback)
		;

		$queryBuilder
			->expects($this->once())
			->method('setMaxResults')
			->with(10)
			->willReturn($queryBuilder)
		;
		$queryBuilder
			->expects($this->once())
			->method('setFirstResult')
			->with(0)
			->willReturn($queryBuilder)
		;

		$queryCount
			->expects($this->once())
			->method('getSingleScalarResult')
			->willThrowException(new NonUniqueResultException())
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

		$em       = $this->createMock(EntityManagerInterface::class);
		$metadata = new ClassMetadata(\stdClass::class);

		$queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
		$queryCount   = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->getMock();
		$query        = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->getMock();


		[$selectCallback, $selectCount] = self::withConsecutiveArgs(
			[[ 'COUNT(t)' ], [ 't' ]],
			[$queryBuilder, $queryBuilder]
		);
		$queryBuilder
			->expects($this->exactly($selectCount))
			->method('select')
			->willReturnCallback($selectCallback)
		;
		[$getQueryCallback, $getQueryCount] = self::withConsecutiveArgs(
			[[], []],
			[$queryCount, $query]
		);
		$queryBuilder
			->expects($this->exactly($getQueryCount))
			->method('getQuery')
			->willReturnCallback($getQueryCallback)
		;
		$queryBuilder
			->expects($this->once())
			->method('setMaxResults')
			->with(10)
			->willReturn($queryBuilder)
		;
		$queryBuilder
			->expects($this->once())
			->method('setFirstResult')
			->with(0)
			->willReturn($queryBuilder)
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

	/**
	 * A real QueryBuilder, so that the joins the ordering generates are actually observable.
	 */
	private function createRealQueryBuilder(): QueryBuilder {

		$query = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->getMock();
		$query->method('setParameters')->willReturnSelf();
		$query->method('setFirstResult')->willReturnSelf();
		$query->method('setMaxResults')->willReturnSelf();
		$query->method('getSingleScalarResult')->willReturn(42);
		$query->method('getResult')->willReturn([ 'RESULT1' ]);

		$em = $this->createMock(EntityManagerInterface::class);
		$em->method('createQuery')->willReturn($query);

		$queryBuilder = new QueryBuilder($em);
		$queryBuilder->select('t')->from(\stdClass::class, 't');

		return $queryBuilder;
	}

	private function createRepository(QueryBuilder $queryBuilder): ApiFinderRepositoryTestApiFindBy {
		$repository = new ApiFinderRepositoryTestApiFindBy(
			$this->createMock(EntityManagerInterface::class),
			new ClassMetadata(\stdClass::class)
		);
		$repository->queryBuilder = $queryBuilder;
		return $repository;
	}

	/**
	 * @return string[]
	 */
	private function orderByParts(QueryBuilder $queryBuilder): array {
		$parts = [];
		foreach ($queryBuilder->getDQLPart('orderBy') as $orderBy) {
			foreach ($orderBy->getParts() as $part) {
				$parts[] = $part;
			}
		}
		return $parts;
	}

	/**
	 * @return string[] join expression => alias
	 */
	private function joinParts(QueryBuilder $queryBuilder): array {
		$joins = [];
		foreach ($queryBuilder->getDQLPart('join') as $rootJoins) {
			foreach ($rootJoins as $join) {
				$joins[$join->getJoin()] = $join->getAlias();
			}
		}
		return $joins;
	}

	public function testApiFindByOrderMultipleKeys() {

		$queryBuilder = $this->createRealQueryBuilder();

		$result = $this->createRepository($queryBuilder)->apiFindByOrder(10, 0, new ApiOrderCollection([
			new ApiOrder('title', Direction::ASC, 'title'),
			new ApiOrder('id', Direction::DESC, 'id'),
		]));

		$this->assertEquals([ 't.title ASC', 't.id DESC' ], $this->orderByParts($queryBuilder));
		$this->assertEquals([], $this->joinParts($queryBuilder));
		$this->assertEquals([ 'RESULT1' ], $result->getData());
		$this->assertEquals(42, $result->getTotal());
	}

	public function testApiFindByOrderJoin() {

		$queryBuilder = $this->createRealQueryBuilder();

		$this->createRepository($queryBuilder)->apiFindByOrder(10, 0, new ApiOrderCollection([
			new ApiOrder('author', Direction::DESC, 'author.name'),
		]));

		$this->assertEquals([ 't.author' => '_gsf_t_author' ], $this->joinParts($queryBuilder));
		$this->assertEquals([ '_gsf_t_author.name DESC' ], $this->orderByParts($queryBuilder));
	}

	public function testApiFindByOrderNestedJoin() {

		$queryBuilder = $this->createRealQueryBuilder();

		$this->createRepository($queryBuilder)->apiFindByOrder(10, 0, new ApiOrderCollection([
			new ApiOrder('country', Direction::ASC, 'author.country.name'),
		]));

		$this->assertEquals([
			't.author' => '_gsf_t_author',
			'_gsf_t_author.country' => '_gsf__gsf_t_author_country',
		], $this->joinParts($queryBuilder));
		$this->assertEquals([ '_gsf__gsf_t_author_country.name ASC' ], $this->orderByParts($queryBuilder));
	}

	public function testApiFindByOrderReusesTheJoinOfTheQueryCallback() {

		$queryBuilder = $this->createRealQueryBuilder();

		$this->createRepository($queryBuilder)->apiFindByOrder(
			10,
			0,
			new ApiOrderCollection([ new ApiOrder('author', Direction::ASC, 'author.name') ]),
			function (QueryBuilder $queryBuilder) {
				$queryBuilder->leftJoin('t.author', 'a');
			}
		);

		$this->assertEquals([ 't.author' => 'a' ], $this->joinParts($queryBuilder));
		$this->assertEquals([ 'a.name ASC' ], $this->orderByParts($queryBuilder));
	}

	public function testApiFindByOrderSharesOneJoinBetweenTwoKeys() {

		$queryBuilder = $this->createRealQueryBuilder();

		$this->createRepository($queryBuilder)->apiFindByOrder(10, 0, new ApiOrderCollection([
			new ApiOrder('author', Direction::ASC, 'author.name'),
			new ApiOrder('authorId', Direction::DESC, 'author.id'),
		]));

		$this->assertEquals([ 't.author' => '_gsf_t_author' ], $this->joinParts($queryBuilder));
		$this->assertEquals([ '_gsf_t_author.name ASC', '_gsf_t_author.id DESC' ], $this->orderByParts($queryBuilder));
	}

	public function testApiFindByOrderSorter() {

		$queryBuilder = $this->createRealQueryBuilder();

		$sorter = $this->createMock(ApiSorterInterface::class);
		$sorter
			->expects($this->once())
			->method('apply')
			->with($queryBuilder, 't', Direction::DESC)
			->willReturnCallback(function (QueryBuilder $queryBuilder, string $alias, Direction $direction) {
				$queryBuilder->addOrderBy('SIZE('.$alias.'.comments)', $direction->value);
			})
		;

		$this->createRepository($queryBuilder)->apiFindByOrder(10, 0, new ApiOrderCollection([
			new ApiOrder('popularity', Direction::DESC, null, $sorter),
		]));

		$this->assertEquals([ 'SIZE(t.comments) DESC' ], $this->orderByParts($queryBuilder));
	}

	public function testApiFindByOrderIgnoresAnEmptyPath() {

		$queryBuilder = $this->createRealQueryBuilder();

		$this->createRepository($queryBuilder)->apiFindByOrder(10, 0, new ApiOrderCollection([
			new ApiOrder('nothing', Direction::ASC, null),
			new ApiOrder('dots', Direction::ASC, '...'),
		]));

		$this->assertEquals([], $this->orderByParts($queryBuilder));
		$this->assertEquals([], $this->joinParts($queryBuilder));
	}

}
