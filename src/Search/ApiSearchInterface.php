<?php
namespace GollumSF\RestBundle\Search;

use GollumSF\RestBundle\Model\ApiList;
use GollumSF\RestBundle\Model\StaticArrayApiList;

interface ApiSearchInterface {
	public function apiFindBy(string $entityClass, \Closure $queryCallback = null): ApiList;
	public function staticArrayList(array $data, \Closure $sortCallback = null, $globalSort = false): StaticArrayApiList;
}