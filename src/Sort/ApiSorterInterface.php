<?php
namespace GollumSF\RestBundle\Sort;

use Doctrine\ORM\QueryBuilder;
use GollumSF\RestBundle\Model\Direction;

/**
 * Applies an ordering no property path can express: a computed expression, an
 * aggregate, a CASE WHEN, a join on a filtered relation...
 *
 * Registered with the `gollumsf_rest.sorter` tag and referenced by class name from
 * #[ApiSortable(sorter: ...)].
 */
interface ApiSorterInterface {

	const TAG = 'gollumsf_rest.sorter';

	/**
	 * @param string $rootAlias Alias of the root entity in the query builder.
	 */
	public function apply(QueryBuilder $queryBuilder, string $rootAlias, Direction $direction): void;
}
