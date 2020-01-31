<?php
namespace GollumSF\RestBundle\Search;

use GollumSF\RestBundle\Model\ApiList;

interface ApiSearchInterface {
	public function apiFindBy(string $entityClass, \Closure $queryCallback = null): ApiList;
}