<?php
namespace GollumSF\RestBundle\Sort\Handler;

use GollumSF\RestBundle\Model\ApiOrder;
use GollumSF\RestBundle\Model\Direction;
use GollumSF\RestBundle\Sort\ApiSortContext;

/**
 * Last resort handler: takes the key as a property path relative to the entity.
 *
 * This is the historical behaviour, kept for every entity declaring no #[ApiSortable].
 * An unknown property still ends up as a 400 through the QueryException, as before.
 */
class PropertyPathHandler implements HandlerInterface {

	public function getOrder(ApiSortContext $context, string $key, ?Direction $direction): ?ApiOrder {

		$segments = array_filter(
			explode('.', (string)preg_replace("/[^(a-zA-Z0-9\-_.)]/", '', $key)),
			static function ($segment) { return $segment !== ''; }
		);

		if (!$segments) {
			return null;
		}

		return new ApiOrder($key, $direction ?? Direction::ASC, implode('.', $segments));
	}
}
