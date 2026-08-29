<?php
namespace GollumSF\RestBundle\Repository;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use GollumSF\RestBundle\Model\ApiList;
use GollumSF\RestBundle\Model\ApiOrder;
use GollumSF\RestBundle\Model\ApiOrderCollection;
use GollumSF\RestBundle\Model\Direction;

trait ApiFinderRepositoryTrait {

	/**
	 * @deprecated since 4.1, use apiFindByOrder() which carries several keys, joins and sorters.
	 */
	public function apiFindBy(int $limit, int $page, ?string $order = null, ?string $direction = null, ?\Closure $queryCallback = null): ApiList {

		$orders = new ApiOrderCollection();

		$order = $order !== null ? preg_replace("/[^(a-zA-Z0-9\-_.)]/", '', $order) : null;
		if ($order) {
			$orders->add(new ApiOrder(
				$order,
				Direction::tryFrom(strtoupper((string)$direction)) ?? Direction::ASC,
				$order
			));
		}

		return $this->apiFindByOrder($limit, $page, $orders, $queryCallback);
	}

	public function apiFindByOrder(int $limit, int $page, ApiOrderCollection $orders, ?\Closure $queryCallback = null): ApiList {

		if ($limit < 1 ) {
			$limit = 1;
		}

		if ($page < 0) {
			$page = 0;
		}

		/** @var QueryBuilder $queryBuilder */
		$queryBuilder = $this->createQueryBuilder('t');

		if ($queryCallback) {
			$queryCallback($queryBuilder);
		}

		// DISTINCT, otherwise a join on a collection added by the query callback counts
		// the same entity once per joined row.
		$queryBuilder->select('COUNT(DISTINCT t)');
		$total = 0;
		try {
			$total = $queryBuilder->getQuery()->getSingleScalarResult();
		} catch (NonUniqueResultException $e) {
		}

		$queryBuilder
			->select('t')
			->setMaxResults($limit)
			->setFirstResult($limit * $page)
		;

		$this->apiApplyOrders($queryBuilder, $orders);

		$data  = $queryBuilder->getQuery()->getResult();

		return new ApiList($data, $total);
	}

	/**
	 * Ordering is applied after the count, so the joins it needs never weigh on it.
	 */
	private function apiApplyOrders(QueryBuilder $queryBuilder, ApiOrderCollection $orders): void {

		if ($orders->isEmpty()) {
			return;
		}

		$rootAlias = $queryBuilder->getRootAliases()[0];
		$rootMetadata = $this->apiRootMetadata($queryBuilder);
		$index = 0;

		foreach ($orders as $order) {

			$sorter = $order->getSorter();
			if ($sorter) {
				$sorter->apply($queryBuilder, $rootAlias, $order->getDirection());
				continue;
			}

			$path = $order->getPath();
			if (!$path) {
				continue;
			}

			$segments = array_values(array_filter(
				explode('.', $path),
				static function ($segment) { return $segment !== ''; }
			));
			if (!$segments) {
				continue;
			}
			$field = array_pop($segments);

			$alias = $rootAlias;
			$metadata = $rootMetadata;
			$crossesCollection = false;

			foreach ($segments as $segment) {
				if ($metadata && $metadata->hasAssociation($segment)) {
					$crossesCollection = $crossesCollection || $metadata->isCollectionValuedAssociation($segment);
					$metadata = $queryBuilder->getEntityManager()->getClassMetadata($metadata->getAssociationTargetClass($segment));
				} else {
					$metadata = null;
				}
				$alias = $this->apiJoinAlias($queryBuilder, $alias, $segment);
			}

			$identifier = $crossesCollection ? $this->apiSingleIdentifier($rootMetadata) : null;

			if ($identifier !== null) {
				// Ordering straight on a collection would multiply the rows, and the limit
				// would then cut a page short. An aggregate keeps one row per entity.
				$sortAlias = '_gsf_sort_'.$index;
				$queryBuilder
					->addSelect(sprintf(
						'%s(%s.%s) AS HIDDEN %s',
						$order->getDirection() === Direction::DESC ? 'MAX' : 'MIN',
						$alias,
						$field,
						$sortAlias
					))
					->addGroupBy($rootAlias.'.'.$identifier)
					->addOrderBy($sortAlias, $order->getDirection()->value)
				;
			} else {
				$queryBuilder->addOrderBy($alias.'.'.$field, $order->getDirection()->value);
			}

			$index++;
		}
	}

	/**
	 * Null as soon as the query builder cannot tell, which leaves the ordering on the
	 * plain join it has always used.
	 */
	private function apiRootMetadata(QueryBuilder $queryBuilder): ?ClassMetadata {
		$entities = $queryBuilder->getRootEntities();
		if (!$entities || !$queryBuilder->getEntityManager()) {
			return null;
		}
		return $queryBuilder->getEntityManager()->getClassMetadata($entities[0]);
	}

	/**
	 * Only a single valued identifier can carry the grouping.
	 */
	private function apiSingleIdentifier(ClassMetadata $metadata): ?string {
		$identifiers = $metadata->getIdentifierFieldNames();
		return count($identifiers) === 1 ? $identifiers[0] : null;
	}

	/**
	 * Reuses the alias when the relation is already joined, by the query callback or by
	 * an earlier ordering, so that two sorts on the same relation share one join.
	 */
	private function apiJoinAlias(QueryBuilder $queryBuilder, string $alias, string $relation): string {

		$join = $alias.'.'.$relation;

		foreach ($queryBuilder->getDQLPart('join') as $joins) {
			foreach ($joins as $existing) {
				if ($existing->getJoin() === $join) {
					return $existing->getAlias();
				}
			}
		}

		$newAlias = '_gsf_'.str_replace('.', '_', $join);
		$queryBuilder->leftJoin($join, $newAlias);

		return $newAlias;
	}
}
