<?php
namespace GollumSF\RestBundle\Sort;

use GollumSF\RestBundle\Model\ApiOrderCollection;
use GollumSF\RestBundle\Model\Direction;
use GollumSF\RestBundle\Sort\Handler\HandlerInterface;

class ApiOrderResolver implements ApiOrderResolverInterface {

	/** @var HandlerInterface[] */
	private $handlers = [];

	public function addHandler(HandlerInterface $handler): void {
		$this->handlers[] = $handler;
	}

	public function resolve(ApiSortContext $context, ?string $order, ?string $direction = null): ApiOrderCollection {

		$defaultDirection = $this->parseDirection($direction);
		$orders = new ApiOrderCollection();

		foreach ($this->parse($order) as [ $key, $keyDirection ]) {
			foreach ($this->handlers as $handler) {
				$apiOrder = $handler->getOrder($context, $key, $keyDirection ?? $defaultDirection);
				if ($apiOrder) {
					$orders->add($apiOrder);
					break;
				}
			}
		}

		return $orders;
	}

	/**
	 * `author.name:asc,id:desc` => [ [ 'author.name', Direction::ASC ], [ 'id', Direction::DESC ] ]
	 *
	 * @return array<int, array{0: string, 1: Direction|null}>
	 */
	private function parse(?string $order): array {

		$parsed = [];

		foreach (explode(',', (string)$order) as $item) {

			$direction = null;
			if (str_contains($item, ':')) {
				[ $item, $rawDirection ] = explode(':', $item, 2);
				$direction = $this->parseDirection($rawDirection);
			}

			$item = trim($item);
			if ($item === '') {
				continue;
			}

			$parsed[] = [ $item, $direction ];
		}

		return $parsed;
	}

	private function parseDirection(?string $direction): ?Direction {
		return $direction !== null ? Direction::tryFrom(strtoupper(trim($direction))) : null;
	}
}
