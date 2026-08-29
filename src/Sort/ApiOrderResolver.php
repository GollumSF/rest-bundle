<?php
namespace GollumSF\RestBundle\Sort;

use GollumSF\RestBundle\Model\ApiOrderCollection;
use GollumSF\RestBundle\Sort\Handler\HandlerInterface;

class ApiOrderResolver implements ApiOrderResolverInterface {

	/** @var HandlerInterface[] */
	private $handlers = [];

	public function addHandler(HandlerInterface $handler): void {
		$this->handlers[] = $handler;
	}

	public function resolve(ApiSortContext $context, ?string $order, ?string $direction = null): ApiOrderCollection {

		$orders = new ApiOrderCollection();

		foreach (ApiOrderParser::parse($order, $direction) as [ $key, $keyDirection ]) {
			foreach ($this->handlers as $handler) {
				$apiOrder = $handler->getOrder($context, $key, $keyDirection);
				if ($apiOrder) {
					$orders->add($apiOrder);
					break;
				}
			}
		}

		return $orders;
	}

}
