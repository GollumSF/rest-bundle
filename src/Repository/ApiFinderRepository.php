<?php
namespace GollumSF\RestBundle\Repository;

use Doctrine\ORM\EntityRepository;
use GollumSF\RestBundle\Model\ApiList;

class ApiFinderRepository extends EntityRepository implements ApiFinderRepositoryInterface {
	use ApiFinderRepositoryTrait;
}