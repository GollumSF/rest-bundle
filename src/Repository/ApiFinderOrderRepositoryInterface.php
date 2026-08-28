<?php
namespace GollumSF\RestBundle\Repository;

use GollumSF\RestBundle\Model\ApiList;
use GollumSF\RestBundle\Model\ApiOrderCollection;

/**
 * Multi key ordering, with joins and custom handlers.
 *
 * ApiFinderRepositoryTrait implements it, so repositories using the trait get it for
 * free without having to declare it. Declare it explicitly only when writing the method
 * by hand.
 */
interface ApiFinderOrderRepositoryInterface extends ApiFinderRepositoryInterface {

	public function apiFindByOrder(int $limit, int $page, ApiOrderCollection $orders, ?\Closure $queryCallback = null): ApiList;
}
