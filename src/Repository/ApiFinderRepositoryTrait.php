<?php
namespace GollumSF\RestBundle\Repository;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use GollumSF\RestBundle\Model\ApiList;

trait ApiFinderRepositoryTrait {
	
	public function apiFindBy(int $limit, int $page, string $order = null, string $direction = null, \Closure $queryCallback = null): ApiList {
		
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

		$queryBuilder->select('COUNT(t)');
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

		$order = $order !== null ? preg_replace("/[^(a-zA-Z0-9\-_)]/", '', $order): null;
		if ($order) {
			$queryBuilder->orderBy('t.`'.$order.'`', $direction);
		}
		
		$data  = $queryBuilder->getQuery()->getResult();
		
		return new ApiList($data, $total);
	}
	
}