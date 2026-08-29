<?php
namespace GollumSF\RestBundle\Sort;

use GollumSF\RestBundle\Model\Direction;

/**
 * Reads the `order` query parameter: comma separated keys, each optionally suffixed by
 * its own direction.
 *
 *     author.name:desc,id
 *
 * Shared by the resolver and by the static lists so that both speak the same language.
 */
final class ApiOrderParser {

	/**
	 * @param string|null $order     Raw `order` query parameter.
	 * @param string|null $direction Raw `direction` query parameter, deprecated. Applies
	 *                               to the keys carrying no direction of their own.
	 *
	 * @return array<int, array{0: string, 1: Direction|null}>
	 */
	public static function parse(?string $order, ?string $direction = null): array {

		$defaultDirection = self::parseDirection($direction);
		$parsed = [];

		foreach (explode(',', (string)$order) as $item) {

			$keyDirection = null;
			if (str_contains($item, ':')) {
				[ $item, $rawDirection ] = explode(':', $item, 2);
				$keyDirection = self::parseDirection($rawDirection);
			}

			$item = trim($item);
			if ($item === '') {
				continue;
			}

			$parsed[] = [ $item, $keyDirection ?? $defaultDirection ];
		}

		return $parsed;
	}

	/**
	 * An unreadable direction is ignored rather than rejected.
	 */
	public static function parseDirection(?string $direction): ?Direction {
		return $direction !== null ? Direction::tryFrom(strtoupper(trim($direction))) : null;
	}
}
