<?php
namespace GollumSF\RestBundle\Repository;
use GollumSF\RestBundle\Model\ApiList;

interface ApiFinderRepositoryInterface {
	public function apiFindBy(int $limit, int $page, string $order = null, string $direction = null, \Closure $queryCallback = null): ApiList;
}