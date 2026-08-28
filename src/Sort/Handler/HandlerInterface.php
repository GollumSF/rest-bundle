<?php
namespace GollumSF\RestBundle\Sort\Handler;

use GollumSF\RestBundle\Model\ApiOrder;
use GollumSF\RestBundle\Model\Direction;
use GollumSF\RestBundle\Sort\ApiSortContext;

/**
 * Turns one requested sort key into an ApiOrder.
 *
 * Handlers are tried by descending priority; the first one returning an ApiOrder wins.
 * Returning null passes the key to the next handler. A handler that owns the key but
 * refuses it should throw a BadRequestHttpException.
 */
interface HandlerInterface {

	/**
	 * @param string         $key       Key as asked for in the `order` query parameter.
	 * @param Direction|null $direction Direction asked for, null when the request gave none.
	 */
	public function getOrder(ApiSortContext $context, string $key, ?Direction $direction): ?ApiOrder;
}
