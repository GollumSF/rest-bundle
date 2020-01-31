<?php
namespace GollumSF\RestBundle\Repository;
use GollumSF\RestBundle\Model\ApiList;

interface ApiFinderRepositoryInterface {

	const DEFAULT_LIMIT_ITEM = 25;
	const DEFAULT_MAX_ITEM = 100;

	const DIRECTION_ASC = 'ASC';
	const DIRECTION_DESC = 'DESC';

	public function getMaxItem(): int;
	public function setMaxItem(int $maxItem);
	public function apiFindBy(int $limit, int $page, string $order = null, string $direction = null, \Closure $queryCallback = null): ApiList;
	
}