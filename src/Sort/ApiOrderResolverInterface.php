<?php
namespace GollumSF\RestBundle\Sort;

use GollumSF\RestBundle\Model\ApiOrderCollection;
use GollumSF\RestBundle\Sort\Handler\HandlerInterface;

interface ApiOrderResolverInterface {

	const HANDLER_TAG = 'gollumsf.rest.sort_resolver.handler';

	public function addHandler(HandlerInterface $handler): void;

	/**
	 * @param string|null $order     Raw `order` query parameter: `title`, `createdAt:desc`,
	 *                               `author.name:asc,id:desc`.
	 * @param string|null $direction Raw `direction` query parameter, deprecated. Applies to
	 *                               the keys that carry no explicit direction.
	 */
	public function resolve(ApiSortContext $context, ?string $order, ?string $direction = null): ApiOrderCollection;
}
